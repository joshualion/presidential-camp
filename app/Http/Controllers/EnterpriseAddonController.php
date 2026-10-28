<?php

namespace App\Http\Controllers;

use App\Services\Enterprise\EntitlementService;
use Illuminate\Http\Request;

class EnterpriseAddonController extends Controller
{
    public function index(Request $request, EntitlementService $entitlements)
    {
        return response()->view('backend.shared.enterprise-addons', [
            'profileData' => $request->user(), 'pageTitle' => 'Add-ons',
            'features' => config('enterprise.features'), 'entitlement' => $entitlements->cached(),
        ])->header('Cache-Control', 'no-store');
    }

    public function refresh(EntitlementService $entitlements)
    {
        try {
            $entitlements->refresh();
            return back()->with('addon_status', 'Installation add-on status synchronized.');
        } catch (\Throwable) {
            return back()->with('addon_error', 'Add-on status could not be synchronized. Check registration and Portal connectivity, then retry.');
        }
    }
}
