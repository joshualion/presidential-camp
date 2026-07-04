@extends('backend.template.backend-master')
@section('content')

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">My Polling Unit Agent Status</h3>
                        <div class="card-tools">
                            @if(app(\App\Services\MemberCapabilityService::class)->allows($profileData, \App\Services\MemberCapabilityService::ELECTION_SUBMISSION))
                                <a href="{{ route('user.elections.workspace') }}" class="btn btn-success btn-sm"><i class="fa fa-vote-yea"></i> Election Workspace</a>
                            @endif
                            <a href="{{ route($profileData->access_level.'.agents.request') }}" class="btn btn-default btn-sm"><i class="fa fa-paper-plane"></i> Request Agent Status</a>
                        </div>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Polling Unit</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Identity</th>
                                <th>Stage</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td>{{ $assignments->firstItem() + $loop->index }}</td>
                                    <td>{{ $assignment->pollingUnit?->name }}<br><small>{{ $assignment->pollingUnit?->ward?->name }} / {{ $assignment->pollingUnit?->ward?->localGovernmentArea?->name }}</small></td>
                                    <td>{{ ucwords(str_replace('_', ' ', $assignment->source)) }}</td>
                                    <td><span class="badge badge-{{ $assignment->status === 'approved' ? 'success' : ($assignment->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($assignment->status) }}</span></td>
                                    <td><span class="badge badge-{{ $assignment->identity_verification_status === 'verified' ? 'success' : ($assignment->identity_verification_status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($assignment->identity_verification_status) }}</span></td>
                                    <td>{{ ucwords(str_replace('_', ' ', $assignment->workflow_stage ?? 'pending')) }}</td>
                                    <td><a href="{{ route($profileData->access_level.'.agents.show', $assignment->uuid) }}" class="btn btn-info btn-sm"><i class="fa fa-eye"></i> View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">You do not have any Polling Unit Agent request yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                        {{ $assignments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
