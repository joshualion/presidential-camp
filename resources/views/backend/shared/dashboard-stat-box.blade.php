@php
    $value = $value ?? 0;
    $total = $total ?? null;
    $color = $color ?? 'bg-primary';
    $icon = $icon ?? 'ion ion-stats-bars';
    $label = $label ?? 'Metric';
    $key = $key ?? \Illuminate\Support\Str::slug($label, '_');
    $statGroup = $statGroup ?? null;
    $suffix = $suffix ?? '';
    $context = $context ?? null;
    $footerUrl = $footerUrl ?? null;
    $footerText = $footerText ?? 'More info';
    $columnClass = $columnClass ?? 'col-xl-3 col-md-6 col-12 d-flex mb-3';
    $decimalPlaces = $decimalPlaces ?? ($suffix === '%' ? 2 : 1);
    $shortLabels = [
        'Senatorial Districts' => 'Sen. Dist',
        'Federal Constituencies' => 'Fed.Const',
        'Polling Units' => 'PUs',
    ];
    $displayLabel = $shortLabels[$label] ?? $label;
    $percent = !is_null($total) && (int) $total > 0
        ? number_format(((int) $value / (int) $total) * 100, 2)
        : '0.00';
    $displayValue = is_numeric($value)
        ? (floor((float) $value) != (float) $value ? number_format((float) $value, $decimalPlaces) : number_format((int) $value))
        : $value;
@endphp

<div class="{{ $columnClass }}" data-dashboard-stat-card data-dashboard-stat-group="{{ $statGroup }}" data-dashboard-stat-key="{{ $key }}">
    <div class="small-box {{ $color }} h-100 w-100 d-flex flex-column">
        <div class="inner dashboard-stat-body">
            <h3>
                <span data-stat-value>{{ $displayValue }}</span><span data-stat-suffix>{{ $suffix }}</span>
                @if(!is_null($total))
                    <small>of <span data-stat-total>{{ number_format((int) $total) }}</span></small>
                @endif
            </h3>
            @if(!is_null($total))
                <p>{{ $displayLabel }}@if($context) ({{ $context }})@endif</p>
                <p class="mb-0 text-right"><b><span data-stat-percent>{{ $percent }}</span>%</b></p>
            @else
                <p>{{ $displayLabel }}</p>
            @endif
        </div>
        <div class="icon">
            <i class="{{ $icon }}"></i>
        </div>
        @if($footerUrl)
            <a href="{{ $footerUrl }}" class="small-box-footer mt-auto">{{ $footerText }} <i class="fas fa-arrow-circle-right"></i></a>
        @else
            <span class="small-box-footer mt-auto">{{ $footerText }}</span>
        @endif
    </div>
</div>
