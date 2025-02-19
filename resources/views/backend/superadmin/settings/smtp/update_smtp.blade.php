@extends('backend.template.backend-master')
@section('content')

        <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card card-primary ">
              <div class="card-header">
              <h3 class="card-title">SMTP Settings</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove">
                        <i class="fas fa-times"></i>
                    </button>
                    </div>
            </div>

            <div class="card-body" style="display: block;">
                <form method="POST" action="{{ route('superadmin.settings.smtp.update') }}">
                    @csrf
                    @method('POST')

                    <input type="hidden" name="id" value="{{$smtpsettings->id}}" />

                    <div class="form-group row">
                        <label for="mailer" class="col-sm-2 col-form-label">Driver</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control @error('mailer') is_invalid @enderror" name="mailer" id="mailer" value="{{($smtpsettings->mailer)}}" placeholder="smtp">
                        </div>
                        @error('mailer')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                      </div>


                      <div class="form-group row">
                        <label for="host" class="col-sm-2 col-form-label">  Server</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control @error('host') is_invalid @enderror" name="host" id="host" value="{{($smtpsettings->host)}}" placeholder="smtp.gmail.com">
                        </div>
                        @error('host')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                      </div>

                      <div class="form-group row">
                        <label for="port" class="col-sm-2 col-form-label"> Port</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control @error('port') is_invalid @enderror" name="port" id="port" value="{{($smtpsettings->port)}}" placeholder="587">
                        </div>
                        @error('port')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group row">
                        <label for="username" class="col-sm-2 col-form-label">  Username</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control @error('username') is_invalid @enderror" name="username" id="username" value="{{($smtpsettings->username)}}" placeholder="youremail@gmail.com">
                        </div>
                        @error('username')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group row">
                        <label for="password" class="col-sm-2 col-form-label"> Password</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control @error('password') is_invalid @enderror" name="password" id="password" value="{{($smtpsettings->password)}}">
                        </div>
                        @error('password')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group row">
                        <label for="encryption" class="col-sm-2 col-form-label"> Encryption</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control @error('encryption') is_invalid @enderror" name="encryption" id="encryption" value="{{($smtpsettings->encryption)}}">
                        </div>
                        @error('encryption')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group row">
                        <label for="from_address" class="col-sm-2 col-form-label">From Address</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control @error('from_address') is_invalid @enderror" name="from_address" id="from_address" value="{{($smtpsettings->from_address)}}">
                        </div>
                        @error('from_address')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <br>
                    <div class="form-group row">
                        <div class="col-sm-6 col-sm-offset-2 center right-5">
                        <button type="submit" class="btn btn-primary"><i class='fas fa-save' style='font-size:24px;color:white'> Save</i></button>

                         </div>
                    </div>
               </form>
                <br>
                <div class="card-footer">

                        <strong> Note:</strong>
                        <p class="text-info">If you're using Google Mail SMTP, please note that Gmail's security settings may require you to generate an "App Password" specifically for your application instead of using your regular Gmail password.</p>


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



@endsection
