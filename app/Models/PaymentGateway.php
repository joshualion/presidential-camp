<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'sandbox_mode',
        'flutterwave_live_api_key',
        'flutterwave_test_api_key',
        'paystack_live_api_key',
        'paystack_test_api_key',
        'usdt_trc20_wallet_address',
    ];
}
