@extends('backend.template.backend-master')
@section('content')


  <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-9">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Edit Permission</h3>
                <div class="card-tools">
                    <a href="{{ route('nationaladmin.settings.permissions') }}"><button class="btn btn-default"> <i class="fas fa-eye"></i> All Permissions</button></a>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">

              <!-- form start -->
              <form class="form" action="{{route('nationaladmin.settings.permission.update',  $permission->id)}}" method="POST">
                @csrf
                <div class="card-body">
                  <div class="form-group">
                    <label for="name">Permission Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{$permission->name}}">
                  </div>

                  <div class="form-group">
                     <label for="group_name">Permission Group</label>
                           <select class="form-control" name="group_name" id="group_name" required >

                            <optgroup label="Permission groups">
                                <option value="nationaladmin" @if($permission->group_name == 'nationaladmin') selected @endif>National Admins</option>
                                <option value="regionaladmin" @if($permission->group_name == 'regionaladmin') selected @endif>Regional Admins</option>
                                <option value="stateadmin" @if($permission->group_name == 'stateadmin') selected @endif>State Admins</option>
                                <option value="lgaadmin" @if($permission->group_name == 'lgaadmin') selected @endif>LGA Admins</option>
                                <option value="wardadmin" @if($permission->group_name == 'wardadmin') selected @endif>Ward Admins</option>
                                <option value="puadmin" @if($permission->group_name == 'puadmin') selected @endif>Polling Unit Admins</option>

                               <!-- End payment Options -->
                            </optgroup>

                            <!-- <optgroup label="Manager">
                                <option value="option3">Option 3</option>
                                <option value="option4">Option 4</option>
                            </optgroup> -->



                            </select>
                  </div>

                <div class="col-3">
                  <button type="submit" class="btn btn-primary btn-block btn-group-lg">Update Permission</button>
                </div>
              </form>
            </div>



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
