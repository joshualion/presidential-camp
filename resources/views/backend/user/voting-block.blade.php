@extends('backend.template.backend-master')

@section('content')
<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="small-box bg-info"><div class="inner"><h3>{{ $bloc['direct_count'] }}</h3><p>Direct referrals</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="small-box bg-primary"><div class="inner"><h3>{{ $bloc['total_count'] }}</h3><p>Total voting bloc</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="small-box bg-success"><div class="inner"><h3>{{ $bloc['eligible_count'] }}</h3><p>Eligible voters</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="small-box bg-secondary"><div class="inner"><h3>{{ $bloc['active_count'] }}</h3><p>Active accounts</p></div></div>
    </div>
</div>

<div class="card card-primary">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title">My Voting Bloc</h3>
        <a href="{{ route('user.referrals') }}" class="btn btn-sm btn-light ml-auto">View direct referrals</a>
    </div>
    <div class="card-body">
        @if($bloc['truncated'])
            <div class="alert alert-warning">This bloc is larger than the configured reporting limit. The figures below are a partial result.</div>
        @endif

        @if($bloc['levels']->isNotEmpty())
            <p class="text-muted">
                @foreach($bloc['levels'] as $level => $count)
                    <span class="badge badge-info mr-1">Level {{ $level }}: {{ $count }}</span>
                @endforeach
            </p>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Level</th>
                        <th>Member</th>
                        <th>Voting status</th>
                        <th>Location</th>
                        <th>Account status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                        <tr>
                            <td>{{ $member->referral_depth }}</td>
                            <td>{{ trim($member->firstname.' '.$member->lastname) ?: $member->username }}</td>
                            <td>{{ $member->validVoter === 'yes' ? 'Eligible voter' : 'Not marked eligible' }}</td>
                            <td>{{ $member->pollingUnit?->name ?? $member->ward?->name ?? $member->lga?->name ?? $member->state?->name ?? 'Onboarding incomplete' }}</td>
                            <td>{{ ucfirst($member->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Your voting bloc is empty. Share your referral link to invite members.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $members->links() }}
    </div>
</div>
@endsection
