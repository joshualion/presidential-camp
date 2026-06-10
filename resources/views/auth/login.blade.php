<style>
    body {
      background-color: {{ $SystemSetting ? $SystemSetting->dark_theme_color : '#165828' }} !important;
    }
    .card-primary{
      border-top: 3px solid {{ $SystemSetting ? $SystemSetting->dark_theme_color : '#165828' }} !important;
      border-bottom: 3px solid {{ $SystemSetting ? $SystemSetting->dark_theme_color : '#165828' }} !important;
    }
</style>
@include('auth.template.header')

<div class="card card-primary">
    <div class="card-body login-card-body">
        <div class="text-center ">
            <h3 class="login-box-msg text-uppercase text-primary ">
                Login
            </h3><hr class="card card-primary"/>
            <p class="login-box-msg text-muted">Sign in to start your session</p>
        </div>




    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

  <form method="POST" action="{{ route('login') }}">
        @csrf @method('POST')
        <div class="input-group mb-3">
          <input type="text" class="form-control @error('login') is-invalid @enderror" id ="login" name="login" placeholder="Email/Username/Phone" autocomplete="true">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope text-primary"></span>
            </div>
          </div>
          @error('login')
                          <span class="text-danger">{{$message}}</span>
                        @enderror
        </div>

        <div class="input-group mb-3">
          <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" id="password" placeholder="Password" autocomplete="true">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock text-primary"></span>
            </div>
          </div>
          @error('password')
                          <span class="text-danger">{{$message}}</span>
                        @enderror
        </div>

        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
               <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" autocomplete="true" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remember">
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
            </label>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-outline btn-block">Sign In</button>
          </div>
          <!-- /.col -->
        </div>
      </form>
       <p class="mb-1">
        @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
      </p>
      <hr/>
      @if($SystemSetting && $SystemSetting->frontend_registration == 1)
      <a href="/register" class="text-center">New member? Signup</a>
      @endif


</div><!-- /.card -->
</div>
<!-- /.register-box -->

@include('auth.template.footer')

<style>
  .text-primary, .card-outline{
      color: {{ $SystemSetting ? $SystemSetting->dark_theme_color : '#008751' }} !important;
  }
</style>
