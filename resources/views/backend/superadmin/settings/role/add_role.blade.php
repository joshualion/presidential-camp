@extends('backend.template.backend-master')
@section('content')


  <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-9">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Add Role</h3>
                <div class="card-tools">
                    <a href="{{ route($profileData->access_level.'.settings.roles') }}"><button class="btn btn-default"> <i class="fas fa-eye"></i> All Roles</button></a>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">

              <!-- form start -->
              <form class="form" action="{{route($profileData->access_level.'.settings.role.store')}}" method="POST">
                @csrf

                  <div class="form-group">
                    <label for="name">Role Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter Role Name">
                  </div>
                  <div class="form-group">
                    <label for="group_name">Role Group</label>
                          <select class="form-control" name="group_name" id="group_name" required >
                               <option value="">Select Role Group</option>
                               <optgroup label="Roles">
                                   @foreach($rbacRoleGroups ?? [] as $accessLevel => $label)
                                       <option value="{{ $accessLevel }}" {{ old('group_name') === $accessLevel ? 'selected' : '' }}>{{ $label }}</option>
                                   @endforeach
                               </optgroup>
                           </select>
                           @error('name')
                               <div class="text-danger small">{{ $message }}</div>
                           @enderror
                           @error('group_name')
                               <div class="text-danger small">{{ $message }}</div>
                           @enderror
                 </div>
                <div class="col-3">
                  <button type="submit" class="btn btn-primary btn-block btn-group-lg">Add Role</button>
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
