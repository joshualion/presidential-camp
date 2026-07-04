@extends('backend.template.backend-master')

@section('content')
@php($priorityClass = [0 => 'secondary', 1 => 'info', 2 => 'warning', 3 => 'danger'][$announcement->priority] ?? 'info')
@php($noticeIndexRoute = $profileData->access_level === 'user' ? 'user.notices' : $profileData->access_level.'.notices.index')
<article class="card border-{{ $priorityClass }}">
    <div class="card-header">
        <h3 class="card-title">{{ $announcement->title }}</h3>
        @if($announcement->priority >= 2)<span class="badge badge-{{ $priorityClass }} float-right">{{ $announcement->priority === 3 ? 'Urgent' : 'High priority' }}</span>@endif
    </div>
    <div class="card-body"><div class="notice-message">{!! nl2br(e($announcement->message)) !!}</div></div>
    <div class="card-footer d-flex align-items-center">
        <span class="text-muted">Published {{ $announcement->published_at?->format('d M Y, H:i') ?? $announcement->created_at?->format('d M Y, H:i') }}</span>
        <a href="{{ route($noticeIndexRoute) }}" class="btn btn-default ml-auto">Back to Notice Board</a>
    </div>
</article>
@endsection
