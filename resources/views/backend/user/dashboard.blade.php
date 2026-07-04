@extends('backend.template.backend-master')
@section('content')


<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Welcome, {{ $user->firstname ?? $user->username }} 👋</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="remove">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="card-body">

        <div class="row">
            <!-- Announcements FIRST -->
            <div class="col-md-12">
                <div class="card card-danger">
                    <div class="card-header card-danger">
                        <h3 class="card-title">Announcements</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-tool" data-card-widget="remove">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        @forelse($announcements as $note)
                            <div class="alert alert-light mb-2">
                                <strong class="text-dark">{{ $note->title }}</strong>
                                <p>{{ \Illuminate\Support\Str::limit($note->message, 250) }}</p>
                                <a href="{{ route('user.notices.show', $note) }}" class="btn btn-sm btn-outline-light">Read full notice</a>
                                <small class="text-muted">{{ $note->created_at->diffForHumans() }}</small>
                            </div>
                        @empty
                            <p>No announcements at the moment.</p>
                        @endforelse
                        <a href="{{ route('user.notices') }}" class="btn btn-sm btn-primary mt-2 text-white">Open Notice Board</a>
                    </div>
                </div>
            </div>


        </div><!-- /.row -->

        @if($votingBlocEnabled)
        <div class="row">
            <!-- My Voting Block -->
            <div class="col-md-6">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">My Voting Block</h3>
                    </div>
                    <div class="card-body">
                        <div class="text text-success mb-0">
                            <strong>{{ $blockStrength }}</strong> Member(s) in Your Block
                            <a href="{{ route('user.block') }}" class="mt-1 mb-2 gap-2">
                                Visit Your Block <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>

                        <p class="mb-2 mt-3">Share this link to grow your voting block.</p>
                        <div class="badge badge-success d-flex justify-content-between align-items-center">
                            <span class="text-truncate" style="max-width: 85%;">
                                {{ $user->referral_link }}
                            </span>
                            <button class="btn btn-sm btn-light" onclick="copyBlockLink('{{ $user->referral_link }}')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Voting Block Distribution Chart -->
            <div class="col-md-6">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Block Members by Vote Eligibility</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="blockChart" style="min-height: 250px; height: 250px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div><!-- /.card-body -->
</div><!-- /.card -->

@if($votingBlocEnabled)
{{-- Chart.js --}}
<script src="{{ asset('assets/plugins/chart.js/Chart.min.js') }}"></script>

{{-- Toastr --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
    // Copy referral link
    function copyBlockLink(link) {
        navigator.clipboard.writeText(link).then(() => {
            toastr.success('Referral link copied to clipboard!');
        }).catch(() => {
            toastr.error('Failed to copy referral link.');
        });
    }

    // Voting Block Chart
    var ctx = document.getElementById('blockChart').getContext('2d');
    var blockChart = new Chart(ctx, {
        type: 'pie',
        data: {!! json_encode($referralChartData) !!},
        options: {
            responsive: true,
            legend: { position: 'bottom' }
        }
    });
</script>
@endif

@endsection
