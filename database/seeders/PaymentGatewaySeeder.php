<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;



class PaymentGatewaySeeder extends Seeder
{
    public function run() :void
    {
        $now = Carbon::now();

        // Seed Flutterwave payment gateway
        DB::table('payment_gateways')->insert([
            'name' => 'Flutterwave',
            'flutterwave_live_api_key' => 'your_live_api_key',
            'flutterwave_test_api_key' => 'your_test_api_key',
            'usdt_trc20_wallet_address' => '',
            'is_active' => false,
            'sandbox_mode' => true,
            'gateway_specific_settings' => json_encode([]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Seed Direct USDT Payment gateway
        DB::table('payment_gateways')->insert([
            'name' => 'USDT-Direct',
            'usdt_trc20_wallet_address' => 'usdt_trc20_wallet_address',
            'is_active' => false,
            'gateway_specific_settings' => json_encode([]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Seed Paystack payment gateway
        DB::table('payment_gateways')->insert([
            'name' => 'Paystack',
            'paystack_live_api_key' => 'your_live_api_key',
            'paystack_test_api_key' => 'your_test_api_key',
            'usdt_trc20_wallet_address' => '',
            'is_active' => false,
            'sandbox_mode' => true, // You can modify this based on whether the mode is sandbox or live
            'gateway_specific_settings' => json_encode([]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
