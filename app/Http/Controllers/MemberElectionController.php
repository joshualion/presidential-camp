<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\PoliticalParty;
use App\Models\PollingUnitResult;
use App\Models\ElectionIncident;
use App\Services\ElectionReportService;
use App\Services\MemberCapabilityService;
use App\Services\MemberElectionParticipationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MemberElectionController extends Controller
{
    public function __construct(private readonly MemberElectionParticipationService $participation)
    {
    }

    public function index(Request $request, ElectionReportService $reports)
    {
        $profileData = $request->user();
        $this->requireCapability($profileData, MemberCapabilityService::ELECTION_REPORTS);
        $elections = $reports->indexRows($profileData);
        $assignments = $this->participation->approvedAssignments($profileData);
        $canSubmit = app(MemberCapabilityService::class)->allows($profileData, MemberCapabilityService::ELECTION_SUBMISSION);
        $pageTitle = 'Elections';

        return view('backend.user.election.index', compact('profileData', 'elections', 'assignments', 'canSubmit', 'pageTitle'));
    }

    public function results(Request $request, string $election, ElectionReportService $reports)
    {
        $profileData = $request->user();
        $this->requireCapability($profileData, MemberCapabilityService::ELECTION_REPORTS);
        $election = Election::query()->where('uuid', $election)->firstOrFail();
        $report = $reports->summary($profileData, $election, []);
        $pageTitle = 'Election Results';

        return view('backend.user.election.results', [
            'profileData' => $profileData,
            'election' => $election,
            'summary' => $report['summary'],
            'summaryStats' => $report['stats'],
            'pageTitle' => $pageTitle,
        ]);
    }

    public function workspace(Request $request)
    {
        $profileData = $request->user();
        $this->requireCapability($profileData, MemberCapabilityService::ELECTION_SUBMISSION);
        $assignments = $this->participation->approvedAssignments($profileData);
        $elections = $this->participation->openElections();
        $resultMap = PollingUnitResult::query()
            ->whereIn('election_id', $elections->pluck('id'))
            ->whereIn('polling_unit_id', $assignments->pluck('polling_unit_id'))
            ->get()
            ->keyBy(fn ($result) => $result->election_id.'-'.$result->polling_unit_id);
        $incidentCounts = ElectionIncident::query()
            ->where('agent_id', $profileData->id)
            ->whereIn('election_id', $elections->pluck('id'))
            ->whereIn('polling_unit_id', $assignments->pluck('polling_unit_id'))
            ->selectRaw('election_id, polling_unit_id, COUNT(*) as aggregate')
            ->groupBy('election_id', 'polling_unit_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->election_id.'-'.$row->polling_unit_id => (int) $row->aggregate]);
        $pageTitle = 'Agent Election Workspace';

        return view('backend.user.election.workspace', compact(
            'profileData', 'assignments', 'elections', 'resultMap', 'incidentCounts', 'pageTitle'
        ));
    }

    public function resultForm(Request $request, string $election, string $assignment)
    {
        [$profileData, $election, $assignment] = $this->submissionContext($request, $election, $assignment);
        $parties = PoliticalParty::query()->orderBy('name')->get();
        $result = $this->participation->result($election, $assignment);
        if ($result && !app(\App\Services\PollingUnitResultPermissionService::class)->canEditPollingUnitResult($profileData, $result)) {
            return redirect()->route('user.elections.results', $election->uuid)
                ->with('message', 'This polling-unit result has already moved into review and can no longer be edited.');
        }
        $existingVotes = $result?->votes->pluck('quantity', 'party_id') ?? collect();
        $pageTitle = 'Submit Polling Unit Result';

        return view('backend.user.election.submit-result', compact(
            'profileData', 'election', 'assignment', 'parties', 'result', 'existingVotes', 'pageTitle'
        ));
    }

    public function submitResult(Request $request, string $election, string $assignment)
    {
        [$profileData, $election, $assignment] = $this->submissionContext($request, $election, $assignment);
        $validated = $request->validate([
            'votes' => ['required', 'array', 'min:1'],
            'votes.*' => ['required', 'integer', 'min:0'],
            'result_sheet' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:4096'],
        ]);
        $partyIds = array_map('intval', array_keys($validated['votes']));
        if (PoliticalParty::query()->whereIn('id', $partyIds)->count() !== count(array_unique($partyIds))) {
            throw ValidationException::withMessages(['votes' => 'One or more selected political parties are invalid.']);
        }

        $this->participation->submitResult(
            $profileData,
            $election,
            $assignment,
            $validated['votes'],
            $request->file('result_sheet')
        );

        return redirect()->route('user.elections.workspace')
            ->with('success', 'Polling-unit result submitted successfully.');
    }

    public function incidentForm(Request $request, string $election, string $assignment)
    {
        [$profileData, $election, $assignment] = $this->submissionContext($request, $election, $assignment);
        $pageTitle = 'Report Election Incident';

        return view('backend.user.election.report-incident', compact('profileData', 'election', 'assignment', 'pageTitle'));
    }

    public function submitIncident(Request $request, string $election, string $assignment)
    {
        [$profileData, $election, $assignment] = $this->submissionContext($request, $election, $assignment);
        $validated = $request->validate([
            'incident_type' => ['required', Rule::in(['violence', 'intimidation', 'equipment_failure', 'late_opening', 'result_dispute', 'other'])],
            'severity' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'remarks' => ['required', 'string', 'max:5000'],
            'pictures' => ['nullable', 'array', 'max:3'],
            'pictures.*' => ['image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $this->participation->reportIncident(
            $profileData,
            $election,
            $assignment,
            $validated,
            $request->file('pictures', [])
        );

        return redirect()->route('user.elections.workspace')
            ->with('success', 'Election incident reported successfully.');
    }

    private function submissionContext(Request $request, string $electionUuid, string $assignmentUuid): array
    {
        $profileData = $request->user();
        $this->requireCapability($profileData, MemberCapabilityService::ELECTION_SUBMISSION);
        $election = Election::query()->where('uuid', $electionUuid)->firstOrFail();
        $assignment = $this->participation->assignment($profileData, $assignmentUuid);
        $this->participation->assertElectionOpen($election);

        return [$profileData, $election, $assignment];
    }

    private function requireCapability(\App\Models\User $user, string $capability): void
    {
        $service = app(MemberCapabilityService::class);
        abort_unless($service->allows($user, $capability), 403, $service->denialMessage($capability));
    }
}
