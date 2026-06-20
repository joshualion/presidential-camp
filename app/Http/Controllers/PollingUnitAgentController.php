<?php

namespace App\Http\Controllers;

use App\Models\PollingUnit;
use App\Models\PollingUnitAgentAssignment;
use App\Models\State;
use App\Models\LocalGovernmentArea;
use App\Models\Ward;
use App\Models\User;
use App\Services\LocationScopeService;
use App\Services\CampaignPackageLocationFormService;
use App\Services\LicensedScopeQueryService;
use App\Services\PollingUnitAgentApprovalPolicyService;
use App\Services\PollingUnitAgentAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;

class PollingUnitAgentController extends Controller
{
    public function __construct(
        private readonly PollingUnitAgentAssignmentService $assignments,
        private readonly PollingUnitAgentApprovalPolicyService $policy,
        private readonly LocationScopeService $scopeService,
        private readonly CampaignPackageLocationFormService $locationForms,
        private readonly LicensedScopeQueryService $licensedScope
    ) {
        View::share('pageTitle', 'Polling Unit Agents');
    }

    public function index(Request $request)
    {
        $profileData = $this->profileData();

        $query = $this->assignments->scopedQuery($profileData);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('source')) {
            $query->where('source', $request->string('source'));
        }

        if ($request->filled('identity_status')) {
            $query->where('identity_verification_status', $request->string('identity_status'));
        }

        $assignments = $query->latest()->paginate(25)->withQueryString();

