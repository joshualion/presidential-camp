@extends('backend.template.backend-master')
@section('content')

    <!-- Main content -->
        <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Edit State</h3>
                    <div class="card-tools">
                        <a href="{{ route($profileData->access_level.'.location.states') }}"><button class="btn btn-default"> <i class="fa fa-eye"></i>states</button></a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">

                    <form action="{{ route($profileData->access_level.'.location.state.update', $state->uuid) }}" method="POST">
                        @csrf   

                        @include('backend.'.$profileData->access_level.'.state.edit-state-form')

                    </form>

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




