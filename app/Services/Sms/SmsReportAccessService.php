<?php

namespace App\Services\Sms;

use App\Models\SmsBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SmsReportAccessService
{
    public function __construct(private SmsAccessService $access) {}

    public function query(User $user): Builder
    {
        $query = SmsBatch::query();
        if (!$this->canViewOrganizationReports($user)) $query->where('created_by', $user->id);
        return $query;
    }

    public function authorize(User $user, SmsBatch $batch): void
    {
        abort_unless($batch->created_by === $user->id || $this->canViewOrganizationReports($user), 403, 'You do not have access to this SMS report.');
    }

    public function canViewOrganizationReports(User $user): bool
    {
        return $user->access_level === 'superadmin' || $this->access->allows($user, 'sms.organization_wallet.view');
    }
}