        return view('backend.shared.agents.index', [
            'profileData' => $profileData,
            'assignments' => $assignments,
            'policy' => $this->policy,
        ]);
    }

    public function show(string $uuid)
    {
        $profileData = $this->profileData();
        $assignment = $this->assignmentForViewer($profileData, $uuid);

        return view('backend.shared.agents.show', [
            'profileData' => $profileData,
            'assignment' => $assignment,
            'policy' => $this->policy,
        ]);
    }

    public function createRequest()
    {
        $profileData = $this->profileData();
        abort_unless($this->policy->canRequest($profileData), 403);

        return view('backend.shared.agents.request', [
            'profileData' => $profileData->loadMissing(['state', 'lga', 'ward', 'pollingUnit']),
            'pollingUnitFull' => $profileData->pollingUnit?->loadMissing('ward.localGovernmentArea.state'),
            'pollingUnitHasCapacity' => $profileData->polling_unit_id
                ? $this->assignments->pollingUnitHasCapacity((int) $profileData->polling_unit_id)
                : false,
        ]);
    }

    public function storeRequest(Request $request)
    {
        $profileData = $this->profileData();
        abort_unless($this->policy->canRequest($profileData), 403);
        abort_if(!$profileData->polling_unit_id, 422, 'Please update your profile/voter listing with your registered polling unit before requesting Polling Unit Agent status.');
        abort_unless($this->assignments->pollingUnitHasCapacity((int) $profileData->polling_unit_id), 422, 'This polling unit already has the maximum number of approved agents.');

        $data = $this->validateIdentityRequest($request, true, [], false);
        $this->licensedScope->assertPayloadWithinScope(['polling_unit_id' => $profileData->polling_unit_id]);
        $pollingUnit = PollingUnit::findOrFail($profileData->polling_unit_id);

        try {
            $assignment = $this->assignments->submitSelfRequest(
                $profileData,
                $pollingUnit,
                array_merge($this->identityPayload($request, $data), [
                    'registered_polling_unit_id' => $profileData->polling_unit_id,
                    'assignment_type' => PollingUnitAgentAssignment::ASSIGNMENT_REGISTERED_POLLING_UNIT,
                ])
            );
        } catch (\RuntimeException $exception) {
            return redirect()->back()->withInput()->withErrors(['polling_unit_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route($profileData->access_level.'.agents.show', $assignment->uuid)
            ->with(['message' => 'Polling Unit Agent request submitted for review.', 'alert-type' => 'success']);
    }

    public function myStatus()
    {
        $profileData = $this->profileData();
        $assignments = PollingUnitAgentAssignment::with(['pollingUnit.ward.localGovernmentArea.state', 'approvals.approver'])
            ->where('user_id', $profileData->id)
            ->latest()
            ->paginate(25);

        return view('backend.shared.agents.my-status', [
            'profileData' => $profileData,
            'assignments' => $assignments,
        ]);
    }

    public function createNomination()
    {
        $profileData = $this->profileData();
        abort_unless($this->policy->canNominate($profileData), 403);

        return view('backend.shared.agents.nominate', [
            'profileData' => $profileData,
            'users' => $this->userOptions($profileData),
            'states' => $this->stateOptions($profileData),
            'locationForm' => $this->locationForms->context($profileData),
        ]);
    }

    public function storeNomination(Request $request)
    {
        $profileData = $this->profileData();
        abort_unless($this->policy->canNominate($profileData), 403);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'polling_unit_id' => ['required', 'exists:polling_units,id'],
            'assignment_mode' => ['required', Rule::in(['registered', 'override'])],
            'use_override' => ['nullable'],
            'override_reason' => ['nullable', 'string', 'max:5000'],
            'appointment_note' => ['nullable', 'string', 'max:5000'],
            'identity_type' => ['nullable', 'string', 'max:100'],
            'identity_number' => ['nullable', 'string', 'max:100'],
            'identity_document' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:4096'],
            'voter_evidence_document' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:4096'],
            'passport_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $pollingUnit = PollingUnit::findOrFail($data['polling_unit_id']);
        $this->licensedScope->assertPayloadWithinScope(['polling_unit_id' => $pollingUnit->id]);
        abort_unless($this->policy->canNominate($profileData, $pollingUnit), 403);
        abort_unless($this->assignments->pollingUnitHasCapacity((int) $pollingUnit->id), 422, 'This polling unit already has the maximum number of approved agents.');

        $nominatedUser = User::findOrFail($data['user_id']);
        $isOverride = $data['assignment_mode'] === 'override';
        if (!$isOverride) {
            abort_unless($nominatedUser->polling_unit_id, 422, 'The selected user has no registered polling unit. Use campaign deployment override with a reason.');
            abort_unless((int) $nominatedUser->polling_unit_id === (int) $pollingUnit->id, 422, 'Normal assignment must use the selected user registered polling unit.');
        }
        if ($isOverride) {
            abort_unless($request->boolean('use_override') && !empty($data['override_reason']), 422, 'Please provide a campaign deployment override reason.');
        }

        try {
            $assignment = $this->assignments->nominate(
                $nominatedUser,
                $pollingUnit,
                $profileData,
                array_merge($this->identityPayload($request, $data, false), [
                    'source' => PollingUnitAgentAssignment::SOURCE_ADMIN_APPOINTMENT,
                    'appointment_note' => $data['appointment_note'] ?? null,
                    'registered_polling_unit_id' => $nominatedUser->polling_unit_id,
                    'assignment_type' => $isOverride
                        ? PollingUnitAgentAssignment::ASSIGNMENT_DEPLOYMENT_OVERRIDE
                        : PollingUnitAgentAssignment::ASSIGNMENT_REGISTERED_POLLING_UNIT,
                    'override_reason' => $isOverride ? $data['override_reason'] : null,
                    'override_authorized_by' => $isOverride ? $profileData->id : null,
                    'override_authorized_at' => $isOverride ? now() : null,
                ])
            );
        } catch (\RuntimeException $exception) {
            return redirect()->back()->withInput()->withErrors(['polling_unit_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route($profileData->access_level.'.agents.show', $assignment->uuid)
            ->with(['message' => 'Polling Unit Agent appointment submitted for review.', 'alert-type' => 'success']);
    }

    public function updateIdentity(Request $request, string $uuid)
    {
        $profileData = $this->profileData();
        $assignment = PollingUnitAgentAssignment::where('uuid', $uuid)->firstOrFail();
        abort_unless((int) $assignment->user_id === (int) $profileData->id || $this->policy->canVerifyIdentity($profileData, $assignment), 403);

        $data = $this->validateIdentityRequest($request, true);
        $assignment->forceFill($this->identityPayload($request, $data))->save();

        return redirect()
            ->route($profileData->access_level.'.agents.show', $assignment->uuid)
            ->with(['message' => 'Identity verification details submitted.', 'alert-type' => 'success']);
    }

    public function verifyIdentity(Request $request, string $uuid)
    {
        $profileData = $this->profileData();
        $assignment = $this->assignmentForViewer($profileData, $uuid);
        abort_unless($this->policy->canVerifyIdentity($profileData, $assignment), 403);

        $data = $request->validate(['verification_note' => ['nullable', 'string', 'max:5000']]);
        $this->assignments->verifyIdentity($assignment, $profileData, $data['verification_note'] ?? null);

        return redirect()->back()->with(['message' => 'Identity verified.', 'alert-type' => 'success']);
    }

    public function rejectIdentity(Request $request, string $uuid)
    {
        $profileData = $this->profileData();
        $assignment = $this->assignmentForViewer($profileData, $uuid);
        abort_unless($this->policy->canVerifyIdentity($profileData, $assignment), 403);

        $data = $request->validate(['identity_rejection_reason' => ['required', 'string', 'max:5000']]);
        $this->assignments->rejectIdentity($assignment, $profileData, $data['identity_rejection_reason']);

        return redirect()->back()->with(['message' => 'Identity verification rejected.', 'alert-type' => 'success']);
    }

    public function approve(Request $request, string $uuid)
    {
        $profileData = $this->profileData();
        $assignment = $this->assignmentForViewer($profileData, $uuid);
        abort_unless($this->policy->canApproveCurrentStage($profileData, $assignment), 403);

        $data = $request->validate(['comments' => ['nullable', 'string', 'max:5000']]);
        try {
            $this->assignments->approveStage($assignment, $profileData, $data['comments'] ?? null);
        } catch (\RuntimeException $exception) {
            return redirect()->back()->withErrors(['approval' => $exception->getMessage()]);
        }

        return redirect()->back()->with(['message' => 'Agent request approved for the current stage.', 'alert-type' => 'success']);
    }

    public function approveDirectly(Request $request, string $uuid)
    {
        $profileData = $this->profileData();
        $assignment = $this->assignmentForViewer($profileData, $uuid);
        abort_unless($this->policy->canApproveDirectly($profileData, $assignment), 403);

        $data = $request->validate(['comments' => ['nullable', 'string', 'max:5000']]);
        try {
            $this->assignments->approveDirectly($assignment, $profileData, $data['comments'] ?? null);
        } catch (\RuntimeException $exception) {
            return redirect()->back()->withErrors(['approval' => $exception->getMessage()]);
        }

        return redirect()->back()->with(['message' => 'Agent assignment approved and activated.', 'alert-type' => 'success']);
    }

    public function reject(Request $request, string $uuid)
    {
        $profileData = $this->profileData();
        $assignment = $this->assignmentForViewer($profileData, $uuid);
        abort_unless($this->policy->canReject($profileData, $assignment), 403);

        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:5000']]);
        $this->assignments->reject($assignment, $profileData, $data['rejection_reason']);

        return redirect()->back()->with(['message' => 'Agent request rejected.', 'alert-type' => 'success']);
    }

    public function directAssignForm()
    {
        $profileData = $this->profileData();
        abort_unless($this->policy->canDirectAssign($profileData), 403);

        return view('backend.shared.agents.direct-assign', [
            'profileData' => $profileData,
            'users' => $this->userOptions($profileData),
            'states' => $this->stateOptions($profileData),
            'locationForm' => $this->locationForms->context($profileData),
        ]);
    }

    public function directAssignStore(Request $request)
    {
        $profileData = $this->profileData();
        abort_unless($this->policy->canDirectAssign($profileData), 403);

        $data = $this->validateIdentityRequest($request, true, [
            'user_id' => ['required', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'assignment_mode' => ['required', Rule::in(['registered', 'override'])],
            'use_override' => ['nullable'],
            'override_reason' => ['nullable', 'string', 'max:5000'],
        ]);
        $pollingUnit = PollingUnit::findOrFail($data['polling_unit_id']);
        $this->licensedScope->assertPayloadWithinScope(['polling_unit_id' => $pollingUnit->id]);
        abort_unless($this->policy->canDirectAssign($profileData, $pollingUnit), 403);
        $assignedUser = User::findOrFail($data['user_id']);
        $isOverride = $data['assignment_mode'] === 'override';
        if (!$isOverride) {
            abort_unless($assignedUser->polling_unit_id, 422, 'The selected user has no registered polling unit. Use campaign deployment override with a reason.');
            abort_unless((int) $assignedUser->polling_unit_id === (int) $pollingUnit->id, 422, 'Normal assignment must use the selected user registered polling unit.');
        }
        if ($isOverride) {
            abort_unless($request->boolean('use_override') && !empty($data['override_reason']), 422, 'Please provide a campaign deployment override reason.');
        }

        try {
            $assignment = $this->assignments->directAssign(
                $assignedUser,
                $pollingUnit,
                $profileData,
                array_merge($this->identityPayload($request, $data), [
                    'notes' => $data['notes'] ?? null,
                    'override_reason' => $isOverride ? $data['override_reason'] : null,
                ])
            );
        } catch (\RuntimeException $exception) {
            return redirect()->back()->withInput()->withErrors(['polling_unit_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route($profileData->access_level.'.agents.show', $assignment->uuid)
            ->with(['message' => 'Polling Unit Agent assigned and activated.', 'alert-type' => 'success']);
    }

    public function suspend(Request $request, string $uuid)
    {
        $profileData = $this->profileData();
        $assignment = $this->assignmentForViewer($profileData, $uuid);
        abort_unless($this->policy->canSuspendOrRevoke($profileData, $assignment), 403);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:5000']]);
        $this->assignments->suspend($assignment, $profileData, $data['reason'] ?? null);

        return redirect()->back()->with(['message' => 'Agent assignment suspended.', 'alert-type' => 'success']);
    }

    public function revoke(Request $request, string $uuid)
    {
        $profileData = $this->profileData();
        $assignment = $this->assignmentForViewer($profileData, $uuid);
        abort_unless($this->policy->canSuspendOrRevoke($profileData, $assignment), 403);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:5000']]);
        $this->assignments->revoke($assignment, $profileData, $data['reason'] ?? null);

        return redirect()->back()->with(['message' => 'Agent assignment revoked.', 'alert-type' => 'success']);
    }

    public function reactivate(Request $request, string $uuid)
    {
        $profileData = $this->profileData();
        $assignment = $this->assignmentForViewer($profileData, $uuid);
        abort_unless($this->policy->canReactivate($profileData, $assignment), 403);

        $data = $request->validate(['comments' => ['nullable', 'string', 'max:5000']]);
        try {
            $this->assignments->reactivate($assignment, $profileData, $data['comments'] ?? null);
        } catch (\RuntimeException $exception) {
            return redirect()->back()->withErrors(['approval' => $exception->getMessage()]);
        }

        return redirect()->back()->with(['message' => 'Agent assignment reactivated.', 'alert-type' => 'success']);
    }

    public function document(string $uuid, string $type)
    {
        $profileData = $this->profileData();
        $assignment = $this->assignmentForViewer($profileData, $uuid);
        abort_unless($this->policy->canViewDocument($profileData, $assignment), 403);
        abort_unless(in_array($type, ['identity_document', 'voter_evidence_document', 'passport_photo'], true), 404);

        $path = $assignment->{$type};
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Response::file(Storage::disk('local')->path($path));
    }

    public function userLocation(int $user)
    {
        $profileData = $this->profileData();
        $query = User::with(['region', 'state', 'lga', 'ward', 'pollingUnit.ward.localGovernmentArea.state'])
            ->whereKey($user);
        $this->scopeService->applyScope($query, $profileData, 'users', 'users');
        $selectedUser = $query->firstOrFail();
        $pollingUnit = $selectedUser->pollingUnit;
        $ward = $pollingUnit?->ward ?? $selectedUser->ward;
        $lga = $ward?->localGovernmentArea ?? $selectedUser->lga;
        $state = $lga?->state ?? $selectedUser->state;

        return response()->json([
            'user' => [
                'id' => $selectedUser->id,
                'name' => trim($selectedUser->firstname.' '.$selectedUser->lastname),
                'email' => $selectedUser->email,
            ],
            'registered' => [
                'region' => $selectedUser->region?->name,
                'state_id' => $state?->id,
                'state' => $state?->name,
                'lga_id' => $lga?->id,
                'lga' => $lga?->name,
                'ward_id' => $ward?->id,
                'ward' => $ward?->name,
                'polling_unit_id' => $pollingUnit?->id,
                'polling_unit' => $pollingUnit?->name,
                'label' => $pollingUnit
                    ? $pollingUnit->name.' - '.$ward?->name.' / '.$lga?->name.' / '.$state?->name
                    : null,
            ],
        ]);
    }

    public function locationStates()
    {
        return response()->json(['states' => $this->stateOptions($this->profileData())->values()]);
    }

    public function locationLgas(Request $request)
    {
        $data = $request->validate(['state_id' => ['required', 'exists:states,id']]);
        $profileData = $this->profileData();
        $query = LocalGovernmentArea::query()
            ->where('state_id', $data['state_id'])
            ->orderBy('name');
        $this->scopeService->applyScope($query, $profileData, 'local_government_areas', 'local_government_areas');

        return response()->json(['lgas' => $query->get(['id', 'name'])->values()]);
    }

    public function locationWards(Request $request)
    {
        $data = $request->validate(['lga_id' => ['required', 'exists:local_government_areas,id']]);
        $profileData = $this->profileData();
        $query = Ward::query()
            ->where('lga_id', $data['lga_id'])
            ->orderBy('name');
        $this->scopeService->applyScope($query, $profileData, 'wards', 'wards');

        return response()->json(['wards' => $query->get(['id', 'name'])->values()]);
    }

    public function locationPollingUnits(Request $request)
    {
        $data = $request->validate(['ward_id' => ['required', 'exists:wards,id']]);
        $profileData = $this->profileData();
        $query = PollingUnit::query()
            ->where('ward_id', $data['ward_id'])
            ->orderBy('name');
        $this->scopeService->applyScope($query, $profileData, 'polling_units', 'polling_units');

        return response()->json(['pollingUnits' => $query->get(['id', 'name'])->values()]);
    }

    private function profileData(): User
    {
        return User::with(['state', 'lga', 'ward', 'pollingUnit'])->findOrFail(Auth::id());
    }

    private function assignmentForViewer(User $viewer, string $uuid): PollingUnitAgentAssignment
    {
        $assignment = $this->assignments->scopedQuery($viewer)
            ->where('uuid', $uuid)
            ->firstOrFail();

        abort_unless($this->policy->canView($viewer, $assignment), 403);

        return $assignment;
    }

    private function pollingUnitOptions(User $viewer)
    {
        $query = PollingUnit::with('ward.localGovernmentArea.state')->orderBy('name');
        $this->scopeService->applyScope($query, $viewer, 'polling_units', 'polling_units');

        if ($viewer->access_level === 'user' && $viewer->polling_unit_id) {
            $query->where('polling_units.id', $viewer->polling_unit_id);
        }

        return $query->limit(2000)->get();
    }

    private function stateOptions(User $viewer)
    {
        $query = State::query()->orderBy('name');

        match ($this->scopeService->getScopeType($viewer)) {
            LocationScopeService::REGION => $query->where('region_id', $viewer->region_id),
            LocationScopeService::STATE,
            LocationScopeService::SENATORIAL,
            LocationScopeService::FEDERAL,
            LocationScopeService::LGA,
            LocationScopeService::WARD,
            LocationScopeService::POLLING_UNIT => $query->where('id', $viewer->state_id),
            default => null,
        };

        $this->licensedScope->applyToStatesQuery($query);

        return $query->get(['id', 'name']);
    }

    private function userOptions(User $viewer)
    {
        $query = User::with('pollingUnit.ward.localGovernmentArea.state')->orderBy('firstname')->orderBy('lastname');
        $this->scopeService->applyScope($query, $viewer, 'users', 'users');

        return $query->limit(1000)->get();
    }

    private function validateIdentityRequest(Request $request, bool $requireIdentity, array $extraRules = [], bool $requirePollingUnit = true): array
    {
        return $request->validate(array_merge([
            'polling_unit_id' => [$requirePollingUnit ? 'required' : 'nullable', 'exists:polling_units,id'],
            'identity_type' => [$requireIdentity ? 'required' : 'nullable', 'string', 'max:100'],
            'identity_number' => [$requireIdentity ? 'required' : 'nullable', 'string', 'max:100'],
            'identity_document' => [$requireIdentity ? 'required' : 'nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:4096'],
            'voter_evidence_document' => [$requireIdentity ? 'required' : 'nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:4096'],
            'passport_photo' => ['nullable', 'image', 'max:2048'],
            'current_address' => ['nullable', 'string', 'max:255'],
            'availability_confirmed' => ['nullable', Rule::in(['1', 'on', 1, true])],
            'willingness_statement' => ['nullable', 'string', 'max:5000'],
        ], $extraRules));
    }

    private function identityPayload(Request $request, array $data, bool $keepMissingFiles = true): array
    {
        $payload = [
            'identity_type' => $data['identity_type'] ?? null,
            'identity_number' => $data['identity_number'] ?? null,
            'current_address' => $data['current_address'] ?? null,
            'availability_confirmed' => $request->boolean('availability_confirmed'),
            'willingness_statement' => $data['willingness_statement'] ?? null,
            'identity_verification_status' => PollingUnitAgentAssignment::IDENTITY_PENDING,
        ];

        if ($request->hasFile('identity_document')) {
            $payload['identity_document'] = $this->assignments->storeIdentityFile($request->file('identity_document'), 'identity_document');
        } elseif (!$keepMissingFiles) {
            unset($payload['identity_document']);
        }

        if ($request->hasFile('voter_evidence_document')) {
            $payload['voter_evidence_document'] = $this->assignments->storeIdentityFile($request->file('voter_evidence_document'), 'voter_evidence_document');
        } elseif (!$keepMissingFiles) {
            unset($payload['voter_evidence_document']);
        }

        if ($request->hasFile('passport_photo')) {
            $payload['passport_photo'] = $this->assignments->storeIdentityFile($request->file('passport_photo'), 'passport_photo');
        } elseif (!$keepMissingFiles) {
            unset($payload['passport_photo']);
        }

        return $payload;
    }
}
