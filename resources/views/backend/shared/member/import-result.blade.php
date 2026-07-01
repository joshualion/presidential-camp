@extends('backend.template.backend-master')
@section('content')

<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Member Import Report</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="small-box bg-primary">
                            <div class="inner"><h3>{{ $batch->total_rows }}</h3><p>Total Rows</p></div>
                            <div class="icon"><i class="fa fa-file-csv"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small-box bg-success">
                            <div class="inner"><h3>{{ $batch->imported_rows }}</h3><p>Imported</p></div>
                            <div class="icon"><i class="fa fa-check"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small-box bg-warning">
                            <div class="inner"><h3>{{ $batch->skipped_rows }}</h3><p>Skipped</p></div>
                            <div class="icon"><i class="fa fa-forward"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small-box bg-danger">
                            <div class="inner"><h3>{{ $batch->failed_rows }}</h3><p>Failed</p></div>
                            <div class="icon"><i class="fa fa-exclamation-triangle"></i></div>
                        </div>
                    </div>
                </div>

                @if($batch->status === 'failed')
                    <div class="alert alert-danger">
                        <strong>Import failed:</strong>
                        {{ data_get($batch->error_summary, 'message', 'Please check the file format and try again.') }}
                    </div>
                @elseif($batch->failed_rows > 0 || $batch->skipped_rows > 0)
                    <div class="alert alert-warning">
                        Import completed with row-level issues. Valid rows were imported; invalid or duplicate rows were not imported.
                    </div>
                @else
                    <div class="alert alert-success">
                        Import completed successfully.
                    </div>
                @endif

                @if($batch->rowErrors->isNotEmpty())
                    <h5>First {{ $batch->rowErrors->count() }} row issues</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Row</th>
                                    <th>Identifier</th>
                                    <th>Issue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($batch->rowErrors as $error)
                                    <tr>
                                        <td>{{ $error->row_number }}</td>
                                        <td>{{ $error->identifier }}</td>
                                        <td>{{ $error->error_message }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route($profileData->access_level.'.members') }}" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Members
                </a>
                <a href="{{ route($profileData->access_level.'.member.import') }}" class="btn btn-primary text-white">
                    <i class="fa fa-upload"></i> Import Another File
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
