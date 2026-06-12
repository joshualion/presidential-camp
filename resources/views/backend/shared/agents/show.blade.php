@extends('backend.template.backend-master')
@section('content')

@php($isOwnRequest = (int) $assignment->user_id === (int) $profileData->id)

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Polling Unit Agent Request Details</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr><th>User</th><td>{{ $assignment->user?->firstname }} {{ $assignment->user?->lastname }} ({{ $assignment->user?->email }})</td></tr>
                            <tr><th>Polling Unit</th><td>{{ $assignment->pollingUnit?->name }} - {{ $assignment->pollingUnit?->ward?->name }} / {{ $assignment->pollingUnit?->ward?->localGovernmentArea?->name }}</td></tr>
                            <tr><th>Registered Polling Unit</th><td>{{ $assignment->registeredPollingUnit?->name ?? 'Not provided' }}</td></tr>
                            <tr><th>Assignment Type</th><td>{{ ucwords(str_replace('_', ' ', $assignment->assignment_type ?? 'registered_polling_unit')) }}</td></tr>
                            @if($assignment->assignment_type === \App\Models\PollingUnitAgentAssignment::ASSIGNMENT_DEPLOYMENT_OVERRIDE)
                                <tr><th>Override Reason</th><td>{{ $assignment->override_reason ?? 'No reason provided' }}</td></tr>
                                <tr><th>Override Authorized By</th><td>{{ $assignment->overrideAuthorizedBy?->firstname }} {{ $assignment->overrideAuthorizedBy?->lastname }} {{ $assignment->override_authorized_at ? 'on '.$assignment->override_authorized_at->format('Y-m-d H:i') : '' }}</td></tr>
                            @endif
                            <tr><th>Source</th><td>{{ ucwords(str_replace('_', ' ', $assignment->source)) }}</td></tr>
                            <tr><th>Status</th><td><span class="badge badge-{{ $assignment->status === 'approved' ? 'success' : ($assignment->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($assignment->status) }}</span></td></tr>
                            <tr><th>Workflow Stage</th><td>{{ ucwords(str_replace('_', ' ', $assignment->workflow_stage ?? 'pending')) }}</td></tr>
                            <tr><th>Identity Status</th><td><span class="badge badge-{{ $assignment->identity_verification_status === 'verified' ? 'success' : ($assignment->identity_verification_status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($assignment->identity_verification_status) }}</span></td></tr>
                            <tr><th>ID Type / Number</th><td>{{ $assignment->identity_type ?? 'Not provided' }} / {{ $assignment->identity_number ?? 'Not provided' }}</td></tr>
                            <tr><th>Current Address</th><td>{{ $assignment->current_address ?? 'Not provided' }}</td></tr>
                            <tr><th>Notes</th><td>{{ $assignment->notes ?? $assignment->appointment_note ?? 'No notes' }}</td></tr>
                            @if($assignment->rejection_reason)
                                <tr><th>Rejection Reason</th><td>{{ $assignment->rejection_reason }}</td></tr>
                            @endif
                            @if($assignment->identity_rejection_reason)
                                <tr><th>Identity Rejection Reason</th><td>{{ $assignment->identity_rejection_reason }}</td></tr>
                            @endif
                            <tr>
                                <th>Documents</th>
                                <td>
                                    @if($assignment->identity_document && $policy->canViewDocument($profileData, $assignment))
                                        <a href="{{ route($profileData->access_level.'.agents.document', [$assignment->uuid, 'identity_document']) }}" class="btn btn-default btn-sm" target="_blank"><i class="fa fa-id-card"></i> Identity Document</a>
                                    @endif
                                    @if($assignment->voter_evidence_document && $policy->canViewDocument($profileData, $assignment))
                                        <a href="{{ route($profileData->access_level.'.agents.document', [$assignment->uuid, 'voter_evidence_document']) }}" class="btn btn-default btn-sm" target="_blank"><i class="fa fa-vote-yea"></i> Voter Evidence</a>
                                    @endif
                                    @if($assignment->passport_photo && $policy->canViewDocument($profileData, $assignment))
                                        <a href="{{ route($profileData->access_level.'.agents.document', [$assignment->uuid, 'passport_photo']) }}" class="btn btn-default btn-sm" target="_blank"><i class="fa fa-image"></i> Passport Photo</a>
                                    @endif
                                    @if(!$assignment->identity_document && !$assignment->voter_evidence_document && !$assignment->passport_photo)
                                        <span class="text-muted">No documents uploaded.</span>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        @if($isOwnRequest && $assignment->workflow_stage === \App\Models\PollingUnitAgentAssignment::STAGE_IDENTITY_SUBMISSION)
                            <hr>
                            <h5>Complete Identity Verification</h5>
                            <form action="{{ route($profileData->access_level.'.agents.identity.store', $assignment->uuid) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="polling_unit_id" value="{{ $assignment->polling_unit_id }}">
                                <div class="row">
                                    <div class="col-md-6"><input type="text" name="identity_type" class="form-control mb-2" placeholder="ID Type" required></div>
                                    <div class="col-md-6"><input type="text" name="identity_number" class="form-control mb-2" placeholder="ID Number" required></div>
                                    <div class="col-md-6"><input type="file" name="identity_document" class="form-control mb-2" required></div>
                                    <div class="col-md-6"><input type="file" name="voter_evidence_document" class="form-control mb-2" required></div>
                                    <div class="col-md-6"><input type="file" name="passport_photo" class="form-control mb-2"></div>
                                    <div class="col-md-12"><textarea name="willingness_statement" class="form-control mb-2" placeholder="Willingness statement"></textarea></div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">Submit Identity Details</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="card card-primary">
                    <div class="card-header"><h3 class="card-title">Approval History</h3></div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead><tr><th>Date</th><th>Actor</th><th>Level</th><th>Status</th><th>Comments</th></tr></thead>
                            <tbody>
                            @forelse($assignment->approvals as $approval)
                                <tr>
                                    <td>{{ $approval->created_at?->format('Y-m-d H:i') }}</td>
                                    <td>{{ $approval->approver?->firstname }} {{ $approval->approver?->lastname }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $approval->approval_level ?? '')) }}</td>
                                    <td>{{ ucfirst($approval->status) }}</td>
                                    <td>{{ $approval->comments }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No approval activity yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-primary">
                    <div class="card-header"><h3 class="card-title">Actions</h3></div>
                    <div class="card-body">
                        @if($policy->canVerifyIdentity($profileData, $assignment) && $assignment->identity_verification_status !== 'verified')
                            <form action="{{ route($profileData->access_level.'.agents.identity.verify', $assignment->uuid) }}" method="POST" class="mb-2">
                                @csrf
                                <textarea name="verification_note" class="form-control mb-2" placeholder="Verification note"></textarea>
                                <button type="submit" class="btn btn-success btn-block btn-sm"><i class="fa fa-check"></i> Verify Identity</button>
                            </form>
                            <form action="{{ route($profileData->access_level.'.agents.identity.reject', $assignment->uuid) }}" method="POST" class="mb-2">
                                @csrf
                                <textarea name="identity_rejection_reason" class="form-control mb-2" placeholder="Identity rejection reason" required></textarea>
                                <button type="submit" class="btn btn-danger btn-block btn-sm"><i class="fa fa-times"></i> Reject Identity</button>
                            </form>
                        @endif

                        @if($policy->canApproveCurrentStage($profileData, $assignment))
                            <form action="{{ route($profileData->access_level.'.agents.approve', $assignment->uuid) }}" method="POST" class="mb-2">
                                @csrf
                                <textarea name="comments" class="form-control mb-2" placeholder="Approval comment"></textarea>
                                <button type="submit" class="btn btn-success btn-block btn-sm"><i class="fa fa-check-circle"></i> Approve Current Stage</button>
                            </form>
                        @endif

                        @if($policy->canApproveDirectly($profileData, $assignment) && $assignment->status !== 'approved')
                            <form action="{{ route($profileData->access_level.'.agents.approve-directly', $assignment->uuid) }}" method="POST" class="mb-2">
                                @csrf
                                <textarea name="comments" class="form-control mb-2" placeholder="Direct approval comment"></textarea>
                                <button type="submit" class="btn btn-primary btn-block btn-sm"><i class="fa fa-bolt"></i> Approve Directly</button>
                            </form>
                        @endif

                        @if($policy->canReject($profileData, $assignment) && !in_array($assignment->status, ['approved', 'rejected'], true))
                            <form action="{{ route($profileData->access_level.'.agents.reject', $assignment->uuid) }}" method="POST" class="mb-2">
                                @csrf
                                <textarea name="rejection_reason" class="form-control mb-2" placeholder="Rejection reason" required></textarea>
                                <button type="submit" class="btn btn-danger btn-block btn-sm"><i class="fa fa-ban"></i> Reject Request</button>
                            </form>
                        @endif

                        @if($policy->canSuspendOrRevoke($profileData, $assignment) && $assignment->status === 'approved')
                            <form action="{{ route($profileData->access_level.'.agents.suspend', $assignment->uuid) }}" method="POST" class="mb-2">
                                @csrf
                                <input type="text" name="reason" class="form-control mb-2" placeholder="Suspension reason">
                                <button type="submit" class="btn btn-warning btn-block btn-sm">Suspend</button>
                            </form>
                            <form action="{{ route($profileData->access_level.'.agents.revoke', $assignment->uuid) }}" method="POST" class="mb-2">
                                @csrf
                                <input type="text" name="reason" class="form-control mb-2" placeholder="Revocation reason">
                                <button type="submit" class="btn btn-danger btn-block btn-sm">Revoke</button>
                            </form>
                        @endif

                        @if($policy->canReactivate($profileData, $assignment))
                            <form action="{{ route($profileData->access_level.'.agents.reactivate', $assignment->uuid) }}" method="POST">
                                @csrf
                                <textarea name="comments" class="form-control mb-2" placeholder="Reactivation comment"></textarea>
                                <button type="submit" class="btn btn-success btn-block btn-sm">Reactivate</button>
                            </form>
                        @endif

                        <a href="{{ route($profileData->access_level.'.agents.index') }}" class="btn btn-default btn-block btn-sm mt-2">Back to Agent Requests</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
