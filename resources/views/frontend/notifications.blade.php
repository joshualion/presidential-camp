@include('frontend.header')
@php
    $pageTitle = 'Notifications';
    $SystemSetting = $SystemSetting ?? \App\Models\SystemSetting::first();
    $brand = $SystemSetting->dark_theme_color ?? '#008751';
@endphp

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Activity Notifications</h4>
        <form method="POST" action="{{ route('notifications.markAllRead') }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-primary">Mark all as read</button>
        </form>
    </div>

    @forelse($notifications as $note)
        <a href="{{ route('notifications.go', $note->id) }}" class="text-decoration-none text-reset">
            <div class="card mb-2 {{ $note->read_at ? '' : 'border-primary' }}">
                <div class="card-body d-flex align-items-start">
                    <img
                        src="{{ $note->actor?->photo ? url('uploads/member_images/'.$note->actor->photo) : url('uploads/no_image.jpg') }}"
                        alt="avatar"
                        class="rounded-circle me-3"
                        width="46"
                        height="46"
                        style="object-fit: cover;"
                    >
                    <div class="flex-fill">
                        <div class="d-flex align-items-center">
                            <strong>{{ trim(($note->actor->firstname ?? '').' '.($note->actor->lastname ?? '')) ?: 'Someone' }}</strong>
                            @if(!$note->read_at)
                                <span class="badge bg-primary ms-2">New</span>
                            @endif
                        </div>
                        <div class="text-muted small mb-1">{{ $note->data['message'] ?? 'New activity' }}</div>
                        <small class="text-muted">{{ $note->created_at->diffForHumans() }}</small>
                    </div>
                </div>
            </div>
        </a>
    @empty
        <div class="alert alert-light">No notifications yet.</div>
    @endforelse

    <div class="mt-3">
        {{ $notifications->links() }}
    </div>
</div>

@include('frontend.footer')
