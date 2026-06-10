@php
    $drilldowns = $dashboardMetrics['drilldowns'] ?? [];
    $sections = [
        'state_to_senatorial' => 'State → Senatorial District',
        'state_to_federal' => 'State → Federal Constituency',
        'senatorial_to_lga' => 'Senatorial District → LGA',
        'federal_to_lga' => 'Federal Constituency → LGA',
    ];
    $hasActionableDrilldowns = collect($drilldowns)
        ->flatten(1)
        ->contains(fn ($item) => !empty($item['url']));
@endphp

@if($hasActionableDrilldowns)
<div class="card card-secondary collapsed-card">
    <div class="card-header">
        <h3 class="card-title">Dashboard Drill-Down Navigation</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>
    <div class="card-body" style="display: none;">
        <div class="row">
            @foreach($sections as $key => $title)
                @php
                    $items = collect($drilldowns[$key] ?? [])->filter(fn ($item) => !empty($item['url']));
                @endphp
                @continue($items->isEmpty())
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ $title }}</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @foreach($items as $item)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            {{ $item['label'] }}
                                            @if(!empty($item['context']))
                                                <small class="text-muted d-block">{{ $item['context'] }}</small>
                                            @endif
                                        </span>
                                        <a href="{{ $item['url'] }}" class="btn btn-sm btn-primary">Open</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
