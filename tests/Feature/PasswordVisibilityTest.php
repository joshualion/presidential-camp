<?php

namespace Tests\Feature;

use App\Models\SMTPSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_smtp_password_is_private_and_blank_input_preserves_it(): void
    {
        config(['campaign.deployment_mode' => 'self_hosted']);
        // Onboarding is covered separately; retain authentication and role checks.
        $this->withoutMiddleware(\App\Http\Middleware\CheckUserOnboarding::class);
        $this->seed([\Database\Seeders\SystemSettingsSeeder::class, \Database\Seeders\SMTPSettingsSeeder::class]);
        $this->actingAs(User::factory()->create(['access_level' => 'superadmin']));
        $smtp = SMTPSetting::firstOrFail();
        $smtp->update(['password' => 'synthetic-smtp-secret']);
        $response = $this->get(route('superadmin.settings.smtp'));
        $this->assertSame(200, $response->status(), 'Redirected to '.$response->headers->get('Location'));
        $response->assertOk()->assertDontSee('synthetic-smtp-secret')->assertSee('cm-password-toggle')->assertSee('Leave blank to keep the current password');
        $this->assertArrayNotHasKey('password', $smtp->toArray());
        $payload = $smtp->only(['id', 'mailer', 'host', 'port', 'username', 'encryption', 'from_address']);
        $this->post(route('superadmin.settings.smtp.update'), $payload + ['password' => ''])->assertRedirect();
        $this->assertSame('synthetic-smtp-secret', $smtp->fresh()->password);
        $this->post(route('superadmin.settings.smtp.update'), $payload + ['password' => 'replacement-synthetic-secret'])->assertRedirect();
        $this->assertSame('replacement-synthetic-secret', $smtp->fresh()->password);
    }
}
