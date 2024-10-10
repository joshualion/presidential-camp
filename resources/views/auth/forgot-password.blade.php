@include('auth.template.header')
<style>
    .text-primary, .card-outline{
        color: {{ $SystemSetting ? $SystemSetting->dark_theme_color : '#008751' }} !important;
    }
</style>
<div class="card card-primary">
    <div class="card-body login-card-body">
        <div class="text-center ">
            <h3 class="login-box-msg text-primary ">
                Password Reset
            </h3><hr class="card card-primary"/>
            <p class="login-box-msg text-muted">let us know your email address and we will email you a password reset link that will allow you to choose a new one.</p>


        </div>



    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
          <!-- Email Address -->
        <div class="input-group mb-4">
          <input type="text" class="form-control @error('email') is-invalid @enderror" id ="email" name="email" placeholder="Email">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope text-primary" ></span>
            </div>
          </div>
          @error('email')
            <span class="text-danger">{{$message}}</span>
        @enderror
        </div>

        <div class="row">
          <!-- /.col -->
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block">Submit</button>
          </div>
          <!-- /.col -->
        </div>



    </form>

<!-- /.register-box -->

@include('auth.template.footer')

