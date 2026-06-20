<?php

namespace App\Http\Controllers;

use App\Services\LocalLicenseService;
use App\Services\PackageScopeService;

class AdminLicenseController extends Controller
{
    public function __construct(
        private LocalLicenseService $licenses,
        private PackageScopeService $scopeService,
    )
    {
    }

    public function show()
    {
        $license = $this->licenses->current();

        return view('backend.license.show', [
            'license' => $license,
            'maskedKey' => $license ? $this->licenses->maskLicenseKey($license->license_key) : null,
            'currentScope' => $this->scopeService->current(),
            'scopeService' => $this->scopeService,
            'pageTitle' => 'License Status',
            'profileData' => auth()->user(),
        ]);
    }
}
