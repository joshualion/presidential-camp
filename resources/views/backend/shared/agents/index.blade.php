@extends('backend.template.backend-master')
@section('content')

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Polling Unit Agent Requests</h3>
                        <div class="card-tools">
                            @if($policy->canNominate($profileData))
                                <a href="{{ route($profileData->access_level.'.agents.nominate') }}" class="btn btn-default btn-sm"><i class="fa fa-user-plus"></i> Nominate Agent</a>
                            @endif
                            @if($policy->canDirectAssign($profileData))
                                <a href="{{ route($profileData->access_level.'.agents.direct-assign') }}" class="btn btn-default btn-sm"><i class="fa fa-check-circle"></i> Direct Assignment</a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row mb-3">
                            <div class="col-md-3">
                                <select name="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    @foreach(['pending', 'approved', 'rejected', 'suspended', 'revoked'] as $status)
                                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="source" class="form-control">
                                    <option value="">All Sources</option>
                                    @foreach(['self_request', 'leader_nomination', 'admin_appointment', 'super_admin_assignment'] as $source)
                                        <option value="{{ $source }}" @selected(request('source') === $source)>{{ ucwords(str_replace('_', ' ', $source)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="identity_status" class="form-control">
                                    <option value="">All Identity Statuses</option>
                                    @foreach(['pending', 'verified', 'rejected'] as $status)
                                        <option value="{{ $status }}" @selected(request('identity_status') === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
                                <a href="{{ route($profileData->access_level.'.agents.index') }}" class="btn btn-default">Reset</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th>S/N</th>
                                    <th>User</th>
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
                                        <td>{{ $assignment->user?->firstname }} {{ $assignment->user?->lastname }}<br><small>{{ $assignment->user?->email }}</small></td>
                                        <td>{{ $assignment->pollingUnit?->name }}<br><small>{{ $assignment->pollingUnit?->ward?->name }} / {{ $assignment->pollingUnit?->ward?->localGovernmentArea?->name }}</small></td>
                                        <td>{{ ucwords(str_replace('_', ' ', $assignment->source)) }}</td>
                                        <td><span class="badge badge-{{ $assignment->status === 'approved' ? 'success' : ($assignment->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($assignment->status) }}</span></td>
                                        <td><span class="badge badge-{{ $assignment->identity_verification_status === 'verified' ? 'success' : ($assignment->identity_verification_status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($assignment->identity_verification_status) }}</span></td>
                                        <td>{{ ucwords(str_replace('_', ' ', $assignment->workflow_stage ?? 'pending')) }}</td>
                                        <td>
                                            <a href="{{ route($profileData->access_level.'.agents.show', $assignment->uuid) }}" class="btn btn-info btn-sm"><i class="fa fa-eye"></i> View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted">No polling unit agent requests found.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $assignments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
