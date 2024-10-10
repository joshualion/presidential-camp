@include('auth.template.header')
<div class="card card-dark card-outline">
    <div class="card-body login-card-body">
      <h3 class="login-box-msg">
        <div class="register-logo">
            <a href="/">
                <img src="{{asset('frontend/images/logo/1.png')}}" alt="Ultimataepips Logo" width="75%" height="75%">
            </a>
        </div>
      </h3>


    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div class="input-group mb-3">
          <input type="text" class="form-control @error('email') is-invalid @enderror" id ="email" name="email" required  placeholder="Email">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
          @error('email')
         <span class="text-danger">{{$message}}</span>
         @enderror
        </div>

        <!-- Password -->
        <div class="input-group mb-3">
          <input type="password" class="form-control @error('password') is-invalid @enderror" required name="password" id="password" placeholder="New Password">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
                  @error('password')
                    <span class="text-danger">{{$message}}</span>
                    @enderror
        </div>

        <!-- Confirm Password -->
        <div class="input-group mb-3">
          <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" required name="password_confirmation" id="password_confirmation" placeholder="Confirm Password">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
                  @error('password_confirmation')
                    <span class="text-danger">{{$message}}</span>
                    @enderror
        </div>

        <div class="flex items-center justify-end mt-4">
        <button type="submit" class="btn btn-dark btn-block">
                {{ __('Reset Password') }}
        </button>
        </div>
    </form>



    </div><!-- /.card -->
</div>
<!-- /.register-box -->

@include('auth.template.footer')
