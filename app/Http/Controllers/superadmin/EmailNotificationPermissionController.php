<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\EmailNotificationPermission;
use App\Services\CampaignPackageRoleService;
use App\Services\EmailNotificationPermissionService;
use Illuminate\Http\Request;

class EmailNotificationPermissionController extends Controller
{
    public function edit(Request $request, EmailNotificationPermissionService $permissions)
    {
        return view('backend.superadmin.settings.email-notifications.permissions', [
            'profileData' => $request->user(),
            'settings' => $permissions->settings(),
            'pageTitle' => 'Email Notification Permissions',
        ]);
    }

    public function update(Request $request, CampaignPackageRoleService $packageRoles)
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'array'],
            'enabled.*' => ['string', 'max:50'],
        ]);

        $allowed = array_keys(array_diff_key($packageRoles->allowedAccessLevels(), ['user' => true]));
        $enabled = array_intersect($validated['enabled'] ?? [], $allowed);

        foreach ($allowed as $accessLevel) {
            EmailNotificationPermission::query()->updateOrCreate(
                ['access_level' => $accessLevel],
                [
                    'enabled' => $accessLevel === 'superadmin' || in_array($accessLevel, $enabled, true),
                    'updated_by' => $request->user()->id,
                ]
            );
        }

        return back()->with('success', 'Email notification permissions updated.');
    }
}
