@extends('backend.template.backend-master')
@section('content')


  <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-9">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Edit Role</h3>
                <div class="card-tools">
                    <a href="{{ route('nationaladmin.settings.roles') }}"><button class="btn btn-default"> <i class="fas fa-eye"></i> All Roles</button></a>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">

              <!-- form start -->
              <form class="form" action="{{route('nationaladmin.settings.role.update',  $role->id)}}" method="POST">
                @csrf
                <div class="card-body">
                  <div class="form-group">
                    <label for="name">Role Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{$role->name}}">
                  </div>

                  <div class="form-group">
                    <label for="group_name">Role Group</label>
                          <select class="form-control" name="group_name" id="group_name" required >
                               <option value="">Select Role Group</option>
                               <optgroup label="Roles">
                                <option value="nationaladmin" @if($role->group_name == 'nationaladmin') selected @endif>National Admins</option>
                                <option value="regionaladmin" @if($role->group_name == 'regionaladmin') selected @endif>Regional Admins</option>
                                <option value="stateadmin" @if($role->group_name == 'stateadmin') selected @endif>State Admins</option>
                                <option value="lgaadmin" @if($role->group_name == 'lgaadmin') selected @endif>LGA Admins</option>
                                <option value="wardadmin" @if($role->group_name == 'wardadmin') selected @endif>Ward Admins</option>
                                <option value="puadmin" @if($role->group_name == 'puadmin') selected @endif>Polling Unit Admins</option>
                                <!-- End payment Options -->
                               </optgroup>
                           </select>
                 </div>



                <div class="col-3">
                  <button type="submit" class="btn btn-primary btn-block btn-group-lg">Update Role</button>
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
