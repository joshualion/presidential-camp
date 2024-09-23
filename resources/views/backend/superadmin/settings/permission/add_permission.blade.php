@extends('template.backend-master')
@section('content')


  <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-9">
            <div class="card card-default">
              <div class="card-header">
                <h3 class="card-title">Add Permission</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.settings.permissions') }}"><button class="btn btn-primary"> <i class="fas fa-eye"></i> All Permissions</button></a>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">

              <!-- form start -->
              <form class="form" action="{{route('admin.settings.permission.store')}}" method="POST">
                @csrf
                <div class="card-body">
                  <div class="form-group">
                    <label for="name">Permission Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter Permission Name">
                  </div>
                 
                  <div class="form-group">                    
                     <label for="group_name">Permission Group</label>
                           <select class="form-control" name="group_name" id="group_name" required >

                            <optgroup label="Admin">
                                <option value="dashboard">Dashboard</option>
                                <option value="store">Store</option>         
                                <option value="billing">Billing</option>
                                <option value="users">Users</option>
                                <option value="bulksms">Bulksms</option>
                                <option value="settings">Settings</option> 
                                <!-- End payment Options -->
                            </optgroup>

                            <!-- <optgroup label="Manager">
                                <option value="option3">Option 3</option>
                                <option value="option4">Option 4</option>
                            </optgroup> -->


                            
                            </select>
                  </div>
               
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
 


@endsection