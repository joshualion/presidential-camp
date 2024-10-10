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



    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
    </form>

        <div class="row">
          <!-- /.col -->
          <div class=" col-12 text-center">
          <div class="d-flex justify-content-center align-items-center" >

            <a class="btn btn-dark btn-block btn-close-white" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            {{ __('LOGOUT') }}
                </a>
          </div>
          </div>
          <!-- /.col -->
        </div>





<!-- /.register-box -->

@include('auth.template.footer')

