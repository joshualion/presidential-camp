<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignFinanceAccount extends Model
{
    protected $table = 'campaign_finance_accounts';
    protected $guarded = ['id'];
    protected $casts = ['balance_minor' => 'integer'];

    public function pollingUnit() { return $this->belongsTo(PollingUnit::class); }
}
