@extends('backend.template.backend-master')

@section('content')
@php
    $emailHtml = view('emails.campaign-notification', ['campaign' => $campaign, 'branding' => $branding])->render();
@endphp
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Review Email Notification</h3>
            </div>
            <div class="card-body">
                @if($recipientCount === 0)
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        No eligible recipients match the selected filters. Go back and adjust the audience before sending.
                    </div>
                @else
                    <div class="alert alert-info">
                        Review the audience and branded email below. The audience will be validated again when you confirm sending.
                    </div>
                @endif

                <div class="row">
                    <div class="col-lg-5">
                        <div class="card card-outline card-secondary h-100">
                            <div class="card-header"><h3 class="card-title">Audience summary</h3></div>
                            <div class="card-body p-0">
                                <table class="table table-bordered mb-0">
                                    <tr><th style="width: 42%">Sender</th><td>{{ $senderName }}</td></tr>
                                    <tr><th>Sending scope</th><td>{{ $summary['scope'] }}</td></tr>
                                    <tr><th>Recipient group</th><td>{{ $summary['recipient_group'] }}</td></tr>
                                    <tr>
                                        <th>Access levels</th>
                                        <td>{{ $summary['access_levels'] ? implode(', ', $summary['access_levels']) : 'Not applied' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Roles</th>
                                        <td>{{ $summary['roles'] ? implode(', ', $summary['roles']) : 'Not applied' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Location filters</th>
                                        <td>
                                            @forelse($summary['locations'] as $type => $name)
                                                <div><strong>{{ $type }}:</strong> {{ $name }}</div>
                                            @empty
                                                All within my jurisdiction
                                            @endforelse
                                        </td>
                                    </tr>
                                    <tr><th>Total recipients</th><td><span class="badge badge-primary p-2">{{ number_format($recipientCount) }}</span></td></tr>
                                    <tr><th>Subject</th><td>{{ $campaign->subject }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7 mt-3 mt-lg-0">
                        <div class="card card-outline card-secondary h-100">
                            <div class="card-header"><h3 class="card-title">Email preview</h3></div>
                            <div class="card-body p-0">
                                <iframe id="email-preview-frame" title="Branded email preview" sandbox="" style="width:100%;height:680px;border:0;background:#f4f6f9"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex flex-wrap">
                <form method="POST" action="{{ route($profileData->access_level.'.email-notifications.preview.back') }}" class="mr-2 mb-2">
                    @csrf
                    @include('backend.email-notifications.partials.composition-fields')
                    <button type="submit" class="btn btn-default"><i class="fas fa-arrow-left"></i> Back to Edit</button>
                </form>

                @if($recipientCount > 0)
                    <form id="confirm-email-notification" method="POST" action="{{ route($profileData->access_level.'.email-notifications.store') }}" class="mb-2">
                        @csrf
                        @include('backend.email-notifications.partials.composition-fields')
                        <button id="confirm-send-button" type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Confirm Send to {{ number_format($recipientCount) }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('email-preview-frame').srcdoc = @json($emailHtml);

    const confirmForm = document.getElementById('confirm-email-notification');
    if (confirmForm) {
        confirmForm.addEventListener('submit', function () {
            const button = document.getElementById('confirm-send-button');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Confirming...';
        });
    }
});
</script>
@endsection
