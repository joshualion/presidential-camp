<?php

namespace App\Mail;

use App\Models\EmailNotificationCampaign;
use App\Models\SystemSetting;
use App\Support\SafeDatabase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignEmailNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public EmailNotificationCampaign $campaign, public array $branding)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->campaign->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.campaign-notification');
    }

    public static function branding(): array
    {
        $settings = SafeDatabase::hasTable('system_settings') ? SystemSetting::query()->first() : null;
        $brandColor = trim((string) $settings?->dark_theme_color);
        if (!preg_match('/^#[0-9a-f]{6}$/i', $brandColor)) {
            $brandColor = '#008751';
        }

        $logoFilename = $settings?->logo ? basename((string) $settings->logo) : null;
        $logoPath = $logoFilename ? public_path('uploads/system_images/'.$logoFilename) : null;

        return [
            'name' => $settings?->system_name ?: config('app.name'),
            'color' => $brandColor,
            'logo_url' => $logoPath && is_file($logoPath) ? url('uploads/system_images/'.$logoFilename) : null,
        ];
    }
}
