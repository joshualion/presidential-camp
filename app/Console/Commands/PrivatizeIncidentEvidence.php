<?php

namespace App\Console\Commands;

use App\Models\PictureEvidence;
use App\Models\VideoEvidence;
use App\Services\IncidentEvidenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Operator-run relocation; dry-run is the default. Never invoked by a page request. */
class PrivatizeIncidentEvidence extends Command
{
    protected $signature = 'campaign:incident-evidence:privatize {--apply : Copy verified files and update metadata} {--remove-public : Also remove verified public originals after all references are private}';
    protected $description = 'Inspect or safely relocate historical incident evidence into private storage';

    public function handle(IncidentEvidenceService $service): int
    {
        if ($this->option('remove-public') && ! $this->option('apply')) {
            $this->error('--remove-public requires --apply.'); return self::FAILURE;
        }
        $copied = 0; $unavailable = 0; $eligible = 0; $failures = 0;
        foreach ([PictureEvidence::class, VideoEvidence::class] as $model) {
            $model::where(fn ($q) => $q->whereNull('disk')->orWhere('disk', 'public')->orWhereNotNull('legacy_public_path'))->orderBy('id')->eachById(function ($evidence) use ($model, $service, &$copied, &$unavailable, &$eligible, &$failures) {
                $oldPath = $evidence->legacy_public_path ?: $evidence->file_path;
                $sourceRecord = clone $evidence;
                $sourceRecord->forceFill(['disk' => 'public', 'file_path' => $oldPath]);
                $file = $service->locate($sourceRecord);
                if (! $file) { $unavailable++; return; }
                $eligible++;
                if (! $this->option('apply')) { return; }
                $alreadyPrivate = $evidence->disk === 'incident_evidence';
                $target = $alreadyPrivate ? $evidence->file_path : 'evidences/'.($evidence instanceof PictureEvidence ? 'pictures' : 'videos').'/'.Str::uuid().'.'.strtolower(pathinfo($oldPath, PATHINFO_EXTENSION));
                $hash = hash_file('sha256', $file['path']);
                try {
                    $written = true;
                    if (! $alreadyPrivate) {
                    $stream = fopen($file['path'], 'rb');
                    if (! $stream) { throw new \RuntimeException('Cannot read evidence.'); }
                    try { $written = Storage::disk('incident_evidence')->put($target, $stream, ['visibility' => 'private']); }
                    finally { fclose($stream); }
                    }
                    $targetFile = Storage::disk('incident_evidence')->path($target);
                    if (! $written || ! is_file($targetFile) || ! hash_equals($hash, hash_file('sha256', $targetFile))) {
                        throw new \RuntimeException('Private copy failed verification.');
                    }
                    $evidence->forceFill(['disk' => 'incident_evidence', 'file_path' => $target,
                        'mime_type' => $file['mime'], 'size_bytes' => filesize($targetFile), 'legacy_public_path' => $oldPath])->save();
                    if (! $alreadyPrivate) { $copied++; }
                    $references = $model::where('file_path', $oldPath)->where(fn ($q) => $q->whereNull('disk')->orWhere('disk', 'public'))->exists();
                    if ($this->option('remove-public') && ! $references && is_file($file['path']) && hash_equals($hash, hash_file('sha256', $file['path']))) {
                        if (! Storage::disk('public')->delete($oldPath)) { $failures++; }
                        else { $evidence->forceFill(['legacy_public_path' => null])->save(); }
                    }
                } catch (\Throwable $exception) {
                    $failures++;
                    // A successful metadata update always keeps its verified private copy.
                    if ($evidence->fresh()?->disk !== 'incident_evidence') {
                        try { Storage::disk('incident_evidence')->delete($target); } catch (\Throwable) { }
                    }
                    report($exception);
                }
            });
        }
        $this->line("Eligible: {$eligible}; copied: {$copied}; unavailable: {$unavailable}; failures: {$failures}.");
        if (! $this->option('apply')) { $this->line('Dry run only. No evidence files or records were changed.'); }

        return $failures ? self::FAILURE : self::SUCCESS;
    }
}
