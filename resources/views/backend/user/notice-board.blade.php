@extends('backend.template.backend-master')

@section('content')
@php($noticeRouteName = $profileData->access_level === 'user' ? 'user.notices.show' : $profileData->access_level.'.notices.show')
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">Campaign Notice Board</h3></div>
    <div class="card-body">
        @forelse($announcements as $announcement)
            @php($priorityClass = [0 => 'secondary', 1 => 'info', 2 => 'warning', 3 => 'danger'][$announcement->priority] ?? 'info')
            <article class="card border-{{ $priorityClass }} mb-3">
                <div class="card-header d-flex align-items-center">
                    <h4 class="card-title mb-0 text-white">{{ $announcement->title }}</h4>
                    @if($announcement->priority >= 2)<span class="badge badge-{{ $priorityClass }} ml-auto">{{ $announcement->priority === 3 ? 'Urgent' : 'High priority' }}</span>@endif
                </div>
                <div class="card-body">
                    <p>{{ \Illuminate\Support\Str::limit($announcement->message, 350) }}</p>
                    <a href="{{ route($noticeRouteName, $announcement) }}" class="btn btn-sm btn-primary text-white">Read full notice</a>
                </div>
                <div class="card-footer text-muted">Published {{ $announcement->published_at?->diffForHumans() ?? $announcement->created_at?->diffForHumans() }}</div>
            </article>
        @empty
            <div class="text-center text-muted py-5"><i class="fas fa-bullhorn fa-3x mb-3"></i><p>No active notices for your campaign area.</p></div>
        @endforelse
        {{ $announcements->links() }}
    </div>
</div>
@endsection
