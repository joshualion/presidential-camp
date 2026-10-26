                <!-- header -->
            @include('backend.template.header')
            <!-- header -->

            <!-- Navbar -->
            @include('backend.template.navbar')
            <!-- /.navbar -->
        
            <!--sidebar -->
            @include('backend.template.sidebar')
            <!-- partial -->
            
            
                 
             @include('backend.shared.update-notification')
             @yield('content')


            <!-- footer -->  
            @include('backend.template.footer')
            <!-- footer -->