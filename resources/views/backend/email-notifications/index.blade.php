@extends('backend.template.backend-master')

@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Email Notifications</h3>
                <div class="card-tools">
                    <a href="{{ route($profileData->access_level.'.email-notifications.create') }}" class="btn btn-default btn-sm">
                        <i class="fas fa-plus"></i> Compose Notification
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Sender</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Total recipients</th>
                                <th>Sent</th>
                                <th>Failed</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($notifications as $notification)
                                @php
                                    $senderName = trim(($notification->sender?->firstname ?? '').' '.($notification->sender?->lastname ?? ''));
                                    $statusClass = match($notification->status) {
                                        'sent' => 'success',
                                        'failed' => 'danger',
                                        'partially_failed' => 'warning',
                                        'sending' => 'info',
                                        default => 'secondary',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $senderName ?: ($notification->sender?->username ?? 'Deleted user') }}</td>
                                    <td>{{ $notification->subject }}</td>
                                    <td><span class="badge badge-{{ $statusClass }}">{{ str($notification->status)->replace('_', ' ')->title() }}</span></td>
                                    <td>{{ number_format($notification->total_recipients) }}</td>
                                    <td>{{ number_format($notification->sent_count) }}</td>
                                    <td>{{ number_format($notification->failed_count) }}</td>
                                    <td>{{ $notification->created_at?->format('d M Y, H:i') }}</td>
                                    <td>
                                        <a href="{{ route($profileData->access_level.'.email-notifications.show', $notification->uuid) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i> Details
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">No email notifications have been created.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $notifications->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
