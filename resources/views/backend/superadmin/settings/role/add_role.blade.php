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
                    <a href="{{ route('nationaladmin.settings.roles') }}"><button class="btn btn-default"> <i class="fas fa-eye"></i> All Roles</button></a>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">

              <!-- form start -->
              <form class="form" action="{{route('nationaladmin.settings.role.store')}}" method="POST">
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
                                   <option value="nationaladmin">National Admins</option>
                                   <option value="regionaladmin">Regional Admins</option>
                                   <option value="stateadmin">State Admins</option>
                                   <option value="lgaadmin">LGA Admins</option>
                                   <option value="wardadmin">Ward Admins</option>
                                   <option value="puadmin">Polling Unit Admins</option>
                                   <!-- End payment Options -->
                               </optgroup>
                           </select>
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
