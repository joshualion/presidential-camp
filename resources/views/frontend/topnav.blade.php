<style>

.micro-header {
    position: sticky;
    top: 0;
    z-index: 2000;
    background: {{ $SystemSetting->dark_theme_color ?? '#0f172a' }};
    box-shadow: 0 2px 6px rgba(0,0,0,.12);
    padding: 5px 0;
}

/* Logo */
.micro-logo img {
    height: 10%;
    width: 10%;
    border-radius: 50%;
}

/* Search */
.micro-search {
    max-width: 520px;
    flex: 1 1 auto;
    margin-right: auto;
}

.micro-search form {
    background: #f0f2f5;
    border-radius: 999px;
    padding: 3px 10px;
    display: flex;
    align-items: center;
    gap: 5px;
    width: 100%;
}

.micro-search input {
    border: none;
    outline: none;
    background: transparent;
    width: 100%;
    font-size: 14px;
}

/* Icons */
.micro-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #111;
    position: relative;
}

.micro-icon i {
    font-size: 16px;
}

.micro-icon:hover {
    background: #e4e6eb;
}

/* Badge */
.micro-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #facc15;
    color: #000;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 999px;
}



</style>

@push('scripts')
<script>
(function() {
    const updateBadge = (id, count) => {
        const el = document.getElementById(id);
        if (!el) return;
        if (count > 0) {
            el.textContent = count;
            el.classList.remove('d-none');
        } else {
            el.classList.add('d-none');
        }
    };

    const fetchUnread = () => {
        fetch(@json(route(auth()->user()->access_level.'.messages.unread-count')), { headers: { 'Accept': 'application/json' }})
            .then(res => res.json())
            .then(data => {
                const c = data?.unread || 0;
                updateBadge('desktop-message-badge', c);
                updateBadge('mobile-message-badge', c);
            })
            .catch(() => {});
    };

    // initial fetch and poll using self-hosted-safe interval
    document.addEventListener('DOMContentLoaded', () => {
        const intervals = window.CampaignManager?.community?.intervals || {};
        fetchUnread();
        setInterval(() => {
            if (document.visibilityState === 'hidden') return;
            fetchUnread();
        }, intervals.message_badge || 30000);
    });
})();
</script>
@endpush
@php
    $supportGroupIds = \App\Support\SafeDatabase::hasTable('support_groups') && \App\Support\SafeDatabase::hasTable('user_support_group')
        ? $profileData->supportGroups()->pluck('support_groups.id')->values()->all()
        : [];

    $userScope = [
        'region_id' => $profileData->region_id ?? null,
        'state_id' => $profileData->state_id ?? null,
        'senatorial_district_id' => $profileData->senatorial_district_id ?? null,
        'federal_constituency_id' => $profileData->federal_constituency_id ?? null,
        'lga_id' => $profileData->lga_id ?? null,
        'ward_id' => $profileData->ward_id ?? null,
        'pu_id' => $profileData->polling_unit_id ?? null,
        'support_group_ids' => $supportGroupIds,
    ];
@endphp
<!-- DESKTOP HEADER -->
<div class="micro-header d-none d-lg-block">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between gap-3">

            <!-- LEFT: Logo -->
            <div class="d-flex align-items-center">
                {{-- you add class="micro-logo" to the <a> --}}
                <a href="{{ route('timeline') }}" class="micro-logo" >
                    <img
                        src="{{ !empty($SystemSetting->logo)
                            ? asset('uploads/system_images/'.$SystemSetting->logo)
                            : asset('logo.png') }}"
                        alt="Logo" class="img img-responsive embossed-image" />
                </a>
            </div>

            <!-- CENTER: Search -->
            <div class="micro-search flex-grow-1">
                <div id="search-root" class="w-100">
                    <search-box mode="desktop" placeholder="Search posts, people..."></search-box>
                </div>
            </div>

            <!-- RIGHT: Actions -->
            <div class="micro-actions d-flex align-items-center gap-3">

                <a href="{{ route('timeline') }}" class="micro-icon" title="Home">
                    <i class="fas fa-home"></i>
                </a>

                <div id="activity-bell-desktop">
                    <activity-bell
                        mode="desktop"
                        :user-scope='@json($userScope)'
                        fetch-url="{{ route('notifications.feed') }}"
                        count-url="{{ route('notifications.unread') }}"
                        mark-url="{{ route('notifications.markRead') }}"
                        mark-all-url="{{ route('notifications.markAllRead') }}"
                        bell-color="{{ $SystemSetting->dark_theme_color ?? '#008751' }}"
                    ></activity-bell>
                </div>

                <a href="javascript:void(0)" class="micro-icon position-relative" title="Messages"
                   onclick="window.dispatchEvent(new Event('open-messenger'));">
                    <i class="fas fa-comment"></i>
                    <span id="desktop-message-badge" class="micro-badge bg-danger d-none">0</span>
                </a>

                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="micro-icon border-0" title="Logout">
                        <i class="fas fa-power-off"></i>
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>

<!-- MOBILE HEADER -->
<div class="container d-lg-none mt-3 mb-3">
    @include('frontend.mobile-menu')
</div>
