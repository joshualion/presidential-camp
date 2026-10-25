<?php

namespace App\Http\Controllers;

use App\Models\ElectionIncident;
use App\Models\PictureEvidence;
use App\Models\VideoEvidence;
use App\Models\SystemSetting;
use App\Services\CampaignCapabilityService;
use App\Services\IncidentEvidenceService;
use App\Services\LocationScopeService;
use App\Services\PollingUnitResultPermissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncidentEvidenceController extends Controller
{
    public function show(Request $request, string $kind, int $id, IncidentEvidenceService $service)
    {
        $user = $request->user();
        abort_unless($user && array_key_exists($user->access_level, config('campaign_roles.access_level_labels', [])), 403);
        abort_unless(app(CampaignCapabilityService::class)->allows($user, 'incidents.view'), 403);
        abort_unless(in_array($kind, ['picture', 'video'], true), 404);
        $model = $kind === 'picture' ? PictureEvidence::class : VideoEvidence::class;
        $evidence = $model::findOrFail($id);
        $query = ElectionIncident::whereKey($evidence->election_incident_id);
        if ($user->access_level === 'user') {
            $query->where('agent_id', $user->id);
            app(LocationScopeService::class)->applyScope($query, null, 'election_incidents', 'election_incidents');
            $incident = $query->firstOrFail();
            abort_unless(app(PollingUnitResultPermissionService::class)->userHasApprovedAssignmentForPollingUnit($user, $incident->polling_unit_id), 404);
        } else {
            app(LocationScopeService::class)->applyScope($query, $user, 'election_incidents', 'election_incidents');
            $query->firstOrFail();
        }
        $file = $service->locate($evidence);
        abort_unless($file, 404, 'This evidence file is unavailable. Contact your administrator.');
        $download = $request->boolean('download') || ($kind === 'video' && ! $file['playable']);
        $response = response()->file($file['path'], [
            'Content-Type' => $file['mime'], 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
        $response->setContentDisposition($download ? 'attachment' : 'inline', basename($file['path']));

        return $response;
    }

    public function settings(Request $request, IncidentEvidenceService $service)
    {
        abort_unless($request->user()?->access_level === 'superadmin', 403);

        return view('backend.shared.incident-evidence-settings', [
            'pageTitle' => 'Incident Evidence Settings', 'profileData' => $request->user(), 'rules' => $service->rules(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        abort_unless($request->user()?->access_level === 'superadmin', 403);
        $data = $request->validate([
            'mode' => ['required', Rule::in(['images_and_videos', 'images_only', 'videos_only', 'none'])],
            'max_images' => ['required', 'integer', 'min:1', 'max:10'],
            'max_videos' => ['required', 'integer', 'min:1', 'max:5'],
            'max_files' => ['required', 'integer', 'min:1', 'max:10'],
            'max_image_kb' => ['required', 'integer', 'min:1', 'max:10240'],
            'max_video_kb' => ['required', 'integer', 'min:1', 'max:51200'],
        ]);
        SystemSetting::firstOrFail()->forceFill(['incident_evidence_rules' => json_encode($data, JSON_THROW_ON_ERROR)])->save();

        return back()->with('message', 'Evidence rules saved. Existing evidence remains accessible.');
    }
}
