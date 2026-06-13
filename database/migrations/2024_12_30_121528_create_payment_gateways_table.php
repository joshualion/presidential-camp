<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentGatewaysTable extends Migration
{
    public function up()
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191)->unique(); // Name of the payment gateway (e.g., "Stripe", "PayPal")   $table->boolean('is_active')->default(true); // Whether the gateway is active or not
                        
            // Flutterwave API keys
            $table->string('flutterwave_live_api_key')->nullable(); // Flutterwave live API key
            $table->string('flutterwave_test_api_key')->nullable(); // Flutterwave test API key

             // USDT TRC20 Wallet Address
             $table->string('usdt_trc20_wallet_address')->nullable(); // USDT TRC20 wallet address


            // Paystack API keys
             $table->string('paystack_live_api_key')->nullable(); // Paystack live API key
             $table->string('paystack_test_api_key')->nullable(); // Paystack test API key

            // // PayPal Client IDs
            // $table->string('paypal_live_client_id')->nullable(); // PayPal live client ID
            // $table->string('paypal_test_client_id')->nullable(); // PayPal test client ID

            $table->unsignedInteger('is_active')->default(1); // Whether the gateway is active or not
            $table->unsignedInteger('sandbox_mode')->default(1); // Whether the gateway is active or not
            $table->json('gateway_specific_settings')->nullable(); // Additional gateway-specific settings
            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('payment_gateways');
    }
}
