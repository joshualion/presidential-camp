<?php

require_once __DIR__.'/../Support/V120Fixtures.php';

use App\Models\ElectionIncident;
use App\Models\PictureEvidence;
use App\Models\VideoEvidence;
use App\Models\PollingUnitResult;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\IncidentEvidenceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    SystemSetting::create(['package' => 'presidential', 'system_name' => 'Evidence Test', 'system_country' => 'Nigeria']);
    Storage::fake('incident_evidence'); Storage::fake('public');
    $this->evidenceLocation = v120Location('evidence');
    $this->evidenceAgent = User::factory()->create(array_merge($this->evidenceLocation, ['access_level' => 'puadmin']));
    v120Assignment($this->evidenceAgent, $this->evidenceLocation['polling_unit_id']);
    $this->evidenceElection = v120Election();
    $this->evidencePayload = $this->evidenceLocation + ['election_id' => $this->evidenceElection->id,
        'incident_type' => 'equipment_failure', 'severity' => 'medium', 'remarks' => 'Equipment stopped working.', 'status' => 'open'];
});

test('image evidence is private has disk metadata and is linked to the matching result', function () {
    $location = $this->evidenceLocation; unset($location['country_id'], $location['region_id']);
    $result = PollingUnitResult::create($location + ['election_id' => $this->evidenceElection->id,
        'submitted_by' => $this->evidenceAgent->id, 'result_status' => 'submitted']);
    $incident = app(IncidentEvidenceService::class)->create($this->evidenceAgent, $this->evidencePayload, [UploadedFile::fake()->image('evidence.jpg')]);
    $evidence = $incident->pictureEvidences()->firstOrFail();
    expect($incident->polling_unit_result_id)->toBe($result->id)->and($evidence->disk)->toBe('incident_evidence')
        ->and($evidence->mime_type)->toBe('image/jpeg')->and($evidence->size_bytes)->toBeGreaterThan(0);
    Storage::disk('incident_evidence')->assertExists($evidence->file_path);
    Storage::disk('public')->assertMissing($evidence->file_path);
    $this->actingAs($this->evidenceAgent)->get(route('election.evidence.show', ['kind' => 'picture', 'id' => $evidence->id]))
        ->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('video evidence stores validated MP4 and supports authorized retrieval', function () {
    $video = UploadedFile::fake()->createWithContent('evidence.mp4', base64_decode(file_get_contents(base_path('tests/Fixtures/evidence-video.mp4.base64')), true));
    $incident = app(IncidentEvidenceService::class)->create($this->evidenceAgent, $this->evidencePayload, [], [$video]);
    $evidence = $incident->videoEvidences()->firstOrFail();
    expect($evidence->mime_type)->toBe('video/mp4');
    $this->actingAs($this->evidenceAgent)->get(route('election.evidence.show', ['kind' => 'video', 'id' => $evidence->id]))
        ->assertOk()->assertHeader('Content-Type', 'video/mp4');
});

test('disabled media types reject new files and existing evidence remains accessible', function (string $mode) {
    $service = app(IncidentEvidenceService::class);
    $incident = $service->create($this->evidenceAgent, $this->evidencePayload, [UploadedFile::fake()->image('before.png')]);
    $evidence = $incident->pictureEvidences()->first();
    SystemSetting::first()->forceFill(['incident_evidence_rules' => json_encode(['mode' => $mode])])->save();
    if (in_array($mode, ['none', 'videos_only'], true)) {
        expect(fn () => $service->create($this->evidenceAgent, $this->evidencePayload, [UploadedFile::fake()->image('after.png')]))->toThrow(ValidationException::class);
    }
    $this->actingAs($this->evidenceAgent)->get(route('election.evidence.show', ['kind' => 'picture', 'id' => $evidence->id]))->assertOk();
    $service->create($this->evidenceAgent, $this->evidencePayload);
})->with(['images_only', 'videos_only', 'none']);

test('invalid MIME unsafe extension count and size failures do not create incident records', function (string $case) {
    $files = match ($case) {
        'mime' => [UploadedFile::fake()->createWithContent('photo.jpg', '<?php echo "unsafe";')],
        'extension' => [UploadedFile::fake()->image('photo.php')],
        'svg' => [UploadedFile::fake()->createWithContent('photo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')],
        'count' => array_map(fn ($i) => UploadedFile::fake()->image('photo'.$i.'.jpg'), range(1, 4)),
        'size' => [UploadedFile::fake()->image('photo.jpg')->size(2049)],
    };
    expect(fn () => app(IncidentEvidenceService::class)->create($this->evidenceAgent, $this->evidencePayload, $files))->toThrow(ValidationException::class);
    expect(ElectionIncident::count())->toBe(0)->and(PictureEvidence::count())->toBe(0);
    expect(Storage::disk('incident_evidence')->allFiles())->toBe([]);
})->with(['mime', 'extension', 'svg', 'count', 'size']);

test('private retrieval denies guests and out of scope administrators', function () {
    $incident = app(IncidentEvidenceService::class)->create($this->evidenceAgent, $this->evidencePayload, [UploadedFile::fake()->image('photo.png')]);
    $evidence = $incident->pictureEvidences()->first();
    $url = route('election.evidence.show', ['kind' => 'picture', 'id' => $evidence->id]);
    $this->get($url)->assertRedirect(route('login'));
    $outside = User::factory()->create(array_merge(v120Location('outside'), ['access_level' => 'stateadmin']));
    $this->actingAs($outside)->get($url)->assertNotFound();
    $this->actingAs(User::factory()->create(['access_level' => 'superadmin']))->get($url)->assertOk();
});

test('legacy public-disk evidence is delivered without a public storage symlink and missing files are clear', function () {
    $incident = app(IncidentEvidenceService::class)->create($this->evidenceAgent, $this->evidencePayload);
    $file = UploadedFile::fake()->image('legacy.png');
    Storage::disk('public')->put('evidences/pictures/legacy.png', file_get_contents($file->getRealPath()));
    $evidence = PictureEvidence::create(['election_incident_id' => $incident->id, 'file_path' => 'evidences/pictures/legacy.png', 'uploaded_by' => $this->evidenceAgent->id]);
    $url = route('election.evidence.show', ['kind' => 'picture', 'id' => $evidence->id]);
    $this->actingAs($this->evidenceAgent)->get($url)->assertOk();
    Storage::disk('public')->delete($evidence->file_path);
    $this->get($url)->assertNotFound()->assertSee('unavailable');
    $evidence->update(['file_path' => '../private/deployment-registration.json']);
    $this->get($url)->assertNotFound();
});

test('only Super Admin may change upload rules', function () {
    $payload = ['mode' => 'none', 'max_images' => 3, 'max_videos' => 1, 'max_files' => 4, 'max_image_kb' => 2048, 'max_video_kb' => 10240];
    $this->actingAs($this->evidenceAgent)->put(route('superadmin.incident-evidence.update'), $payload)->assertForbidden();
    $this->actingAs(User::factory()->create(['access_level' => 'superadmin']))->put(route('superadmin.incident-evidence.update'), $payload)->assertRedirect();
    expect(app(IncidentEvidenceService::class)->rules()['mode'])->toBe('none');
});

test('a failed disk write reports failure and leaves no incident or evidence row', function () {
    $disk = Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
    $disk->shouldReceive('put')->once()->andReturn(false);
    $disk->shouldReceive('delete')->once()->andReturn(true);
    Storage::shouldReceive('disk')->with('incident_evidence')->andReturn($disk);
    expect(fn () => app(IncidentEvidenceService::class)->create($this->evidenceAgent, $this->evidencePayload, [UploadedFile::fake()->image('photo.png')]))
        ->toThrow(ValidationException::class);
    expect(ElectionIncident::count())->toBe(0)->and(PictureEvidence::count())->toBe(0);
});

test('legacy relocation is dry by default and verifies private copies before removing public originals', function () {
    $incident = app(IncidentEvidenceService::class)->create($this->evidenceAgent, $this->evidencePayload);
    $image = UploadedFile::fake()->image('legacy.png');
    $path = 'evidences/pictures/relocation.png';
    Storage::disk('public')->put($path, file_get_contents($image->getRealPath()));
    $evidence = PictureEvidence::create(['election_incident_id' => $incident->id, 'file_path' => $path, 'uploaded_by' => $this->evidenceAgent->id]);
    $this->artisan('campaign:incident-evidence:privatize')->assertSuccessful();
    expect($evidence->fresh()->disk)->toBeNull(); Storage::disk('public')->assertExists($path);
    $this->artisan('campaign:incident-evidence:privatize', ['--apply' => true])->assertSuccessful();
    $evidence = $evidence->fresh();
    expect($evidence->disk)->toBe('incident_evidence')->and($evidence->legacy_public_path)->toBe($path);
    Storage::disk('public')->assertExists($path); Storage::disk('incident_evidence')->assertExists($evidence->file_path);
    $this->artisan('campaign:incident-evidence:privatize', ['--apply' => true, '--remove-public' => true])->assertSuccessful();
    Storage::disk('public')->assertMissing($path);
    expect($evidence->fresh()->legacy_public_path)->toBeNull()->and(ElectionIncident::count())->toBe(1);
});

test('result submission without an incident keeps the result and creates no incident', function () {
    $member = $this->evidenceAgent;
    $assignment = $member->pollingUnitAgentAssignments()->firstOrFail();
    $party = $this->evidenceElection->party_id;
    $result = app(\App\Services\MemberElectionParticipationService::class)->submitResult($member, $this->evidenceElection, $assignment, [$party => 12]);
    expect($result->votes()->count())->toBe(1)->and(ElectionIncident::count())->toBe(0);
});

test('result creation does not implicitly grant result amendment', function () {
    $member = $this->evidenceAgent;
    $member->forceFill(['capabilities_managed' => true])->save();
    $member->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('election.results.create', 'web'));
    $assignment = $member->pollingUnitAgentAssignments()->firstOrFail();
    $service = app(\App\Services\MemberElectionParticipationService::class);
    $result = $service->submitResult($member, $this->evidenceElection, $assignment, [$this->evidenceElection->party_id => 12]);
    expect(fn () => $service->submitResult($member, $this->evidenceElection, $assignment, [$this->evidenceElection->party_id => 20]))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    expect((int) $result->votes()->first()->quantity)->toBe(12);
});

test('images-only policy rejects video evidence and total count limits remain independent', function () {
    $service = app(IncidentEvidenceService::class);
    SystemSetting::first()->forceFill(['incident_evidence_rules' => json_encode(['mode' => 'images_only'])])->save();
    $video = UploadedFile::fake()->createWithContent('evidence.mp4', base64_decode(file_get_contents(base_path('tests/Fixtures/evidence-video.mp4.base64')), true));
    expect(fn () => $service->validate([], [$video]))->toThrow(ValidationException::class);
    SystemSetting::first()->forceFill(['incident_evidence_rules' => json_encode(['mode' => 'images_and_videos', 'max_files' => 1])])->save();
    expect(fn () => $service->validate([UploadedFile::fake()->image('a.png'), UploadedFile::fake()->image('b.png')], []))
        ->toThrow(ValidationException::class);
});
