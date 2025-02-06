<body class="d-flex flex-column min-vh-100" >

    <!-- Include Header -->
    @include('frontend.header')

    <!-- Include Navbar -->
    @include('frontend.topnav')

    <div class="container-fluid flex-grow-3">
        <div class="row">
            <div class="col-lg-12" >
                <div class="container mt-4">
                    <div class="row">
                
                              <!-- Include Left Sidebar  -->
                            @desktop()
                            @if(Route::currentRouteName() === "timeline")
                                <div class="col-md-3 sidebar">
                                
                                        @include('frontend.sidebar') 
                                    
                                </div>
                            @endif
                            @enddesktop()
                            <!-- Include Left Sidebar  -->


                            <!-- Yield for content -->
                            @yield('content')
                            <!-- Yield for content -->

                
                 
                 
                            <!-- Include Right Sidebar -->
                            @if(Route::currentRouteName() === "timeline")
                            <div class="col-md-3 sidebar">
                                  @include('frontend.right-sidebar')
                               
                            </div>
                            @endif
                            <!-- Include Right Sidebar -->
                
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    @include('frontend.footer')


</body>
</html>

                 