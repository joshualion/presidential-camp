@extends('backend.template.backend-master')
@section('content')

<style>
  .form-check-label{
    text-transform: capitalize;
  }
</style>

  <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Add Roles in Permission</h3>
                <div class="card-tools">
                <a href="{{ route($profileData->access_level.'.settings.roles') }}"><button class="btn btn-default"> <i class="fas fa-eye"></i> Manage Roles</button></a>

                <a href="{{ route($profileData->access_level.'.settings.permissions') }}"><button class="btn btn-default"> <i class="fas fa-eye"></i> Manage Permissions</button></a>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">

              <!-- form start -->
              <form class="form" action="{{route($profileData->access_level.'.settings.role.permission.store')}}" method="POST">
                @csrf


                  <div class="form-group">
                          <label for="role_name">Roles Name</label>
                          <select class="form-control" name="role_id" id="role_id" required >
                          @foreach($roles as $role)
                          <option value="{{$role->id}}">{{$role->name}}</option>
                          @endforeach
                          </select>
                          @error('role_id')
                            <div class="text-danger small">{{ $message }}</div>
                          @enderror
                  </div>

                  <div class="form-group form-checkbox col-4 form-check-inline">
                    <input type="checkbox" class="form-check-input" id="permission_all"  >
                    <label class="form-check-label" for="permission_all">All Permission</label>
                  </div>
                  <hr>

           @foreach($permission_groups as $permissiongroup)
                  <div class="row">
                    <div class="col-3">
                    <div class="form-group form-checkbox col-4 form-check-inline">
                    <input type="checkbox" class="form-check-input" id="permission_group{{$permissiongroup->group_name}}"  >
                    <label class="form-check-label" for="permission_group{{$permissiongroup->group_name}}">{{$permissiongroup->group_name}}</label>
                  </div>

                    </div>

                    <div class="col-9">
        @php
        $permissions = App\Models\User::getPermissionByGroupName($permissiongroup->group_name);
        @endphp

                @foreach($permissions as $permission)
                    <div class="form-group form-checkbox col-4 form-check-inline">
                    <input type="checkbox" class="form-check-input" id="permission{{$permission->id}}" name="permission[]" value="{{$permission->id}}">
                    <label class="form-check-label" for="permission{{$permission->id}}">{{$permission->name}}</label>
                   </div>
                   @endforeach
                   <hr>
                        <br>
                    </div>

                  </div><!-- End of row -->
                @endforeach
                <hr>


                <div class="col-3">
                  <button type="submit" class="btn btn-primary btn-block btn-group-lg">Add Permission</button>
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
 <!-- jQuery -->
<script src="{{asset('assets/plugins/jquery/jquery.min.js')}}"></script>

<script>
    $('#permission_all').click(function(){
        if ($(this).is(':checked')) {
            $('input[name="permission[]"]').prop('checked', true);
        } else {
            $('input[name="permission[]"]').prop('checked', false);
        }
    });
</script>



@endsection
