<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ number_format($summaryStats['submitted_polling_units'] ?? 0) }}</h3>
                <p>Submitted Results</p>
            </div>
            <div class="icon"><i class="fas fa-file-alt"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($summaryStats['verified_results'] ?? 0) }}</h3>
                <p>Verified Results</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ number_format($summaryStats['disputed_results'] ?? 0) }}</h3>
                <p>Disputed Results</p>
            </div>
            <div class="icon"><i class="fas fa-flag"></i></div>
        </div>
    </div>
</div>
