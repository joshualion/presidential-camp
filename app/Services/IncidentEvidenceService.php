<?php

namespace App\Services;

use App\Models\ElectionIncident;
use App\Models\PictureEvidence;
use App\Models\VideoEvidence;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class IncidentEvidenceService
{
    public const IMAGE_MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    public const VIDEO_MIMES = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];

    public function rules(): array
    {
        $defaults = config('incident_evidence');
        $stored = Schema::hasColumn('system_settings', 'incident_evidence_rules')
            ? json_decode((string) DB::table('system_settings')->value('incident_evidence_rules'), true) : [];
        $rules = array_merge($defaults, is_array($stored) ? array_intersect_key($stored, $defaults) : []);
        if (! in_array($rules['mode'], ['images_and_videos', 'images_only', 'videos_only', 'none'], true)) { $rules['mode'] = 'none'; }
        foreach (['max_images' => 10, 'max_videos' => 5, 'max_files' => 10, 'max_image_kb' => 10240, 'max_video_kb' => 51200] as $key => $ceiling) {
            $rules[$key] = max(1, min($ceiling, (int) $rules[$key]));
        }

        return $rules;
    }

    public function validate(array $pictures, array $videos): void
    {
        $rules = $this->rules();
        $imagesAllowed = in_array($rules['mode'], ['images_and_videos', 'images_only'], true);
        $videosAllowed = in_array($rules['mode'], ['images_and_videos', 'videos_only'], true);
        Validator::make(['pictures' => $pictures, 'videos' => $videos], [
            'pictures' => [$imagesAllowed ? 'array' : 'prohibited', 'max:'.$rules['max_images']],
            'pictures.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$rules['max_image_kb'], 'dimensions:max_width=16000,max_height=16000'],
            'videos' => [$videosAllowed ? 'array' : 'prohibited', 'max:'.$rules['max_videos']],
            'videos.*' => ['required', 'file', 'mimetypes:video/mp4,video/webm', 'max:'.$rules['max_video_kb']],
        ])->validate();
        if (count($pictures) + count($videos) > $rules['max_files']) {
            throw ValidationException::withMessages(['pictures' => 'The total evidence file count exceeds the configured limit.']);
        }
        foreach (['pictures' => $pictures, 'videos' => $videos] as $kind => $files) {
            $allowed = $kind === 'pictures' ? ['jpg', 'jpeg', 'png', 'webp'] : ['mp4', 'webm'];
            foreach ($files as $file) {
                if (! $file instanceof UploadedFile || ! $file->isValid()
                    || ! in_array(strtolower($file->getClientOriginalExtension()), $allowed, true)) {
                    throw ValidationException::withMessages([$kind => 'The uploaded evidence has an unsupported or unsafe extension.']);
                }
            }
        }
    }

    /** Store media first; a failed write cannot create a successful incident record. */
    public function create(User $actor, array $data, array $pictures = [], array $videos = []): ElectionIncident
    {
        abort_unless(app(CampaignCapabilityService::class)->allows($actor, 'incidents.create'), 403);
        $data = array_intersect_key($data, array_flip(['election_id', 'polling_unit_id', 'incident_type', 'severity', 'remarks', 'status']));
        $election = \App\Models\Election::findOrFail($data['election_id']);
        $pollingUnit = \App\Models\PollingUnit::with('ward.localGovernmentArea.state')->findOrFail($data['polling_unit_id']);
        abort_unless(app(PollingUnitResultPermissionService::class)->userHasApprovedAssignmentForPollingUnit($actor, $pollingUnit->id), 403);
        app(MemberElectionParticipationService::class)->assertElectionOpen($election);
        app(LicensedScopeQueryService::class)->assertPayloadWithinScope(['polling_unit_id' => $pollingUnit->id]);
        $ward = $pollingUnit->ward; $lga = $ward?->localGovernmentArea; $state = $lga?->state;
        abort_unless($ward && $lga && $state, 422, 'The polling-unit geography is incomplete.');
        $result = app(PollingUnitResultPermissionService::class)->activeOfficialResult($election, $pollingUnit->id);
        $data = array_merge($data, [
            'region_id' => $state->region_id, 'state_id' => $state->id, 'lga_id' => $lga->id, 'ward_id' => $ward->id,
            'senatorial_district_id' => $pollingUnit->senatorial_district_id ?: $lga->senatorial_district_id,
            'federal_constituency_id' => $pollingUnit->federal_constituency_id ?: $lga->federal_constituency_id,
            'polling_unit_result_id' => $result?->id,
        ]);
        $this->validate($pictures, $videos);
        $files = [];
        $incident = null;
        try {
            foreach (['pictures' => $pictures, 'videos' => $videos] as $kind => $uploads) {
                foreach ($uploads as $upload) {
                    $mime = $upload->getMimeType();
                    $types = $kind === 'pictures' ? self::IMAGE_MIMES : self::VIDEO_MIMES;
                    if (! isset($types[$mime])) { throw ValidationException::withMessages([$kind => 'Unsupported evidence content.']); }
                    $path = 'evidences/'.$kind.'/'.Str::uuid().'.'.$types[$mime];
                    $stream = fopen($upload->getRealPath(), 'rb');
                    if (! $stream) { throw new RuntimeException('The uploaded evidence could not be read.'); }
                    try {
                        $files[] = ['kind' => $kind, 'file_path' => $path, 'disk' => 'incident_evidence',
                            'mime_type' => $mime, 'size_bytes' => $upload->getSize(),
                            'original_name' => mb_substr(basename(str_replace('\\', '/', $upload->getClientOriginalName())), 0, 255)];
                        if (! Storage::disk('incident_evidence')->put($path, $stream, ['visibility' => 'private'])) {
                            throw new RuntimeException('Evidence upload failed. Please retry.');
                        }
                    } finally { fclose($stream); }
                }
            }
            DB::transaction(function () use (&$incident, $actor, $data, $files) {
                $incident = ElectionIncident::create(array_merge($data, ['agent_id' => $actor->id]));
                foreach ($files as $file) {
                    $kind = $file['kind']; unset($file['kind']);
                    $class = $kind === 'pictures' ? PictureEvidence::class : VideoEvidence::class;
                    $class::create($file + ['election_incident_id' => $incident->id, 'uploaded_by' => $actor->id, 'verification_status' => 'pending']);
                }
            });
        } catch (\Throwable $exception) {
            // Explicit compensation also covers installations with nontransactional legacy tables.
            if ($incident) {
                try {
                PictureEvidence::where('election_incident_id', $incident->id)->delete();
                VideoEvidence::where('election_incident_id', $incident->id)->delete();
                $incident->delete();
                } catch (\Throwable $cleanupException) { report($cleanupException); }
            }
            foreach ($files as $file) {
                try { Storage::disk('incident_evidence')->delete($file['file_path']); } catch (\Throwable) { }
            }
            if ($exception instanceof ValidationException) { throw $exception; }
            report($exception);
            throw ValidationException::withMessages(['pictures' => 'Evidence could not be saved completely. Check the report list before retrying, or contact your administrator.']);
        }

        return $incident;
    }

    /** Only known relative evidence paths and disks are resolvable; never accept a browser path. */
    public function locate(PictureEvidence|VideoEvidence $evidence): ?array
    {
        $path = (string) $evidence->file_path;
        $kind = $evidence instanceof PictureEvidence ? 'pictures' : 'videos';
        if (! preg_match('#^evidences/'.$kind.'/[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp|gif|bmp|mp4|webm|mov)$#D', $path)) { return null; }
        $disks = $evidence->disk ? [$evidence->disk] : ['public'];
        foreach ($disks as $disk) {
            if (! in_array($disk, ['incident_evidence', 'public'], true)) { continue; }
            try {
                $root = realpath(Storage::disk($disk)->path(''));
                $file = realpath(Storage::disk($disk)->path($path));
                if (! $root || ! $file || ! str_starts_with($file, $root.DIRECTORY_SEPARATOR) || ! is_file($file)) { continue; }
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file);
                $mimes = $kind === 'pictures' ? array_merge(array_keys(self::IMAGE_MIMES), ['image/gif', 'image/bmp', 'image/x-ms-bmp']) : array_merge(array_keys(self::VIDEO_MIMES), ['video/quicktime']);
                if (! in_array($mime, $mimes, true)) { return null; }
                return ['path' => $file, 'mime' => $mime, 'playable' => isset(self::VIDEO_MIMES[$mime])];
            } catch (\Throwable) { return null; }
        }

        return null;
    }
}
