@extends('backend.template.backend-master')
@section('content')

@php
    $pollingUnit = $result->pollingUnit;
    $ward = $pollingUnit?->ward;
    $lga = $ward?->localGovernmentArea;
    $state = $lga?->state;
    $extension = $result->result_sheet ? strtolower(pathinfo($result->result_sheet, PATHINFO_EXTENSION)) : '';
@endphp

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Result Sheet</h3>
                        <div class="card-tools">
                            @if($fileUrl)
                                <a href="{{ $fileUrl }}" target="_blank" class="btn btn-default btn-sm">
                                    <i class="fas fa-external-link-alt"></i> Open File
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        @if(!$fileUrl)
                            <p class="text-muted mb-0">No result sheet file has been uploaded for this result.</p>
                        @elseif($extension === 'pdf')
                            <iframe src="{{ $fileUrl }}" style="width:100%; min-height:720px; border:1px solid #ddd;"></iframe>
                        @else
                            <img src="{{ $fileUrl }}" alt="Result sheet" class="img-fluid border">
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Governance Metadata</h3>
                        <div class="card-tools">
                            <span class="badge badge-light">{{ $packageContext['label'] ?? 'Campaign Package' }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p><strong>Election:</strong> {{ \Carbon\Carbon::parse($result->election->year)->year }} - {{ $result->election->name }}</p>
                        <p><strong>Polling Unit:</strong> {{ $pollingUnit->name ?? 'Unknown polling unit' }}</p>
                        <p><strong>Ward:</strong> {{ $ward->name ?? 'Unknown ward' }}</p>
                        <p><strong>LGA:</strong> {{ $lga->name ?? 'Unknown LGA' }}</p>
                        <p><strong>State:</strong> {{ $state->name ?? 'Unknown state' }}</p>

                        <hr>
                        <h6>Submission</h6>
                        <p><strong>Submitted By:</strong> {{ optional($result->submittedBy)->firstname }} {{ optional($result->submittedBy)->lastname }}</p>
                        <p><strong>Submitted At:</strong> {{ optional($result->submitted_at)->format('Y-m-d H:i') ?? 'Unknown time' }}</p>

                        <hr>
                        <h6>Verification</h6>
                        <p><strong>Status:</strong> {{ ucfirst($result->verification_status ?? 'submitted') }}</p>
                        <p><strong>Verified By:</strong> {{ trim((optional($result->verifiedBy)->firstname ?? '').' '.(optional($result->verifiedBy)->lastname ?? '')) ?: 'Not verified' }}</p>
                        <p><strong>Verified At:</strong> {{ optional($result->verified_at)->format('Y-m-d H:i') ?? 'Not verified' }}</p>
                        <p><strong>Notes:</strong> {{ $result->verification_notes ?: 'None' }}</p>

                        <hr>
                        <h6>Dispute</h6>
                        <p><strong>Status:</strong> {{ ucfirst($result->dispute_status ?? 'normal') }}</p>
                        <p><strong>Disputed By:</strong> {{ trim((optional($result->disputedBy)->firstname ?? '').' '.(optional($result->disputedBy)->lastname ?? '')) ?: 'Not disputed' }}</p>
                        <p><strong>Disputed At:</strong> {{ optional($result->disputed_at)->format('Y-m-d H:i') ?? 'Not disputed' }}</p>
                        <p><strong>Reason:</strong> {{ $result->dispute_reason ?: 'None' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
