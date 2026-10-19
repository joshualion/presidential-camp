<?php

namespace App\Http\Controllers;

use App\Jobs\SendDirectSmsBatch;
use App\Jobs\SyncDirectSmsBatch;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\SmsDirectBatch;
use App\Models\SmsProviderAccount;
use App\Models\State;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Ward;
use App\Services\Sms\Direct\DirectSmsAccess;
use App\Services\Sms\Direct\DirectSmsProvider;
use App\Services\Sms\Direct\DirectSmsService;
use App\Services\Sms\Direct\SmsMessageEstimate;
use App\Services\Sms\SmsPhoneNormalizer;
use App\Services\Sms\SmsRecipientService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DirectSmsController extends Controller
{
    public function __construct(private DirectSmsAccess $access, private DirectSmsService $sms, private DirectSmsProvider $provider) {}

    private function user(Request $r): User
    {
        $u = $r->user();
        abort_unless($u, 403);
        $this->access->assertEnabled($u);

        return $u;
    }

    private function geographyModels(): array
    {
        return ['region' => Region::class, 'state' => State::class, 'senatorial' => SenatorialDistrict::class, 'federal' => FederalConstituency::class, 'lga' => LocalGovernmentArea::class, 'ward' => Ward::class, 'polling_unit' => PollingUnit::class];
    }

    private function data(User $u): array
    {
        return ['profileData' => $u, 'pageTitle' => 'SMS', 'account' => $this->sms->account($u), 'settings' => SystemSetting::first(), 'levels' => DirectSmsAccess::LEVELS, 'prefix' => $u->access_level.'.direct-sms.'];
    }

    public function index(Request $r)
    {
        $u = $this->user($r);
        $batches = $this->access->batches($u)->with('sender')->withCount(['messages as visible_count' => fn ($q) => $this->access->restrict($q, $u, true)])->latest()->paginate(20);

        return view('backend.sms.direct.index', $this->data($u) + compact('batches'));
    }

    public function settings(Request $r)
    {
        $u = $this->user($r);
        $search = $r->validate(['search' => 'nullable|string|max:100'])['search'] ?? '';
        $admins = $u->access_level === 'superadmin' ? User::whereIn('access_level', DirectSmsAccess::LEVELS)->when($search, fn ($q) => $q->where(fn ($names) => $names->where('firstname', 'like', '%'.$search.'%')->orWhere('lastname', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))->orderBy('firstname')->paginate(10)->withQueryString() : null;
        $grants = $admins ? DB::table('sms_provider_grants')->whereIn('user_id', $admins->pluck('id'))->get()->keyBy('user_id') : collect();
        $boundaries = collect();
        foreach ($grants as $grant) {
            $model = $this->geographyModels()[$grant->audit_scope ?? ''] ?? null;
            if ($model && $grant->audit_scope_id) {
                $boundaries->put($grant->user_id, $model::find($grant->audit_scope_id)?->name);
            }
        }

        return view('backend.sms.direct.settings', $this->data($u) + compact('admins', 'grants', 'boundaries'));
    }

    public function save(Request $r)
    {
        $u = $this->user($r);
        $v = $r->validate(['provider' => ['required', Rule::in(['termii', 'bulksmsnigeria'])], 'base_url' => 'required|string|max:255', 'api_key' => 'nullable|string|min:8|max:1000', 'sender_id' => 'required|regex:/^[A-Za-z0-9 ]{3,11}$/D', 'enabled' => 'nullable|boolean']);
        try {
            $v['base_url'] = $this->provider->validateEndpoint($v['provider'], $v['base_url']);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['base_url' => $e->getMessage()]);
        }
        $current = $this->sms->account($u);
        $secret = $v['api_key'] ?? '';
        if ($secret === '' && (! $current || $current->provider !== $v['provider'] || $current->base_url !== $v['base_url'])) {
            throw ValidationException::withMessages(['api_key' => 'Enter the API key for this provider and endpoint.']);
        }
        // A credential change creates an immutable account revision for queued sends and reports.
        DB::transaction(function () use ($u, $v, $current, $secret) {
            User::whereKey($u->id)->lockForUpdate()->first();
            SmsProviderAccount::where('user_id', $u->id)->update(['is_active' => false]);
            SmsProviderAccount::create(['user_id' => $u->id, 'provider' => $v['provider'], 'base_url' => $v['base_url'], 'sender_id' => $v['sender_id'], 'api_key' => $secret !== '' ? $secret : $current->api_key, 'is_active' => true]);
            $this->sms->event(null, 'provider.settings_saved', $u->id, ['provider' => $v['provider']]);
        });

        return back()->with('message', 'SMS provider settings saved. Your API key is encrypted and hidden.');
    }

    public function policy(Request $r)
    {
        $u = $this->user($r);
        abort_unless($u->access_level === 'superadmin', 403);
        $v = $r->validate(['enabled' => 'nullable|boolean', 'levels' => 'nullable|array', 'levels.*' => [Rule::in(DirectSmsAccess::LEVELS)]]);
        SystemSetting::firstOrCreate([])->update(['direct_sms_enabled' => $r->boolean('enabled'), 'direct_sms_levels' => $v['levels'] ?? []]);
        $this->sms->event(null, 'sms.policy_updated', $u->id, ['enabled' => $r->boolean('enabled'), 'levels' => $v['levels'] ?? []]);

        return back()->with('message', 'SMS access policy saved.');
    }

    public function grant(Request $r, User $admin)
    {
        $u = $this->user($r);
        abort_unless($u->access_level === 'superadmin' && in_array($admin->access_level, DirectSmsAccess::LEVELS, true), 403);
        $v = $r->validate(['enabled' => ['required', Rule::in(['inherit', 'allow', 'deny'])], 'audit_scope' => ['nullable', Rule::in(['own', 'national', 'region', 'state', 'senatorial', 'federal', 'lga', 'ward', 'polling_unit'])], 'audit_scope_id' => 'nullable|integer|min:1']);
        $type = $v['audit_scope'] ?? 'own';
        $id = $v['audit_scope_id'] ?? null;
        if (! in_array($type, ['own', 'national'], true)) {
            $models = $this->geographyModels();
            if (! $id || ! $models[$type]::whereKey($id)->exists()) {
                throw ValidationException::withMessages(['audit_scope_id' => 'Choose an existing oversight boundary.']);
            }
        } else {
            $id = null;
        }
        DB::table('sms_provider_grants')->updateOrInsert(['user_id' => $admin->id], ['enabled' => match ($v['enabled']) {
            'allow' => true,'deny' => false,default => null
        }, 'audit_scope' => $type === 'own' ? null : $type, 'audit_scope_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
        $this->sms->event(null, 'sms.admin_access_updated', $u->id, ['user_id' => $admin->id, 'mode' => $v['enabled'], 'audit_scope' => $type, 'audit_scope_id' => $id]);

        return back()->with('message', 'Administrator SMS access saved. Oversight never expands sending scope.');
    }

    public function boundaries(Request $r)
    {
        $u = $this->user($r);
        abort_unless($u->access_level === 'superadmin', 403);
        $v = $r->validate(['type' => ['required', Rule::in(array_keys($this->geographyModels()))], 'search' => 'nullable|string|max:100']);
        $model = $this->geographyModels()[$v['type']];
        $rows = $model::query()->when(! empty($v['search']), fn ($q) => $q->where('name', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $v['search']).'%'))->orderBy('name')->limit(50)->get();

        return response()->json(['data' => $rows->map(function ($row) use ($v) {
            $parent = match ($v['type']) {
                'state' => $row->region?->name,'senatorial','federal','lga' => $row->state?->name,'ward' => $row->localGovernmentArea?->name,'polling_unit' => $row->ward?->name,default => null
            };

            return ['id' => $row->id, 'name' => $row->name.($parent ? ' · '.$parent : '')];
        })]);
    }

    public function suppression(Request $r)
    {
        $u = $this->user($r);
        $v = $r->validate(['phones' => 'required|string|max:100000', 'action' => ['required', Rule::in(['add', 'remove'])], 'campaign' => 'nullable|boolean']);
        abort_if($r->boolean('campaign') && $u->access_level !== 'superadmin', 403);
        $owner = $r->boolean('campaign') ? 'campaign' : 'user:'.$u->id;
        $prepared = app(SmsPhoneNormalizer::class)->prepare(preg_split('/[\s,;]+/', trim($v['phones']), -1, PREG_SPLIT_NO_EMPTY));
        foreach ($prepared['recipients'] as $row) {
            if ($v['action'] === 'add') {
                DB::table('sms_direct_suppressions')->updateOrInsert(['owner_key' => $owner, 'phone' => $row['phone']], ['created_at' => now(), 'updated_at' => now()]);
            } else {
                DB::table('sms_direct_suppressions')->where('owner_key', $owner)->where('phone', $row['phone'])->delete();
            }
        }$this->sms->event(null, 'sms.suppression_updated', $u->id, ['count' => $prepared['valid'], 'action' => $v['action'], 'campaign' => $r->boolean('campaign')]);

        return back()->with('message', 'Contact suppression list updated.');
    }

    public function test(Request $r)
    {
        $u = $this->user($r);
        $a = $this->sms->account($u);
        abort_unless($a, 422, 'Save your provider account first.');
        $result = $this->provider->connection($a);
        $a->update(['connection_status' => $result['ok'] ? 'connected' : 'failed']);

        return back()->with('message', $result['ok'] ? 'Provider connection verified. No SMS was sent.' : 'Could not verify the provider connection. Check your endpoint, key and provider account.');
    }

    public function compose(Request $r)
    {
        $u = $this->user($r);

        return view('backend.sms.direct.compose', $this->composeData($u));
    }

    private function composeData(User $u): array
    {
        $recipients = app(SmsRecipientService::class);
        $states = $recipients->locationOptions($u, 'states');

        return $this->data($u) + ['sources' => $recipients->sourceLabels(), 'states' => $states];
    }

    public function locations(Request $r, string $type)
    {
        $u = $this->user($r);
        abort_unless(in_array($type, ['states', 'lgas', 'wards', 'polling_units'], true), 404);
        $filters = $r->validate(['state_id' => 'nullable|integer', 'lga_id' => 'nullable|integer', 'ward_id' => 'nullable|integer']);

        return response()->json(['data' => app(SmsRecipientService::class)->locationOptions($u, $type, $filters)->map(fn ($row) => ['id' => $row->id, 'name' => $row->name])->values()]);
    }

    private function selection(Request $r): array
    {
        return $r->validate(['recipient_source' => ['required', Rule::in(['members', 'agents', 'admins', 'manual'])], 'manual_recipients' => 'nullable|string|max:1000000', 'filters' => 'nullable|array', 'filters.state_id' => 'nullable|integer', 'filters.lga_id' => 'nullable|integer', 'filters.ward_id' => 'nullable|integer', 'filters.polling_unit_id' => 'nullable|integer']);
    }

    public function preview(Request $r)
    {
        $u = $this->user($r);
        abort_unless($this->access->canSend($u), 403, 'SMS sending is disabled.');
        $a = $this->sms->account($u);
        abort_unless($a, 422, 'Configure your SMS provider first.');
        $v = $r->validate(['message' => 'required|string|max:1530', 'scheduled_at' => 'nullable|date|after:now']);
        $selection = $this->selection($r);
        $audience = $this->sms->audience($u, $selection);
        $token = (string) Str::uuid();
        $fingerprint = hash('sha256', json_encode($audience['recipients']));
        $r->session()->put('direct_sms.'.$token, ['user_id' => $u->id, 'account_id' => $a->id, 'selection' => $selection, 'message' => $v['message'], 'scheduled_at' => $v['scheduled_at'] ?? null, 'fingerprint' => $fingerprint, 'created_at' => now()->timestamp]);

        return view('backend.sms.direct.compose', $this->composeData($u) + ['confirmation' => ['token' => $token, 'scheduled_at' => $v['scheduled_at'] ?? null, 'message' => $v['message'], 'count' => $audience['valid'], 'invalid' => $audience['invalid'], 'duplicates' => $audience['duplicates'], 'suppressed' => $audience['suppressed'], 'estimate' => SmsMessageEstimate::for($v['message']), 'preview' => array_slice($audience['recipients'], 0, 10)]]);
    }

    public function send(Request $r)
    {
        $u = $this->user($r);
        $r->validate(['confirmation_token' => 'required|uuid', 'send_consent' => 'required|accepted']);
        $token = $r->input('confirmation_token');
        $v = $r->session()->get('direct_sms.'.$token);
        abort_unless($v && $v['user_id'] === $u->id && $v['created_at'] >= now()->subMinutes(30)->timestamp, 422, 'Preview the message again; confirmation expired.');
        $a = $this->sms->account($u);
        abort_unless($a && $a->id === $v['account_id'], 409, 'Provider settings changed. Preview again.');
        $audience = $this->sms->audience($u, $v['selection']);
        abort_unless(hash_equals($v['fingerprint'], hash('sha256', json_encode($audience['recipients']))), 409, 'The audience changed. Preview the current recipients again.');
        if (! empty($v['scheduled_at']) && Carbon::parse($v['scheduled_at'])->isPast()) {
            abort(422, 'Scheduled time passed. Preview again.');
        }
        $batch = $this->sms->create($u, $a, $v['selection'], $v['message'], $token, $v['scheduled_at']);
        $r->session()->forget('direct_sms.'.$token);
        SendDirectSmsBatch::dispatch($batch->id)->onQueue('sms')->delay($batch->scheduled_at ?? now());

        return redirect()->route($u->access_level.'.direct-sms.show', $batch)->with('message', 'SMS queued using your provider account. Accepted does not mean delivered.');
    }

    public function show(Request $r, SmsDirectBatch $batch)
    {
        $u = $this->user($r);
        abort_unless($this->access->batches($u)->whereKey($batch->id)->exists(), 404);
        $messages = $this->access->messages($u)->where('batch_id', $batch->id)->paginate(50);
        $full = $this->access->fullBatch($u, $batch);
        $events = $full ? DB::table('sms_direct_events')->where('batch_id', $batch->id)->latest('id')->limit(30)->get() : collect();

        return view('backend.sms.direct.show', $this->data($u) + compact('batch', 'messages', 'full', 'events'));
    }

    public function sync(Request $r, SmsDirectBatch $batch)
    {
        $u = $this->user($r);
        abort_unless($u->access_level === 'superadmin' || $batch->created_by === $u->id, 403);
        abort_unless($this->access->batches($u)->whereKey($batch->id)->exists(), 404);
        SyncDirectSmsBatch::dispatch($batch->id)->onQueue('sms');

        return back()->with('message', 'Delivery report refresh queued. Only provider-confirmed outcomes are shown as delivered.');
    }
}
