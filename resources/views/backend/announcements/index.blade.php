@extends('backend.template.backend-master')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Announcements</h3>
        <div class="card-tools">
            <a <a class="btn btn-dark btn-sm" href="{{ route($profileData->access_level.'.notices.index') }}"><i class="fas fa-eye"></i> View Notice Board</a>
            <a class="btn btn-default btn-sm" href="{{ route($profileData->access_level.'.announcements.create') }}"><i class="fas fa-plus"></i> Create Announcement</a>
        </div>
    </div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Title</th><th>Publisher</th><th>Audience</th><th>Priority</th><th>Status</th><th>Publish / expiry</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($announcements as $announcement)
                        @php
                            $priority = [0 => ['Low', 'secondary'], 1 => ['Normal', 'info'], 2 => ['High', 'warning'], 3 => ['Urgent', 'danger']][$announcement->priority] ?? ['Normal', 'info'];
                            [$status, $statusClass] = !$announcement->is_active
                                ? ['Inactive', 'secondary']
                                : ($announcement->published_at?->isFuture()
                                    ? ['Scheduled', 'info']
                                    : ($announcement->expires_at?->isPast() ? ['Expired', 'dark'] : ['Published', 'success']));
                            $publisher = trim(($announcement->creator?->firstname ?? '').' '.($announcement->creator?->lastname ?? ''));
                        @endphp
                        <tr>
                            <td>{{ $announcement->title }}</td>
                            <td>{{ $publisher ?: ($announcement->creator?->username ?? 'Legacy / system') }}</td>
                            <td>{{ app(\App\Services\AnnouncementAudienceService::class)->scopeLabel($announcement) }}</td>
                            <td><span class="badge badge-{{ $priority[1] }}">{{ $priority[0] }}</span></td>
                            <td><span class="badge badge-{{ $statusClass }}">{{ $status }}</span></td>
                            <td>
                                {{ $announcement->published_at?->format('d M Y, H:i') ?? 'Immediately' }}
                                @if($announcement->expires_at)<br><small>to {{ $announcement->expires_at->format('d M Y, H:i') }}</small>@endif
                            </td>
                            <td class="text-nowrap">
                                <a <a class="btn btn-dark btn-sm" href="{{ route($profileData->access_level.'.announcements.edit', $announcement) }}"><i class="fas fa-edit"></i></a>
                                <form class="d-inline" method="POST" action="{{ route($profileData->access_level.'.announcements.destroy', $announcement) }}" onsubmit="return confirm('Delete this announcement?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No announcements have been created.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $announcements->links() }}
    </div>
</div>
@endsection
