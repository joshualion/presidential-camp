@include('auth.template.header')

<style>
    .text-primary, .card-outline{
        color: {{ $SystemSetting ? $SystemSetting->dark_theme_color : '#008751' }} !important;
    }
</style>

<div class="card card-primary">
    <div class="card-body login-card-body">
      <h3 class="login-box-msg">
        <div class="register-logo">
            <a href="/">
                <img src="{{asset('frontend/images/logo/1.png')}}" alt="Ultimataepips Logo" width="75%" height="75%">
            </a>
          </div>
      </h3>



    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400 text-center text-success">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <button class="btn btn-primary">
                    {{ __('Resend Verification Email') }}
                </button>
            </div>
        </form>
        <br><br>
        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="btn btn-secondary underline text-sm ">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>



</div><!-- /.card -->
    </div>
    <!-- /.register-box -->

  @include('auth.template.footer')
