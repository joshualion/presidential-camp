<?php

namespace App\Services;

use App\Models\AgeGrade;
use App\Models\Religion;
use App\Models\SystemSetting;
use App\Models\SupportGroup;
use App\Models\User;

/**
 * Blank paper enrollment: no member records, credentials or prefilled personal data.
 * Location requirements are supplied by the same controller rules as online creation.
 */
class OfflineRegistrationForm
{
    public function fields(User $actor, string $accessLevel, array $locationRules): array
    {
        $context = app(CampaignPackageLocationFormService::class)->context($actor, null, $accessLevel);
        $labels = [
            'firstname' => 'First name', 'lastname' => 'Last name',
            'email' => 'Email address', 'phone' => 'Phone number',
            'gender' => 'Gender (male / female)',
            'age_grade_id' => 'Age grade: '.AgeGrade::orderBy('id')->pluck('name')->implode(' / '),
            'religion_id' => 'Religion: '.Religion::orderBy('id')->pluck('name')->implode(' / '),
            'validvoter' => 'Valid voter card? (yes / no)',
            'vin' => 'Voter identification number (VIN)',
            'address' => 'Residential address', 'occupation' => 'Occupation',
            'qualification' => 'Qualification', 'photo' => 'Passport photograph (attach separately)',
            'bank' => 'Bank name', 'bank_account_number' => 'Bank account number (10 digits)',
        ];
        $fields = [['Preferred username', true]];
        foreach (app(MemberEnrollmentSchema::class)->profileRules($actor) as $key => $rules) {
            $rules = is_array($rules) ? $rules : explode('|', $rules);
            if (in_array($key, ['bank', 'bank_account_number'], true) && ! in_array('required', $rules, true)) {
                continue;
            }
            $fields[] = [$labels[$key] ?? ucwords(str_replace('_', ' ', $key)), in_array('required', $rules, true)];
        }
        $groups = SupportGroup::orderBy('name')->pluck('name');
        if ($groups->isNotEmpty()) {
            $fields[] = ['Volunteer / support groups (select at least one): '.$groups->implode(' / '), true];
        }
        $locations = [
            'region_id' => 'Voting region', 'state_id' => 'Voting state',
            'senatorial_district_id' => 'Senatorial district',
            'federal_constituency_id' => 'Federal constituency',
            'lga_id' => 'Voting local government', 'ward_id' => 'Voting ward',
            'polling_unit_id' => 'Voting polling unit (name and code)',
        ];
        foreach ($locations as $key => $label) {
            $fixed = $context['fixed_labels'][$key] ?? null;
            $fields[] = [$label.($fixed ? ': '.$fixed.' (campaign scope)' : ''),
                str_starts_with($locationRules[$key] ?? '', 'required'), (bool) $fixed];
        }

        return ['context' => $context, 'fields' => $fields];
    }

    public function download(User $actor, string $accessLevel, array $locationRules)
    {
        $form = $this->fields($actor, $accessLevel, $locationRules);
        $settings = SystemSetting::first();
        $lines = [
            [$settings?->system_name ?: 'Campaign Manager', 17],
            ['OFFLINE REGISTRATION FORM', 14],
            ['Campaign scope: '.($form['context']['scope_name'] ?: 'Configured campaign'), 10],
            ['Enrollment level: '.(app(CampaignPackageRoleService::class)->accessLevelLabels()[$accessLevel] ?? $accessLevel), 10],
            ['Complete one form per person. * Required. Write clearly in BLOCK LETTERS.', 10],
            ['Account password and role are assigned by an authorized administrator in the office.', 10],
            ['Country: '.($settings?->system_country ?: 'Nigeria'), 10],
            ['', 8],
        ];
        foreach ($form['fields'] as $field) {
            [$label, $required] = $field;
            $fixed = $field[2] ?? false;
            $lines[] = [$label.($required ? ' *' : ''), 11];
            $lines[] = [$fixed ? '' : str_repeat('_', 79), 11];
            $lines[] = ['', 7];
        }
        $lines[] = ['I authorize this campaign to record these details for registration and contact.', 10];
        $lines[] = ['Signature / thumbprint: _________________________ Date: __________________', 11];
        $lines[] = ['Collected by: ______________________ Location: ___________________________', 11];
        $lines[] = ['Office use - entered by: _____________________ Entry date: _______________', 11];

        return response($this->pdf($lines), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="offline-registration-form.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Small dependency-free A4 text PDF, with page breaks and escaped PDF strings. */
    public function pdf(array $lines): string
    {
        $pages = [];
        $stream = '';
        $y = 795;
        foreach ($lines as [$text, $size]) {
            $text = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: '';
            foreach (explode("\n", wordwrap($text, $size > 12 ? 60 : 88, "\n", true)) as $line) {
                if ($y < 60) {
                    $pages[] = $stream;
                    $stream = '';
                    $y = 795;
                }
                $line = str_replace(['\\', '(', ')', "\r"], ['\\\\', '\\(', '\\)', ''], $line);
                $stream .= "BT /F1 {$size} Tf 45 {$y} Td ({$line}) Tj ET\n";
                $y -= $size + 7;
            }
        }
        $pages[] = $stream;
        $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>'];
        $kids = [];
        foreach ($pages as $index => $content) {
            $page = 4 + $index * 2;
            $body = $page + 1;
            $kids[] = "{$page} 0 R";
            $objects[$page] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents {$body} 0 R >>";
            $objects[$body] = '<< /Length '.strlen($content)." >>\nstream\n".$content."endstream";
        }
        $objects[2] = '<< /Type /Pages /Count '.count($pages).' /Kids ['.implode(' ', $kids).'] >>';
        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$body}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($objects as $number => $body) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
