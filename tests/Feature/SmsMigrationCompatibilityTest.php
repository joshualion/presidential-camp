<?php

it('keeps SMS polymorphic indexes within legacy MySQL key limits', function () {
    $migration = file_get_contents(database_path('migrations/2026_07_04_000001_create_portal_sms_core_tables.php'));

    expect($migration)
        ->toContain("string('related_type', 191)")
        ->toContain("string('auditable_type', 191)");
});
