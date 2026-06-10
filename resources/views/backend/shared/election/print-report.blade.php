<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Election Report - {{ $election->name }}</title>
    <link rel="stylesheet" href="{{ asset('assets/plugins/bootstrap/css/bootstrap.min.css') }}">
    <style>
        body { font-size: 12px; color: #111; }
        .report-header { border-bottom: 2px solid #222; margin-bottom: 16px; padding-bottom: 10px; }
        .badge { border: 1px solid #222; color: #111; }
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th { background: #f1f1f1; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="report-header">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h3 class="mb-1">{{ \Carbon\Carbon::parse($election->year)->year }} - {{ $election->name }}</h3>
                <div>{{ $scopeLabel }}</div>
                <div>{{ $packageContext['label'] ?? 'Campaign Package' }}</div>
            </div>
            <button type="button" class="btn btn-dark btn-sm no-print" onclick="window.print()">Print</button>
        </div>
    </div>

    <h5>Election Summary</h5>
    <table class="table table-bordered table-sm">
        <tbody>
            <tr>
                <th>Total Polling Units</th>
                <td>{{ number_format($summaryStats['total_polling_units'] ?? 0) }}</td>
                <th>Submitted Results</th>
                <td>{{ number_format($summaryStats['submitted_polling_units'] ?? 0) }}</td>
                <th>Verified Results</th>
                <td>{{ number_format($summaryStats['verified_results'] ?? 0) }}</td>
                <th>Disputed Results</th>
                <td>{{ number_format($summaryStats['disputed_results'] ?? 0) }}</td>
            </tr>
            <tr>
                <th>Total Valid Votes</th>
                <td>{{ number_format($summaryStats['total_valid_votes'] ?? 0) }}</td>
                <th>Total Parties</th>
                <td>{{ number_format($summaryStats['total_parties_participated'] ?? 0) }}</td>
                <th>Results Received</th>
                <td>{{ number_format($summaryStats['results_received_percent'] ?? 0, 2) }}%</td>
                <th>Generated</th>
                <td>{{ now()->format('Y-m-d H:i') }}</td>
            </tr>
        </tbody>
    </table>

    <h5>Party Totals</h5>
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>Rank</th>
                <th>Party</th>
                <th>Votes</th>
                <th>Comment</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary as $item)
                <tr>
                    <td>{{ $item['rank'] }}</td>
                    <td>{{ $item['party_name'] }}</td>
                    <td>{{ number_format($item['total_votes']) }}</td>
                    <td>{{ $item['comment'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h5>Polling Unit Results</h5>
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>#</th>
                <th>State</th>
                <th>LGA</th>
                <th>Ward</th>
                <th>Polling Unit</th>
                <th>{{ $election->party->acronym }} Votes</th>
                <th>Total Votes</th>
                <th>Submitted By</th>
                <th>Submitted At</th>
                <th>Verification</th>
                <th>Dispute</th>
                <th>Governance Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['state_name'] }}</td>
                    <td>{{ $row['lga_name'] }}</td>
                    <td>{{ $row['ward_name'] }}</td>
                    <td>{{ $row['polling_unit_name'] }}</td>
                    <td>{{ number_format($row['votes_obtained']) }}</td>
                    <td>{{ number_format($row['total_votes_cast']) }}</td>
                    <td>{{ $row['submitted_by'] }}</td>
                    <td>{{ $row['submitted_at'] ?: 'Unknown time' }}</td>
                    <td>{{ ucfirst($row['verification_status']) }}</td>
                    <td>{{ ucfirst($row['dispute_status']) }}</td>
                    <td>
                        @if($row['verified_by'])
                            Verified by {{ $row['verified_by'] }} {{ $row['verified_at'] ? 'at '.$row['verified_at'] : '' }}.
                        @endif
                        @if($row['verification_notes'])
                            Notes: {{ $row['verification_notes'] }}.
                        @endif
                        @if($row['disputed_by'])
                            Disputed by {{ $row['disputed_by'] }} {{ $row['disputed_at'] ? 'at '.$row['disputed_at'] : '' }}.
                        @endif
                        @if($row['dispute_reason'])
                            Reason: {{ $row['dispute_reason'] }}.
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
