@php
    $cardGroups = $dashboardMetrics['card_groups'] ?? [];
    $groups = $groups ?? array_keys($cardGroups);
@endphp

<div class="row dashboard-stats-grid align-items-stretch">
    @foreach($groups as $group)
        @php $cards = $cardGroups[$group] ?? []; @endphp
        @foreach($cards as $card)
            @include('backend.shared.dashboard-stat-box', array_merge($card, ['statGroup' => $group]))
        @endforeach
    @endforeach
</div>
