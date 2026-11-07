<?php

namespace App\Services\Enterprise;

use App\Models\User;
use App\Services\LocationScopeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampaignFinanceProfileBank
{
    public function snapshot(User $user): array
    {
        $bank = mb_strtolower(trim((string) $user->bank));
        $number = trim((string) $user->bank_account_number);
        $name = trim($user->firstname.' '.$user->lastname);
        if ($user->status !== 'active' || $bank === '' || !preg_match('/^[0-9]{10}$/D', $number)
            || $name === '' || mb_strlen($name) > 160) {
            throw ValidationException::withMessages(['finance' => 'The beneficiary needs an active profile with a name and valid bank details.']);
        }

        $jurisdiction = $user->only(['access_level', 'region_id', 'state_id', 'senatorial_district_id',
            'federal_constituency_id', 'lga_id', 'ward_id', 'polling_unit_id']);
        $details = [$user->id, $bank, $number, $name, $user->finance_bank_revision, $jurisdiction];
        return [
            'bank' => $bank, 'number' => $number, 'name' => $name, 'user_id' => $user->id,
            'fingerprint' => hash_hmac('sha256', json_encode($details, JSON_THROW_ON_ERROR), config('app.key')),
        ];
    }

    public function forRequest(User $actor, int $beneficiaryId, bool $lock = false): ?array
    {
        $binding = DB::table('campaign_finance_profile_beneficiaries')->where('beneficiary_id', $beneficiaryId)->first();
        if (!$binding) {
            return null;
        }
        $scope = app(LocationScopeService::class);
        $profile = $scope->applyScope(User::query(), $actor, 'users')->whereKey($binding->user_id);
        $user = ($lock ? $profile->lockForUpdate() : $profile)->firstOrFail();
        $bank = $this->snapshot($user);
        if (!hash_equals($binding->bank_fingerprint, $bank['fingerprint'])) {
            throw ValidationException::withMessages(['finance' =>
                'The member’s bank details or jurisdiction changed. A new independently approved reimbursement is required.']);
        }
        return $bank;
    }
}
