@extends('backend.template.backend-master')
@section('content')

    <!-- Main content -->
        <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Add State</h3>
                    <div class="card-tools">
                        <a href="{{ route('nationaladmin.location.states') }}"><button class="btn btn-default"> <i class="fa fa-eye"></i> All states</button></a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">

                    <form action="{{ route('nationaladmin.location.state.store') }}" method="POST">
                        @csrf   

                        @include('backend.nationaladmin.state.add-state-form')

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




