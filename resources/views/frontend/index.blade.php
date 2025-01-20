
<body class="d-flex flex-column min-vh-100">

    <!-- Include Header -->
    @include('frontend.header')

    <!-- Include Navbar -->
    @include('frontend.topnav')

    
    <div class="container-fluid flex-grow-3" >
        <div class="row">


            <div class="col-lg-12">                
                <!-- Include New Post -->
                @include('frontend.createPost')                
                  
            </div>
        </div>
    </div>
    

     <!-- Vue Control Area -->
    <div class="container-fluid flex-grow-3" id="app" >
        <div class="row">
            

            <div class="col-lg-12">                
                <!-- Include New Post -->
                @include('frontend.followers')                
                  
            </div>
        </div>
    </div>
     <!-- Vue Control Area -->

    <!-- Include Footer -->
    @include('frontend.footer')
