@include('auth.template.header')
@php
    $a = rand(1, 10);
    $b = rand(1, 10);
    session(['logic_question_answer' => $a + $b]);
@endphp


<div class="card card-primary">
    <div class="card-body login-card-body">
        <div class="text-center ">
            <h3 class="login-box-msg text-uppercase text-primary ">
               SignUp
            </h3><hr class="card card-primary"/>
            <p class="login-box-msg text-muted">Create a New Account</p>
        </div>
  <form action="{{ route('register') }}" method="post" autocomplete="off">
            @csrf

                <!-- Username Unique -->
                <div class="  input-group mb-3">
                <input type="text" class="form-control @error('username') is-invalid @enderror " id="username" name="username" required placeholder="Username *" value="{{ old('username') }}" autofocus="false">
                <div class="input-group-append">
                    <div class="input-group-text">
                    <span class="fas fa-user text-primary"></span>
                    </div>
                </div>
                @error('username')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                </div>


                <div class=" input-group mb-3">
                <input type="text" class="form-control  @error('firstname') is-invalid @enderror" id="firstname" name="firstname" required placeholder="First Name *" value="{{ old('firstname') }}">
                <div class="input-group-append">
                    <div class="input-group-text">
                    <span class="fas fa-user text-primary"></span>
                    </div>
                </div>
                @error('firstname')
                <div class="invalid-feedback text-warning">{{ $message }}</div>
            @enderror
                </div>
                <div class=" input-group mb-3">
                <input type="text" class="form-control @error('lastname') is-invalid @enderror" id="lastname" name="lastname" required placeholder="Last Name *" value="{{ old('lastname') }}">
                <div class="input-group-append">
                    <div class="input-group-text">
                    <span class="fas fa-user text-primary"></span>
                    </div>
                </div>
                @error('lastname')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                </div>

                <div class=" input-group mb-3">
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="Email" value="{{ old('email') }}">
                    <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-envelope text-primary"></span>
                    </div>
                    </div>
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>



                        <!-- Password -->
                    <div class=" input-group mb-3">
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required  placeholder="Password">
                        <div class="input-group-append">
                            <div class="input-group-text">
                            <span class="fas fa-lock text-primary"></span>
                            </div>
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        </div>
                        <!-- Confirm Password -->
                        <div class=" input-group mb-3">
                        <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation" required  placeholder="Confirm password">
                        <div class="input-group-append">
                            <div class="input-group-text">
                            <span class="fas fa-lock text-primary"></span>
                            </div>
                        </div>
                        @error('password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        </div>

                        <!-- RECAPTCHA GOES HERE -->
                                 <div class="form-group mb3">
                                     <input type="text" name="nickname" style="position:absolute; left:-9999px;">
                                   <!-- Simple Logic Question -->
                                    <div>
                                        <label for="logic_question">What is {{ $a }} + {{ $b }}?</label>
                                        <input type="text" name="logic_question" id="logic_question" required value="{{ old('logic_question') }}">
                                        @error('logic_question')
                                            <div style="color:red;">{{ $message }}</div>
                                        @enderror
                                    </div>

                                </div>

                            @if(request('ref'))
                                <input type="hidden" name="referral_code" value="{{ request('ref') }}">
                            @endif



                        <!-- /.col -->
                        <div class="input-group mb-3">
                            <button type="submit" class="btn btn-primary btn-block fa-pull-right text-center ">Register</button>

                        </div>
                        <!-- /.col -->

                            <div class="pull-right text-center">
                                <a href="/login" class="text-center">I already have a membership</a>
                            </div>

                    </div>
    </form>
      <hr/>

    </div>
    <!-- /.form-box -->
  </div><!-- /.card -->
</div>
<!-- /.register-box -->
@include('auth.template.footer')
<style>
  .text-primary, .card-outline{
      color: {{ $SystemSetting ? $SystemSetting->dark_theme_color : '#008751' }} !important;
  }
</style>
