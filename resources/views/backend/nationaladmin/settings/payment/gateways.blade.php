@extends('backend.template.backend-master')
@section('content')


    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-9">
                    @foreach ($gateways as $gateway)
                        <div class="card card-primary @if ($gateway->is_active) collapsed-card @endif">
                            <div class="card-header">
                                <h3 class="card-title">{{ $gateway->name }} Settings</h3>
                                <div class="card-tools">
                                    @if ($gateway->is_active)
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">                                            
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-tool" data-card-widget="remove">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
    
                            <div class="card-body" style="display: block;">
                                <form method="POST" action="{{ route($profileData->access_level.'.settings.paymentgateway.update') }}">
                                    @csrf
                                    @method('POST')
    
                                    <input type="hidden" name="id" value="{{ $gateway->id }}" />
    
                                    <div class="form-group row">
                                        <label for="name" class="col-sm-2 col-form-label">Gateway Name</label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control @error('name') is_invalid @enderror"
                                                name="name" id="name" value="{{ $gateway->name }}" readonly placeholder="Gateway Name">
                                        </div>
                                        @error('name')
                                        <div class="alert alert-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
    
                                    <!-- Flutterwave Settings -->
                                    @if ($gateway->name === 'Flutterwave')
                                        <div class="form-group row">
                                            <label for="flutterwave_live_api_key" class="col-sm-2 col-form-label">Flutterwave Live API Key</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="flutterwave_live_api_key"
                                                    id="flutterwave_live_api_key" value="{{ $gateway->flutterwave_live_api_key }}"
                                                    placeholder="Flutterwave Live API Key">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="flutterwave_test_api_key" class="col-sm-2 col-form-label">Flutterwave Test API Key</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="flutterwave_test_api_key"
                                                    id="flutterwave_test_api_key" value="{{ $gateway->flutterwave_test_api_key }}"
                                                    placeholder="Flutterwave Test API Key">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="is_active" class="col-sm-2 col-form-label">Mode</label>
                                            <div class="col-sm-10">
                                                <div class="form-check">
                                                    @if($gateway->sandbox_mode == 1)
                                                        <input type="checkbox" class="form-check-input" id="sandbox_mode" name="sandbox_mode" value="2">
                                                        <label class="form-check-label" for="sandbox_mode">Enable Live Mode <span class="badge badge-danger text-sm">Test Mode Active </span> </label>
                                                    @elseif($gateway->sandbox_mode == 2)
                                                        <input type="checkbox" class="form-check-input" id="sandbox_mode" name="sandbox_mode" value="1">
                                                        <label class="form-check-label" for="sandbox_mode">Disable Live Mode <span class="badge badge-success text-sm">Live Mode Active </span> </label>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @elseif ($gateway->name === 'USDT-Direct')
                                        <div class="form-group row">
                                            <label for="usdt_trc20_wallet_address" class="col-sm-2 col-form-label">USDT TRC20 Wallet Address</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="usdt_trc20_wallet_address"
                                                    id="usdt_trc20_wallet_address" value="{{ $gateway->usdt_trc20_wallet_address }}"
                                                    placeholder="USDT TRC20 Wallet Address">
                                            </div>
                                        </div>
                                    @elseif ($gateway->name === 'Paystack')
                                        <!-- Paystack Settings -->
                                        <div class="form-group row">
                                            <label for="paystack_live_api_key" class="col-sm-2 col-form-label">Paystack Live API Key</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="paystack_live_api_key"
                                                    id="paystack_live_api_key" value="{{ $gateway->paystack_live_api_key }}"
                                                    placeholder="Paystack Live API Key">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="paystack_test_api_key" class="col-sm-2 col-form-label">Paystack Test API Key</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="paystack_test_api_key"
                                                    id="paystack_test_api_key" value="{{ $gateway->paystack_test_api_key }}"
                                                    placeholder="Paystack Test API Key">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="is_active" class="col-sm-2 col-form-label">Mode</label>
                                            <div class="col-sm-10">
                                                <div class="form-check">
                                                    @if($gateway->sandbox_mode == 1)
                                                        <input type="checkbox" class="form-check-input" id="sandbox_mode" name="sandbox_mode" value="2">
                                                        <label class="form-check-label" for="sandbox_mode">Enable Live Mode <span class="badge badge-danger text-sm">Test Mode Active </span> </label>
                                                    @elseif($gateway->sandbox_mode == 2)
                                                        <input type="checkbox" class="form-check-input" id="sandbox_mode" name="sandbox_mode" value="1">
                                                        <label class="form-check-label" for="sandbox_mode">Disable Live Mode <span class="badge badge-success text-sm">Live Mode Active </span> </label>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
    
                                    <!-- General "Is Active" Radio Buttons -->
                                    <div class="form-group row">
                                        <label class="col-sm-2 col-form-label">Is Active</label>
                                        <div class="col-sm-10">
                                            <div class="form-check">
                                                <input type="radio" class="form-check-input" id="enable_gateway" name="is_active" value="2"
                                                    @if($gateway->is_active == 2) checked @endif>
                                                <label class="form-check-label" for="enable_gateway">Enable Gateway</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" class="form-check-input" id="disable_gateway" name="is_active" value="1"
                                                    @if($gateway->is_active == 1) checked @endif>
                                                <label class="form-check-label" for="disable_gateway">Disable Gateway</label>
                                            </div>
                                        </div>
                                    </div>
    
                                    <!-- Save Button -->
                                    <div class="form-group row">
                                        <div class="col-sm-3 center">
                                            <button type="submit" class="btn btn-primary btn-block btn-group-lg"><i class='fas fa-save' style='font-size:24px;color:white'> Save</i></button>
                                        </div>
                                    </div>
                                </form>
                                <br>
                                <div class="card-footer">
                                    <strong>Note:</strong>
                                    <p class="text-info">Provide the necessary information for configuring the payment gateway.</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    

@endsection