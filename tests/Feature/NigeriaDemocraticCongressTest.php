<?php

use App\Models\PoliticalParty;
use Database\Seeders\PoliticalPartiesSeeder;

test('NDC is installed with the locally bundled INEC logo', function () {
    $party = PoliticalParty::where('acronym', 'NDC')->firstOrFail();
    expect($party->name)->toBe('Nigeria Democratic Congress');
    expect($party->logo)->toBe('NDC-INEC-2026-10.png');
    $bundled = public_path('assets/img/parties/NDC.png');
    $stored = public_path('uploads/system_images/politicalparty/'.$party->logo);
    expect(is_file($bundled))->toBeTrue()->and(is_file($stored))->toBeTrue();
    expect(getimagesize($bundled)['mime'])->toBe('image/png');
    expect(hash_file('sha256', $stored))->toBe(hash_file('sha256', $bundled));
});

test('repeated default seeding and the NDC upgrade preserve existing party records and logos', function () {
    $party = PoliticalParty::where('acronym', 'NDC')->firstOrFail();
    $party->update(['name' => 'Owner party label', 'logo' => 'owner-logo.png']);
    $this->seed(PoliticalPartiesSeeder::class);
    $this->seed(PoliticalPartiesSeeder::class);
    $migration = require database_path('migrations/2026_10_20_000001_add_nigeria_democratic_congress.php');
    $migration->up();
    expect(PoliticalParty::where('acronym', 'NDC')->count())->toBe(1);
    expect($party->fresh()->name)->toBe('Owner party label')->and($party->fresh()->logo)->toBe('owner-logo.png');
});
