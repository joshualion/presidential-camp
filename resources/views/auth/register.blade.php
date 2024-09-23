

@include('auth.template.header')   
  <div class="card card-success card-outline">
    <div class="card-body ">
      <p class="login-box-msg">Register a new membership</p>

      <form action="{{ route('register') }}" method="post">   
        @csrf          
        
        <!-- Username Unique -->
        <div class="  input-group mb-3">
          <input type="text" class="form-control @error('username') is-invalid @enderror " id="username" name="username" required placeholder="Username *" autofocus="false">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
          @error('username')
              <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

          <!-- First name
        <div class=" input-group mb-3">
          <input type="text" class="form-control  @error('firstname') is-invalid @enderror" id="firstname" name="firstname" required placeholder="First Name *">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
          @error('firstname')
        <div class="invalid-feedback text-warning">{{ $message }}</div>
       @enderror
        </div>
          Last Name -->
        <!-- <div class=" input-group mb-3">
          <input type="text" class="form-control @error('lastname') is-invalid @enderror" id="lastname" name="lastname" required placeholder="Last Name *">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
          @error('lastname')
              <div class="invalid-feedback">{{ $message }}</div>
          @enderror
           
        </div> -->
        
        
        

        
          
        <!-- Email -->
          <!-- <div class=" input-group mb-3">
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="Email">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-envelope"></span>
              </div>
            </div>
            @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div> -->
         
      
        
      
        <!-- Password -->
      <div class=" input-group mb-3">
          <input type="password" class="form-control" id="password" name="password" required  placeholder="Password">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <!-- Confirm Password -->
        <div class=" input-group mb-3">
          <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required  placeholder="Confirm password">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>

             
          <!-- Phone -->
        <!-- <div class=" input-group mb-3">
          <input  class="form-control  @error('phone') is-invalid @enderror" name="phone" id="phone" type="tel" >
          <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-phone"></span>
              </div>
            </div>  
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror                          
          </div>           -->
          <!-- /.col -->
          <div class="input-group mb-3">
            <button type="submit" class="btn btn-success btn-block fa-pull-right text-center "><h5>NEXT &rarr; </h5></button>            
              
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
