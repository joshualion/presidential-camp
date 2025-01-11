@extends('backend.template.backend-master')
@section('content')

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            Incident Report for 
                            {{ $pollingUnit->name }} 
                            in Ward {{ $pollingUnit->ward->name }} , 
                            {{ $pollingUnit->ward->localGovernmentArea->name }} LGA of 
                            {{ $pollingUnit->ward->localGovernmentArea->state->name }} State
                        </h3>
                        <div class="card-tools">
                           
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        @if($incidents->isEmpty())
                            <p>No incidents reported for this polling unit.</p>
                        @else
                            @foreach($incidents as $incident)
                                <div class="card mb-4">
                                    <div class="card-header bg-secondary">
                                        <h5>Incident #{{ $loop->iteration }}</h5>
                                        <p><strong>Reported by:</strong> {{ optional($incident->agent)->name ?? 'Unknown Agent' }}</p>

                                        <p><strong>Remarks:</strong> {{ $incident->remarks ?? 'No remarks provided' }}</p>
                                    </div>
                                    <div class="card-body">
                                        <h6>Picture Evidence:</h6>
                                        @if($incident->pictureEvidences->isEmpty())
                                            <p>No picture evidence available.</p>
                                        @else
                                            <div class="row">
                                                @foreach ($incident->pictureEvidences as $picture)
                                                    <div class="col-md-4 mb-3">
                                                        <img src="{{ asset('storage/' . $picture->file_path) }}" 
                                                             alt="Picture Evidence" 
                                                             class="img-fluid rounded">
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <h6>Video Evidence:</h6>
                                        @if($incident->videoEvidences->isEmpty())
                                            <p>No video evidence available.</p>
                                        @else
                                            <div class="row">
                                                @foreach ($incident->videoEvidences as $video)
                                                    <div class="col-md-4 mb-3">
                                                        <video controls class="w-100 rounded">
                                                            <source src="{{ asset('storage/' . $video->file_path) }}" type="video/mp4">
                                                            Your browser does not support the video tag.
                                                        </video>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>
<!-- /.content -->

@endsection
