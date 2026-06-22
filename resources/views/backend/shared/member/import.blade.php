@extends('backend.template.backend-master')
@section('content')

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Import Members</h3>
                    </div>
                    <form action="{{ route($profileData->access_level.'.member.import.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            @if(isset($errors) && $errors->any())
                                <div class="alert alert-danger">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <p class="text-muted">
                                Upload a CSV file with existing campaign members. Rows outside your licensed or administrative scope are rejected.
                            </p>

                            @if(!empty($locationForm['message']))
                                <div class="alert alert-info">
                                    {{ $locationForm['message'] }}
                                </div>
                            @endif

                            <div class="mb-3">
                                <a href="{{ route($profileData->access_level.'.member.import.template') }}" class="btn btn-success">
                                    <i class="fa fa-download"></i> Download CSV Template
                                </a>
                            </div>

                            <div class="form-group">
                                <label for="import_file">CSV file</label>
                                <input type="file" name="import_file" id="import_file" class="form-control" accept=".csv,text/csv" required>
                                <small class="form-text text-muted">
                                    CSV only. Maximum 5,000 rows per upload. Duplicates are skipped and reported.
                                </small>
                            </div>

                            <div class="alert alert-warning">
                                Do not include superadmin users. Imported accounts receive a secure temporary password and must update their credentials before normal use.
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <a href="{{ route($profileData->access_level.'.members') }}" class="btn btn-default">
                                <i class="fa fa-arrow-left"></i> Back to Members
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-upload"></i> Upload and Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Template Notes</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2">Required columns:</p>
                        <ul class="pl-3">
                            <li>firstname</li>
                            <li>lastname</li>
                            <li>username</li>
                            <li>email</li>
                            <li>gender</li>
                            <li>access_level</li>
                        </ul>
                        <p class="text-muted mb-2">Location values can use names. Fixed licensed geography may be left blank and will be applied automatically.</p>
                        <p class="text-muted mb-0">No email or SMS is sent during import.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
