@php
    $items = collect($items ?? []);
    $hasGovernanceColumns = $items->contains(fn ($item) => isset($item['verification_status']) || isset($item['dispute_status']));
@endphp

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        @if($items->isEmpty())
            <p class="text-muted mb-0 p-3">{{ $emptyText ?? 'No records found for this scope.' }}</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Polling Unit</th>
                            <th>Ward</th>
                            <th>LGA</th>
                            <th>By</th>
                            <th>Time</th>
                            @if($hasGovernanceColumns)
                                <th>Verification</th>
                                <th>Dispute</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $activity)
                            <tr>
                                <td>
                                    <span class="badge badge-{{ ($activity['type'] ?? '') === 'incident' ? 'danger' : (($activity['type'] ?? '') === 'evidence' ? 'secondary' : 'success') }}">
                                        {{ ucfirst($activity['type'] ?? 'activity') }}
                                    </span>
                                    {{ $activity['title'] ?? 'Activity' }}
                                </td>
                                <td>{{ $activity['polling_unit'] ?? 'Unknown polling unit' }}</td>
                                <td>{{ $activity['ward'] ?? 'Unknown ward' }}</td>
                                <td>{{ $activity['lga'] ?? 'Unknown LGA' }}</td>
                                <td>{{ $activity['actor'] ?? 'Unknown reporter' }}</td>
                                <td>
                                    @if(!empty($activity['time']))
                                        {{ $activity['time']->diffForHumans() }}
                                    @else
                                        Unknown time
                                    @endif
                                </td>
                                @if($hasGovernanceColumns)
                                    <td>{{ $activity['verification_status'] ?? 'N/A' }}</td>
                                    <td>{{ $activity['dispute_status'] ?? 'N/A' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
