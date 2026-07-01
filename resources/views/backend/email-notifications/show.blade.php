@extends('backend.template.backend-master')

@section('content')
@php
    $senderName = trim(($notification->sender?->firstname ?? '').' '.($notification->sender?->lastname ?? ''))
        ?: ($notification->sender?->username ?? 'Deleted user');
    $campaignStatusClass = match($notification->status) {
        'sent' => 'success',
        'failed' => 'danger',
        'partially_failed' => 'warning',
        'sending' => 'info',
        default => 'secondary',
    };
    $reportRoute = $profileData->access_level.'.email-notifications.show';
    $filters = $notification->audience_filters ?? [];
@endphp
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Email Notification Report</h3>
                <div class="card-tools">
                    <a href="{{ route($profileData->access_level.'.email-notifications.index') }}" class="btn btn-default btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Notifications
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="card card-outline card-secondary h-100">
                            <div class="card-header"><h3 class="card-title">Sending summary</h3></div>
                            <div class="card-body p-0">
                                <table class="table table-bordered mb-0">
                                    <tr><th style="width: 34%">Subject</th><td>{{ $notification->subject }}</td></tr>
                                    <tr><th>Title</th><td>{{ $notification->title ?: $notification->subject }}</td></tr>
                                    <tr><th>Sender</th><td>{{ $senderName }}</td></tr>
                                    <tr><th>Sending scope</th><td>{{ $sendingScope }}</td></tr>
                                    <tr><th>Recipient group</th><td>{{ $recipientGroupLabel }}</td></tr>
                                    <tr><th>Selected access levels</th><td>{{ $selectedAccessLevels ? implode(', ', $selectedAccessLevels) : 'Not applied' }}</td></tr>
                                    <tr><th>Selected roles</th><td>{{ $selectedRoles ? implode(', ', $selectedRoles) : 'Not applied' }}</td></tr>
                                    <tr>
                                        <th>Location filters</th>
                                        <td>
                                            @forelse($locationFilters as $type => $name)
                                                <div><strong>{{ $type }}:</strong> {{ $name }}</div>
                                            @empty
                                                All within sender jurisdiction
                                            @endforelse
                                        </td>
                                    </tr>
                                    <tr><th>Additional filters</th><td>Active only: {{ !empty($filters['active_only']) ? 'Yes' : 'No' }}; Verified email only: {{ !empty($filters['verified_only']) ? 'Yes' : 'No' }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 mt-3 mt-lg-0">
                        <div class="card card-outline card-secondary h-100">
                            <div class="card-header"><h3 class="card-title">Delivery totals</h3></div>
                            <div class="card-body p-0">
                                <table class="table table-bordered mb-0">
                                    <tr><th>Status</th><td><span class="badge badge-{{ $campaignStatusClass }}">{{ str($notification->status)->replace('_', ' ')->title() }}</span></td></tr>
                                    <tr><th>Total recipients</th><td>{{ number_format($notification->total_recipients) }}</td></tr>
                                    <tr><th>Sent</th><td class="text-success font-weight-bold">{{ number_format($notification->sent_count) }}</td></tr>
                                    <tr><th>Failed</th><td class="text-danger font-weight-bold">{{ number_format($notification->failed_count) }}</td></tr>
                                    <tr><th>Created</th><td>{{ $notification->created_at?->format('d M Y, H:i') }}</td></tr>
                                    <tr><th>Queued</th><td>{{ $notification->created_at?->format('d M Y, H:i') }}</td></tr>
                                    <tr><th>Sending started</th><td>{{ $notification->started_at?->format('d M Y, H:i') ?? 'Not started' }}</td></tr>
                                    <tr><th>Completed</th><td>{{ $notification->completed_at?->format('d M Y, H:i') ?? 'Not completed' }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Recipient delivery report</h3>
            </div>
            <div class="card-body">
                <div class="btn-group mb-3" role="group" aria-label="Recipient status filter">
                    @foreach(['all' => 'All', 'queued' => 'Queued', 'sent' => 'Sent', 'failed' => 'Failed'] as $value => $label)
                        <a href="{{ route($reportRoute, ['campaign' => $notification->uuid, 'recipient_status' => $value]) }}"
                           class="btn btn-sm {{ $recipientStatus === $value ? 'btn-primary' : 'btn-outline-primary' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Recipient</th>
                                <th>Email</th>
                                <th>Access level / role</th>
                                <th>Status</th>
                                <th>Failure reason</th>
                                <th>Sent</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recipients as $recipient)
                                @php
                                    $recipientName = trim(($recipient->user?->firstname ?? '').' '.($recipient->user?->lastname ?? ''))
                                        ?: ($recipient->user?->username ?? 'Deleted user');
                                    $accessLabel = $recipient->user
                                        ? config('campaign_roles.access_level_labels.'.$recipient->user->access_level, $recipient->user->access_level)
                                        : 'Unavailable';
                                    $roleNames = $recipient->user ? $recipient->user->roles->pluck('name')->all() : [];
                                    $displayStatus = match($recipient->status) {
                                        'pending' => 'Queued',
                                        'processing' => 'Processing',
                                        default => str($recipient->status)->replace('_', ' ')->title()->toString(),
                                    };
                                    $statusClass = match($recipient->status) {
                                        'sent' => 'success',
                                        'failed' => 'danger',
                                        'processing' => 'info',
                                        default => 'secondary',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $recipientName }}</td>
                                    <td>{{ $recipient->email }}</td>
                                    <td>
                                        <div>{{ $accessLabel }}</div>
                                        @if($roleNames)<small class="text-muted">{{ implode(', ', $roleNames) }}</small>@endif
                                    </td>
                                    <td><span class="badge badge-{{ $statusClass }}">{{ $displayStatus }}</span></td>
                                    <td style="max-width: 360px; white-space: normal">{{ $recipient->error_message ?: '—' }}</td>
                                    <td>{{ $recipient->sent_at?->format('d M Y, H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">No recipients match this status filter.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $recipients->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
