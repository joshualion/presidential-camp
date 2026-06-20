<?php

use Illuminate\Support\Facades\URL;

test('backend menu templates do not use root relative internal links', function () {
    $paths = collect(glob(resource_path('views/backend/template/*.blade.php')))
        ->merge(glob(resource_path('views/backend/template/partials/*.blade.php')));

    $pattern = '/href="\/(superadmin|national|regional|state|senatorial|federal|lga|ward|pu|members|locations|elections|messaging|settings|profile|logout|get-roles-by-access-level)\b/';

    foreach ($paths as $path) {
        expect(file_get_contents($path))
            ->not->toMatch($pattern, basename($path).' contains a root-relative internal sidebar link.');
    }
});

test('generated navigation urls respect temporary public smoke test base path', function () {
    URL::forceRootUrl('http://localhost/otu/public');

    expect(route('superadmin.dashboard'))->toBe('http://localhost/otu/public/superadmin/dashboard')
        ->and(url('get-roles-by-access-level'))->toBe('http://localhost/otu/public/get-roles-by-access-level');
});

