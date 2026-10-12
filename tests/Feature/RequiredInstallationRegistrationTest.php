<?php

use App\Services\DeploymentRegistrationService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    File::delete(storage_path('app/private/deployment-registration.json'));
    config(['app.url'=>'https://campaign.example.test','campaign.portal_api_base'=>'https://portal.test/api/v2']);
});
afterEach(fn()=>File::delete(storage_path('app/private/deployment-registration.json')));

function requiredReceipt($request): array {
    return ['registered'=>true,'contact_verified'=>false,'receipt'=>['installation_id'=>$request['deployment_id'],'domain'=>'campaign.example.test','contact_email'=>'admin@example.test','terms_version'=>'2026-10-12','registered_at'=>'2026-10-12T00:00:00Z']];
}

test('portal outage retains encrypted identity for idempotent retry', function () {
    $ids=[];$offline=true;
    Http::fake(function($request)use(&$ids,&$offline){$ids[]=$request['deployment_id'];if($offline){throw new \Illuminate\Http\Client\ConnectionException('Synthetic outage');}return Http::response(requiredReceipt($request),201);});
    $service=app(DeploymentRegistrationService::class);
    expect(fn()=>$service->registerIdentity('admin@example.test',false))->toThrow(\Illuminate\Http\Client\ConnectionException::class);
    expect($service->summary()['registered'])->toBeFalse();$offline=false;
    $service->registerIdentity('admin@example.test',false);
    expect($ids[0])->toBe($ids[1])->and($service->summary()['registered'])->toBeTrue();
    expect(File::get(storage_path('app/private/deployment-registration.json')))->not->toContain('admin@example.test');
    expect(fileperms(storage_path('app/private/deployment-registration.json'))&0777)->toBe(0600);
});

test('success without a matching receipt cannot authorize completion',function(){
    Http::fake(fn()=>Http::response(['registered'=>true],201));$service=app(DeploymentRegistrationService::class);
    expect(fn()=>$service->registerIdentity('admin@example.test',false))->toThrow(RuntimeException::class,'invalid installation receipt');
    expect(fn()=>$service->assertInstallationReceipt('admin@example.test'))->toThrow(RuntimeException::class);
});

test('mismatched receipt fields are rejected',function($field,$value){
    Http::fake(function($request)use($field,$value){$data=requiredReceipt($request);$data['receipt'][$field]=$value;return Http::response($data,201);});
    expect(fn()=>app(DeploymentRegistrationService::class)->registerIdentity('admin@example.test',false))->toThrow(RuntimeException::class);
})->with([['domain','other.example.test'],['contact_email','other@example.test'],['terms_version','old']]);

test('status outage retains accepted receipt and pending status retries',function(){
    $offline=true;Http::fake(function($request)use(&$offline){return str_ends_with($request->url(),'/register')?Http::response(requiredReceipt($request),201):Http::response(['accepted'=>true],$offline?503:200);});
    $service=app(DeploymentRegistrationService::class);$service->registerIdentity('admin@example.test',false);$service->reportInstallationCompleted();
    expect($service->summary()['registered'])->toBeTrue()->and($service->summary()['completion_pending'])->toBeTrue();$service->assertInstallationReceipt('admin@example.test');
    $offline=false;$service->reportInstallationCompleted();expect($service->summary()['completion_pending'])->toBeFalse();
});

test('registration transmits only disclosed fields with optional marketing false',function(){
    Http::fake(fn($request)=>Http::response(requiredReceipt($request),201));app(DeploymentRegistrationService::class)->registerIdentity('admin@example.test',false);
    Http::assertSent(function($request){expect(array_keys($request->data()))->toBe(['deployment_id','token','domain','contact_email','installed_version','installation_status','registration_consent','marketing_opt_in','terms_version']);return $request['installation_status']==='started'&&$request['marketing_opt_in']===false;});
});

test('HTTP and malformed domain configurations do not transmit credentials',function(){
    Http::fake();config(['campaign.portal_api_base'=>'http://portal.test/api/v2']);
    expect(fn()=>app(DeploymentRegistrationService::class)->registerIdentity('admin@example.test',false))->toThrow(RuntimeException::class,'HTTPS');
    config(['campaign.portal_api_base'=>'https://portal.test/api/v2','app.url'=>'https://-bad.example']);
    expect(fn()=>app(DeploymentRegistrationService::class)->registerIdentity('admin@example.test',false))->toThrow(RuntimeException::class);Http::assertNothingSent();
});

test('installer requires explicit terms acceptance',function(){
    $this->markApplicationNotInstalledForTests();
    $this->post(route('install.admin'),['name'=>'Admin','email'=>'admin@example.test','password'=>'Password123!','password_confirmation'=>'Password123!'])->assertSessionHasErrors('terms_accepted');
    expect(File::exists(storage_path('app/private/deployment-registration.json')))->toBeFalse();
});

test('existing installations have no compulsory portal request on ordinary use',function(){
    Http::fake();\App\Models\SystemSetting::query()->create(['package'=>'presidential','system_name'=>'Test Campaign','system_country'=>'Nigeria']);
    $this->actingAs(\App\Models\User::factory()->create(['access_level'=>'superadmin']))->get(route('superadmin.updates'))->assertOk();Http::assertNothingSent();
});
