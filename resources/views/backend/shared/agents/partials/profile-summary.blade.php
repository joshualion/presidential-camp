<div class="row">
    <div class="col-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Profile Details</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4"><strong>Name:</strong> {{ $user->firstname }} {{ $user->lastname }}</div>
                    <div class="col-md-4"><strong>Email:</strong> {{ $user->email }}</div>
                    <div class="col-md-4"><strong>Phone:</strong> {{ $user->phone ?? 'Not provided' }}</div>
                    <div class="col-md-4 mt-2"><strong>State:</strong> {{ $user->state?->name ?? 'Not provided' }}</div>
                    <div class="col-md-4 mt-2"><strong>LGA:</strong> {{ $user->lga?->name ?? 'Not provided' }}</div>
                    <div class="col-md-4 mt-2"><strong>Ward:</strong> {{ $user->ward?->name ?? 'Not provided' }}</div>
                    <div class="col-md-4 mt-2"><strong>Polling Unit:</strong> {{ $user->pollingUnit?->name ?? 'Not provided' }}</div>
                    <div class="col-md-4 mt-2"><strong>Access Level:</strong> {{ $user->access_level }}</div>
                </div>
                @if(!$user->polling_unit_id)
                    <div class="alert alert-warning mt-3 mb-0">
                        Your profile does not currently have a polling unit. Please update your profile before requesting Polling Unit Agent status.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
