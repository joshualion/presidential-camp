@extends('backend.template.backend-master')
@section('content')


    <!-- Main content -->
        <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Edit Region</h3>
                    <div class="card-tools">
                        <a href="{{ route('superadmin.location.regions') }}"><button class="btn btn-default"> <i class="fa fa-eye"></i> All Regions</button></a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">

                    <form action="{{ route('superadmin.location.region.update' , $region->uuid) }}" method="POST">
                        @csrf

                        @include('backend.superadmin.region.edit-region-form')

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




