@extends('backend.template.backend-master')
@section('content')


  <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-9">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Bulksms Settings </h3>
                <div class="card-tools">
                    <a href="https://wiredirectsms.com/register" target="new"><button class="btn btn-success"> <i class="fa fa-external-link"></i> Create WiredirectSMS Account</button></a>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">

              <!-- form start -->
              <form class="form" action="{{route($profileData->access_level.'.bulksms.setting.store')}}" method="POST">
                @csrf
                <div class="card-body">
                   <div class="form-group">
                   <input type="hidden" name="user_id" value="{{Auth::user()->id}}">
                   <input type="hidden" name="smsgateway_provider" value="wiredirectsms">
                   <input type="hidden" name="smsgateway_endpoint" value="https://wiredirectsms.com/api/v3/">
                  </div>
                  <div class="form-group">
                    <label for="sender_id">WiredirectSMS Registered Sender ID</label>
                    <input type="text" class="form-control" id="sender_id" name="sender_id" placeholder="Enter Sender Id"  maxlength="10">
                  </div>
                  <div class="form-group">
                    <label for="sms_api_key">WiredirectSMS API Token</label>
                    <input type="text" class="form-control" id="sms_api_key" name="sms_api_key" placeholder="Enter API Token" >
                  </div>
               
                <div class="col-3">
                  <button type="submit" class="btn btn-primary btn-block btn-group-lg"><i class='fas fa-save' style='font-size:24px;color:white'> Save</i> </button>
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