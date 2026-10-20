<?php

use App\Http\Middleware\CheckUserOnboarding;
use App\Http\Middleware\KuyakEyenIno;
use App\Http\Middleware\ValidateLicense;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\CampaignPackageLocationFormService;
use App\Services\MemberEnrollmentSchema;
use App\Services\OfflineRegistrationForm;

beforeEach(function () {
    config(['campaign.deployment_mode' => 'self_hosted']);
    SystemSetting::create(['package' => 'presidential', 'system_name' => 'Test Campaign', 'require_bank_details' => false]);
    $this->mock(CampaignPackageLocationFormService::class, function ($mock) {
        $mock->shouldReceive('context')->andReturn([
            'scope_name' => 'Cross River', 'fixed_labels' => ['state_id' => 'Cross River'],
        ]);
    });
});

test('offline PDF shares profile requirements and includes fixed campaign geography', function () {
    $actor = User::factory()->create(['access_level' => 'superadmin']);
    $form = app(OfflineRegistrationForm::class);
    $data = $form->fields($actor, 'user', ['polling_unit_id' => 'required|exists:polling_units,id']);
    $labels = collect($data['fields'])->pluck(0)->implode("\n");
    expect($labels)->toContain('Phone number', 'Voting state: Cross River (campaign scope)', 'Voting polling unit', 'Voter identification number')
        ->not->toContain('Bank name');
    $required = collect($data['fields'])->filter(fn ($field) => $field[1])->pluck(0);
    expect($required)->toContain('First name', 'Last name', 'Email address', 'Valid voter card? (yes / no)');
    SystemSetting::first()->update(['require_bank_details' => true]);
    $data = $form->fields($actor, 'user', []);
    expect(collect($data['fields'])->pluck(0)->all())->toContain('Bank name', 'Bank account number (10 digits)');
    expect(app(MemberEnrollmentSchema::class)->profileRules($actor)['bank'])->toStartWith('required');
});

test('PDF download uses the existing authorized add route and does not disclose personal data', function () {
    $this->withoutMiddleware([CheckUserOnboarding::class, KuyakEyenIno::class, ValidateLicense::class]);
    $actor = User::factory()->create(['access_level' => 'superadmin', 'status' => 'active']);
    $response = $this->actingAs($actor)->get(route('superadmin.member.add', ['offline_form' => 1]));
    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF-1.4')
        ->toContain('OFFLINE REGISTRATION FORM', 'Cross River', '%%EOF')
        ->not->toContain($actor->email, $actor->password);
});

test('offline generation rejects another access level route and higher enrollment levels', function () {
    $this->withoutMiddleware([CheckUserOnboarding::class, KuyakEyenIno::class, ValidateLicense::class]);
    $actor = User::factory()->create(['access_level' => 'stateadmin', 'status' => 'active']);
    $this->actingAs($actor)->get(route('superadmin.member.add', ['offline_form' => 1]))->assertForbidden();
    $this->get(route('stateadmin.member.add', ['offline_form' => 1, 'enrollment_level' => 'superadmin']))->assertForbidden();
});

test('PDF cross reference offsets remain valid across multiple pages and escaped labels', function () {
    $lines = array_fill(0, 100, ['Name (test) \\ address', 11]);
    $pdf = app(OfflineRegistrationForm::class)->pdf($lines);
    expect($pdf)->toContain('/Count 3', 'Name \\(test\\) \\\\ address');
    preg_match('/startxref\n(\d+)\n/', $pdf, $matches);
    expect(substr($pdf, (int) $matches[1], 4))->toBe('xref');
    preg_match_all('/(\d+) 0 obj\n/', $pdf, $objects, PREG_OFFSET_CAPTURE);
    foreach ($objects[1] as $i => [$number]) {
        $offset = $objects[0][$i][1];
        expect($pdf)->toContain(sprintf('%010d 00000 n', $offset));
    }
});
