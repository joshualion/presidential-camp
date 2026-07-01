<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailNotificationCampaignJob;
use App\Mail\CampaignEmailNotification;
use App\Models\EmailNotificationCampaign;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\State;
use App\Models\Ward;
use App\Services\EmailNotificationAudienceService;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmailNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = EmailNotificationCampaign::query()
            ->with('sender:id,firstname,lastname,username')
            ->when($user->access_level !== 'superadmin', fn ($query) => $query->where('sender_id', $user->id))
            ->latest()
            ->paginate(20);

        return view('backend.email-notifications.index', [
            'profileData' => $user,
            'notifications' => $notifications,
            'pageTitle' => 'Email Notifications',
        ]);
    }

    public function create(Request $request, EmailNotificationAudienceService $audience)
    {
        return view('backend.email-notifications.create', [
            'profileData' => $request->user(),
            'accessLevels' => $audience->accessLevelsFor($request->user()),
            'roles' => $audience->rolesFor($request->user()),
            'states' => $audience->locationOptions($request->user(), 'states'),
            'sendingScope' => $audience->sendingScopeLabel($request->user()),
            'submissionToken' => (string) Str::uuid(),
            'pageTitle' => 'Compose Email Notification',
        ]);
    }

    public function show(Request $request, string $campaign, EmailNotificationAudienceService $audience)
    {
        $viewer = $request->user();
        $notification = EmailNotificationCampaign::query()
            ->with('sender')
            ->where('uuid', $campaign)
            ->when($viewer->access_level !== 'superadmin', fn ($query) => $query->where('sender_id', $viewer->id))
            ->firstOrFail();

        $recipientStatus = (string) $request->query('recipient_status', 'all');
        if (!in_array($recipientStatus, ['all', 'queued', 'sent', 'failed'], true)) {
            $recipientStatus = 'all';
        }

        $recipients = $notification->recipients()
            ->with([
                'user' => fn ($query) => $query->select(['id', 'firstname', 'lastname', 'username', 'access_level']),
                'user.roles' => fn ($query) => $query->select(['roles.id', 'roles.name']),
            ])
            ->when($recipientStatus === 'queued', fn ($query) => $query->whereIn('status', ['pending', 'processing']))
            ->when(in_array($recipientStatus, ['sent', 'failed'], true), fn ($query) => $query->where('status', $recipientStatus))
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        $sendingScope = 'Sender account unavailable';
        if ($notification->sender) {
            try {
                $sendingScope = $audience->sendingScopeLabel($notification->sender);
            } catch (Throwable) {
                $sendingScope = 'Sender jurisdiction unavailable';
            }
        }

        return view('backend.email-notifications.show', [
            'profileData' => $viewer,
            'pageTitle' => 'Email Notification Report',
            'notification' => $notification,
            'recipients' => $recipients,
            'recipientStatus' => $recipientStatus,
            'sendingScope' => $sendingScope,
            'recipientGroupLabel' => $this->recipientGroupLabel($notification->recipient_group),
            'selectedAccessLevels' => collect($notification->selected_access_levels ?? [])
                ->map(fn (string $level) => config("campaign_roles.access_level_labels.{$level}", $level))
                ->values()
                ->all(),
            'selectedRoles' => $notification->selected_roles ?? [],
            'locationFilters' => $this->locationFilterLabels($notification->audience_filters ?? []),
        ]);
    }

    public function audiencePreview(Request $request, EmailNotificationAudienceService $audience)
    {
        $validated = $this->validateComposition($request, false);
        $count = $audience->recipientCount($request->user(), $validated);

        return response()->json([
            'count' => $count,
            'message' => $count > 0
                ? trans_choice('{1} :count eligible recipient|[2,*] :count eligible recipients', $count, ['count' => number_format($count)])
                : 'No eligible recipients match the selected filters.',
        ]);
    }

    public function preview(Request $request, EmailNotificationAudienceService $audience)
    {
        $validated = $this->validateComposition($request);
        $recipientCount = $audience->recipientCount($request->user(), $validated);
        $summary = $audience->summaryFor($request->user(), $validated);
        $campaign = new EmailNotificationCampaign([
            'subject' => $validated['subject'],
            'title' => $validated['subject'],
            'body' => $validated['body'],
            'cta_label' => $validated['cta_label'] ?? null,
            'cta_url' => $validated['cta_url'] ?? null,
        ]);

        $senderName = trim(($request->user()->firstname ?? '').' '.($request->user()->lastname ?? ''))
            ?: ($request->user()->username ?? 'Administrator');

        return view('backend.email-notifications.preview', [
            'profileData' => $request->user(),
            'pageTitle' => 'Preview Email Notification',
            'composition' => $validated,
            'campaign' => $campaign,
            'branding' => CampaignEmailNotification::branding(),
            'summary' => $summary,
            'senderName' => $senderName,
            'recipientCount' => $recipientCount,
        ]);
    }

    public function backToEdit(Request $request)
    {
        try {
            $this->validateComposition($request);
        } catch (ValidationException $exception) {
            return redirect()->route($this->routeName($request, 'create'))->withErrors($exception->errors());
        }

        return redirect()->route($this->routeName($request, 'create'))->withInput($request->except('_token'));
    }

    public function locationOptions(Request $request, EmailNotificationAudienceService $audience)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['lgas', 'wards', 'polling_units'])],
            'state_id' => ['nullable', 'integer', 'min:1', 'required_if:type,lgas'],
            'lga_id' => ['nullable', 'integer', 'min:1', 'required_if:type,wards'],
            'ward_id' => ['nullable', 'integer', 'min:1', 'required_if:type,polling_units'],
        ]);

        return response()->json([
            'data' => $audience->locationOptions($request->user(), $validated['type'], $validated)->values(),
        ]);
    }

    public function store(Request $request, EmailNotificationAudienceService $audience)
    {
        try {
            $validated = $this->validateComposition($request);
        } catch (ValidationException $exception) {
            return redirect()->route($this->routeName($request, 'create'))
                ->withErrors($exception->errors())
                ->withInput($request->except('_token'));
        }

        $existingCampaign = EmailNotificationCampaign::query()
            ->where('submission_token', $validated['submission_token'])
            ->first();
        if ($existingCampaign) {
            if ((int) $existingCampaign->sender_id !== (int) $request->user()->id) {
                return redirect()->route($this->routeName($request, 'create'))
                    ->withErrors(['submission_token' => 'The submission token is invalid or has already been used.'])
                    ->withInput($request->except('_token', 'submission_token'));
            }

            return redirect()->route($this->routeName($request, 'index'))
                ->with('success', 'This email notification was already queued.');
        }

        try {
            $recipients = $audience->recipients($request->user(), $validated);
        } catch (ValidationException $exception) {
            return redirect()->route($this->routeName($request, 'create'))
                ->withErrors($exception->errors())
                ->withInput($request->except('_token'));
        }

        if ($recipients->isEmpty()) {
            return redirect()->route($this->routeName($request, 'create'))
                ->withErrors(['recipient_group' => 'No eligible recipients match this audience.'])
                ->withInput($request->except('_token'));
        }

        $eligibleRoles = $audience->rolesFor($request->user())->keyBy('id');
        $selectedRoles = collect($validated['role_ids'] ?? [])
            ->map(fn ($id) => $eligibleRoles->get((int) $id)?->name)
            ->filter()
            ->values()
            ->all();

        try {
            $campaign = DB::transaction(function () use ($request, $validated, $recipients, $selectedRoles) {
                $campaign = EmailNotificationCampaign::query()->create([
                    'submission_token' => $validated['submission_token'],
                    'sender_id' => $request->user()->id,
                    'subject' => $validated['subject'],
                    'title' => $validated['subject'],
                    'body' => $validated['body'],
                    'cta_label' => $validated['cta_label'] ?? null,
                    'cta_url' => $validated['cta_url'] ?? null,
                    'recipient_group' => $validated['recipient_group'],
                    'selected_access_levels' => $validated['recipient_group'] === 'access_levels' ? $validated['access_levels'] : null,
                    'selected_roles' => $validated['recipient_group'] === 'roles' ? $selectedRoles : null,
                    'audience_filters' => $validated['audience_filters'],
                    'status' => 'queued',
                    'total_recipients' => $recipients->count(),
                ]);

                $now = now();
                $campaign->recipients()->insert($recipients->map(fn ($recipient) => [
                    'campaign_id' => $campaign->id,
                    'user_id' => $recipient->id,
                    'email' => trim($recipient->email),
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());

                return $campaign;
            });
        } catch (QueryException $exception) {
            $campaign = EmailNotificationCampaign::query()
                ->where('submission_token', $validated['submission_token'])
                ->first();

            if (!$campaign) {
                throw $exception;
            }

            if ((int) $campaign->sender_id !== (int) $request->user()->id) {
                return redirect()->route($this->routeName($request, 'create'))
                    ->withErrors(['submission_token' => 'The submission token is invalid or has already been used.'])
                    ->withInput($request->except('_token', 'submission_token'));
            }

            return redirect()->route($this->routeName($request, 'index'))
                ->with('success', 'This email notification was already queued.');
        }

        SendEmailNotificationCampaignJob::dispatch($campaign->id)->afterCommit();

        return redirect()->route($this->routeName($request, 'index'))
            ->with('success', "Email notification queued for {$campaign->total_recipients} recipients.");
    }

    private function validateComposition(Request $request, bool $includeMessage = true): array
    {
        $rules = [
            'recipient_group' => ['required', Rule::in(['all', 'admins', 'members', 'agents', 'access_levels', 'roles'])],
            'access_levels' => ['nullable', 'array', 'max:20'],
            'access_levels.*' => ['string', 'max:50', 'distinct'],
            'role_ids' => ['nullable', 'array', 'max:100'],
            'role_ids.*' => ['integer', 'min:1', 'distinct'],
            'active_only' => ['nullable', 'boolean'],
            'verified_only' => ['nullable', 'boolean'],
            'state_id' => ['nullable', 'integer', 'min:1'],
            'lga_id' => ['nullable', 'integer', 'min:1'],
            'ward_id' => ['nullable', 'integer', 'min:1'],
            'polling_unit_id' => ['nullable', 'integer', 'min:1'],
        ];

        if ($includeMessage) {
            $rules = array_merge([
                'submission_token' => ['required', 'uuid'],
                'subject' => ['required', 'string', 'max:255', 'not_regex:/[\r\n]/'],
                'body' => ['required', 'string', 'max:50000'],
                'cta_label' => ['nullable', 'string', 'max:100', 'required_with:cta_url'],
                'cta_url' => ['nullable', 'url:http,https', 'max:2048', 'required_with:cta_label'],
            ], $rules);
        }

        $validated = $request->validate($rules);
        $validated['audience_filters'] = [
            'active_only' => $request->boolean('active_only'),
            'verified_only' => $request->boolean('verified_only'),
            'state_id' => $validated['state_id'] ?? null,
            'lga_id' => $validated['lga_id'] ?? null,
            'ward_id' => $validated['ward_id'] ?? null,
            'polling_unit_id' => $validated['polling_unit_id'] ?? null,
        ];

        return $validated;
    }

    private function routeName(Request $request, string $action): string
    {
        return $request->user()->access_level.'.email-notifications.'.$action;
    }

    private function recipientGroupLabel(string $group): string
    {
        return [
            'all' => 'All eligible users',
            'admins' => 'Admins only',
            'members' => 'Members only',
            'agents' => 'Agents only',
            'access_levels' => 'Selected access levels',
            'roles' => 'Selected roles',
        ][$group] ?? str($group)->replace('_', ' ')->title()->toString();
    }

    private function locationFilterLabels(array $filters): array
    {
        return collect([
            'State' => !empty($filters['state_id']) ? State::query()->whereKey($filters['state_id'])->value('name') : null,
            'LGA' => !empty($filters['lga_id']) ? LocalGovernmentArea::query()->whereKey($filters['lga_id'])->value('name') : null,
            'Ward' => !empty($filters['ward_id']) ? Ward::query()->whereKey($filters['ward_id'])->value('name') : null,
            'Polling Unit' => !empty($filters['polling_unit_id']) ? PollingUnit::query()->whereKey($filters['polling_unit_id'])->value('name') : null,
        ])->filter()->all();
    }
}
