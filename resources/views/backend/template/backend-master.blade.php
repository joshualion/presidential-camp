                <!-- header -->
            @include('backend.template.header')
            <!-- header -->

            <!-- Navbar -->
            @include('backend.template.navbar')
            <!-- /.navbar -->
        
            <!--sidebar -->
            @include('backend.template.sidebar')
            <!-- partial -->
            
            
                 
             @yield('content')


            <!-- footer -->  
            @include('backend.template.footer')
            <!-- footer -->