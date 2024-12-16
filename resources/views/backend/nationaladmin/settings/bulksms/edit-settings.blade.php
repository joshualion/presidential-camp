@extends('backend.template.backend-master')
@section('content')


  <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-9">
            <div class="card card-success card-outline">
              <div class="card-header">
                <h3 class="card-title">Bulksms Settings </h3>
            
              </div>
              <!-- /.card-header -->
              <div class="card-body">

              <!-- form start -->
              <form class="form" action="{{route($profileData->access_level.'.bulksms.setting.update')}}" method="POST">
                @csrf
                <div class="card-body">
                   <div class="form-group">
                   <input type="hidden" name="user_id" value="{{Auth::user()->id}}">
                  </div>
                  <div class="form-group">
                    <label for="sender_id">WiredirectSMS Registered Sender ID</label>
                    <input type="text" class="form-control" id="sender_id" name="sender_id" value="{{$bulksms->sender_id}}"  maxlength="10">
                    <input type="hidden" name="smsgateway_provider" value="wiredirectsms">
                </div>
                  <div class="form-group">
                    <label for="sms_api_key">WiredirectSMS API Token</label>
                    <input type="text" class="form-control" id="sms_api_key" name="sms_api_key" value="{{$bulksms->sms_api_key}}" >
                  </div>
               
                <div class="col-3">
                  <button type="submit" class="btn btn-success btn-block btn-group-lg"><i class='fas fa-save' style='font-size:24px;color:white'> Save</i></button>
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