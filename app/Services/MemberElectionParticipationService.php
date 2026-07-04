<?php

namespace App\Services;

use App\Models\Election;
use App\Models\ElectionIncident;
use App\Models\PictureEvidence;
use App\Models\PollingUnitAgentAssignment;
use App\Models\PollingUnitResult;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class MemberElectionParticipationService
{
    public function __construct(private readonly PollingUnitResultPermissionService $permissions)
    {
    }

    public function approvedAssignments(User $member): Collection
    {
        return PollingUnitAgentAssignment::query()
            ->approved()
            ->where('user_id', $member->id)
            ->with('pollingUnit.ward.localGovernmentArea.state')
            ->orderBy('polling_unit_id')
            ->get();
    }

    public function assignment(User $member, string $uuid): PollingUnitAgentAssignment
    {
        return PollingUnitAgentAssignment::query()
            ->approved()
            ->where('user_id', $member->id)
            ->where('uuid', $uuid)
            ->with('pollingUnit.ward.localGovernmentArea.state')
            ->firstOrFail();
    }

    public function openElections(): Collection
    {
        return Election::query()
            ->with('party')
            ->where(function ($query) {
                $query->where('status', 'ongoing')
                    ->orWhere(function ($today) {
                        $today->whereDate('year', today())
                            ->whereNotIn('status', ['cancelled', 'concluded']);
                    });
            })
            ->orderBy('year')
            ->get();
    }

    public function assertElectionOpen(Election $election): void
    {
        abort_unless(
            $election->status === 'ongoing'
                || ($election->year && Carbon::parse($election->year)->isToday() && !in_array($election->status, ['cancelled', 'concluded'], true)),
            422,
            'This election is not open for polling-unit submissions.'
        );
    }

    public function result(Election $election, PollingUnitAgentAssignment $assignment): ?PollingUnitResult
    {
        return $this->permissions->activeOfficialResult($election, $assignment->polling_unit_id)?->load('votes.politicalParty');
    }

    public function submitResult(
        User $member,
        Election $election,
        PollingUnitAgentAssignment $assignment,
        array $votes,
        ?UploadedFile $resultSheet = null
    ): PollingUnitResult {
        $this->assertElectionOpen($election);
        abort_unless((int) $assignment->user_id === (int) $member->id && $assignment->status === PollingUnitAgentAssignment::STATUS_APPROVED, 403);
        abort_unless($this->permissions->canSubmitPollingUnitResult($member, $assignment->polling_unit_id, $election), 403);

        $result = DB::transaction(function () use ($member, $election, $assignment, $votes) {
            $result = $this->permissions->activeOfficialResultQuery($election->id, $assignment->polling_unit_id)
                ->lockForUpdate()
                ->first();

            if ($result) {
                abort_unless($this->permissions->canEditPollingUnitResult($member, $result), 403);
            }

            $location = $this->locationPayload($assignment);
            $resultLocation = $location;
            unset($resultLocation['region_id']);
            $result ??= PollingUnitResult::query()->create(array_merge($resultLocation, [
                'election_id' => $election->id,
                'polling_unit_id' => $assignment->polling_unit_id,
                'submitted_by' => $member->id,
                'submitted_at' => now(),
                'review_status' => 'pending',
                'verification_status' => 'submitted',
                'dispute_status' => 'normal',
                'result_status' => 'submitted',
            ]));

            $result->forceFill(array_merge($resultLocation, [
                'submitted_by' => $member->id,
                'submitted_at' => now(),
                'review_status' => 'pending',
                'verification_status' => 'submitted',
                'result_status' => 'submitted',
            ]))->save();

            foreach ($votes as $partyId => $quantity) {
                Vote::query()->updateOrCreate([
                    'election_id' => $election->id,
                    'party_id' => (int) $partyId,
                    'polling_unit_id' => $assignment->polling_unit_id,
                    'polling_unit_result_id' => $result->id,
                ], array_merge($location, [
                    'agent_id' => $member->id,
                    'quantity' => (int) $quantity,
                ]));
            }

            Vote::query()
                ->where('polling_unit_result_id', $result->id)
                ->whereNotIn('party_id', array_map('intval', array_keys($votes)))
                ->delete();

            return $result;
        });

        if ($resultSheet) {
            $filename = app(FileStorageService::class)->storeFile(
                $resultSheet,
                'system_images/election_result_sheets',
                $result->result_sheet
            );
            $result->forceFill(['result_sheet' => $filename])->save();
        }

        return $result->fresh(['votes.politicalParty', 'pollingUnit']);
    }

    public function reportIncident(
        User $member,
        Election $election,
        PollingUnitAgentAssignment $assignment,
        array $data,
        array $pictures = []
    ): ElectionIncident {
        $this->assertElectionOpen($election);
        abort_unless((int) $assignment->user_id === (int) $member->id && $assignment->status === PollingUnitAgentAssignment::STATUS_APPROVED, 403);
        abort_unless($this->permissions->userHasApprovedAssignmentForPollingUnit($member, $assignment->polling_unit_id), 403);

        $incident = DB::transaction(function () use ($member, $election, $assignment, $data) {
            return ElectionIncident::query()->create(array_merge($this->locationPayload($assignment), [
                'election_id' => $election->id,
                'polling_unit_id' => $assignment->polling_unit_id,
                'agent_id' => $member->id,
                'incident_type' => $data['incident_type'],
                'severity' => $data['severity'],
                'status' => 'open',
                'remarks' => $data['remarks'],
            ]));
        });

        foreach ($pictures as $picture) {
            $path = $picture->store('evidences/pictures', 'public');
            PictureEvidence::query()->create([
                'election_incident_id' => $incident->id,
                'file_path' => $path,
                'uploaded_by' => $member->id,
                'verification_status' => 'pending',
            ]);
        }

        return $incident;
    }

    private function locationPayload(PollingUnitAgentAssignment $assignment): array
    {
        $assignment->loadMissing('pollingUnit.ward.localGovernmentArea.state');
        $pollingUnit = $assignment->pollingUnit;
        $ward = $pollingUnit?->ward;
        $lga = $ward?->localGovernmentArea;
        $state = $lga?->state;

        abort_unless($pollingUnit && $ward && $lga && $state, 422, 'The assigned polling unit location is incomplete.');

        return [
            'region_id' => $state->region_id,
            'state_id' => $state->id,
            'senatorial_district_id' => $pollingUnit->senatorial_district_id ?: $lga->senatorial_district_id,
            'federal_constituency_id' => $pollingUnit->federal_constituency_id ?: $lga->federal_constituency_id,
            'lga_id' => $lga->id,
            'ward_id' => $ward->id,
        ];
    }
}
