@extends('backend.template.backend-master')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Direct Referrals</h3>
    </div>
    <div class="card-body">
        <p class="text-muted">People who registered directly through your referral link.</p>

        <div class="input-group mb-4">
            <input id="referral-link" class="form-control" value="{{ $user->referral_link }}" readonly>
            <div class="input-group-append">
                <button type="button" class="btn btn-primary" id="copy-referral-link">Copy link</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Voting status</th>
                        <th>Location</th>
                        <th>Account status</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($referrals as $referral)
                        <tr>
                            <td>{{ trim($referral->firstname.' '.$referral->lastname) ?: $referral->username }}</td>
                            <td>{{ $referral->validVoter === 'yes' ? 'Eligible voter' : 'Not marked eligible' }}</td>
                            <td>{{ $referral->pollingUnit?->name ?? $referral->ward?->name ?? $referral->lga?->name ?? $referral->state?->name ?? 'Onboarding incomplete' }}</td>
                            <td>{{ ucfirst($referral->status) }}</td>
                            <td>{{ $referral->created_at?->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No direct referrals yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $referrals->links() }}
    </div>
</div>

<script>
document.getElementById('copy-referral-link')?.addEventListener('click', async function () {
    const input = document.getElementById('referral-link');
    try {
        await navigator.clipboard.writeText(input.value);
        this.textContent = 'Copied';
    } catch (error) {
        input.select();
        document.execCommand('copy');
        this.textContent = 'Copied';
    }
});
</script>
@endsection
