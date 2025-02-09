@desktop
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <!-- Toggle Button for Small Screens -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <div class="row w-100 align-items-center">
                <!-- Logo: 3 columns on large screens, 4 on medium and smaller screens -->
                <div class="col-4 col-lg-3 d-flex align-items-center">
                    <a class="navbar-brand" href="{{route('timeline')}}" style="width: 100px; height: 50px; overflow: hidden;">
                        <img 
                            src="{{ !empty($SystemSetting->logo) ? url('uploads/system_images/'.$SystemSetting->logo) : url('uploads/system_images/logo.png') }}" 
                            class="logo img-responsive embossed-image" 
                            style="max-width: 100%; height: auto;" 
                        />
                    </a>
                </div>

                <!-- Search Form: 6 columns on large screens, 8 on medium screens -->
                <div class="col-8 col-lg-6">
                    <form class="d-flex" role="search">
                        <input class="form-control me-2 w-100 styled-search-form" type="search" placeholder="Search" aria-label="Search">
                        <button class="btn btn-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

               
              <!-- Notification, Message, and Logout Icons -->
                <div class="col-12 col-lg-3 d-flex justify-content-end mt-3 mt-lg-0">
                    <ul class="navbar-nav ms-auto d-flex flex-row align-items-center gap-3" style="list-style: none; margin: 0; padding: 0;">
                        
                        <!-- Home Icon -->
                        <li class="nav-item position-relative">
                            <a class="nav-link p-2 rounded-circle d-flex justify-content-center align-items-center shadow" href="/community/feed" title="Home" style="background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }}; width: 35px; height: 35px;">
                                <i class="fas fa-home" style="font-size: 1.2rem; color: #f9f9f9; "></i>
                            </a>
                            
                        </li>
                        
                        <!-- Notification Icon -->
                        <li class="nav-item position-relative">
                            <a class="nav-link p-2 rounded-circle d-flex justify-content-center align-items-center shadow" href="#" title="Notifications" style="background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }}; width: 35px; height: 35px;">
                                <i class="fas fa-bell" style="font-size: 1.2rem; color: #f9f9f9; "></i>
                            </a>
                            <!-- Optional Badge -->
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning" style="font-size: 0.75rem;">
                                3
                            </span>
                        </li>

                        <!-- Message Icon -->
                        <li class="nav-item position-relative">
                            <a class="nav-link p-2 rounded-circle d-flex justify-content-center align-items-center shadow" href="#" title="Messages" style="background-color:  {{ $SystemSetting->dark_theme_color ?? '#008751' }}; width: 35px; height: 35px;">
                                <i class="fas fa-comment" style="font-size: 1.2rem; color: #f9f9f9;"></i>
                            </a>
                            <!-- Optional Badge -->
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.75rem;">
                                5
                            </span>
                        </li>

                        <!-- Logout Icon -->
                        <li class="nav-item">
                            <a class="nav-link p-2 rounded-circle d-flex justify-content-center align-items-center shadow" href="{{route($profileData->access_level.'.logout')}}" title="Logout" style="background-color:  {{ $SystemSetting->dark_theme_color ?? '#008751' }}; width: 35px; height: 35px;">
                                <i class="fas fa-power-off" style="font-size: 1.2rem; color:#f9f9f9; "></i>
                            </a>
                        </li>
                    </ul>
                </div>
             

            </div>
        </div>
    </div>
</nav>
@enddesktop()

@mobile()

<div class="container mb-5" style="padding-top: 70px;">
        @include('frontend.mobile-menu')
</div>

@endmobile()




<!-- Include Bootstrap JS and Popper.js -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
<style>
    /* Navbar Styling */
    /* .navbar {
        background-color: #008751 !important; /* Your theme color */
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Add a subtle shadow */
    } */

    .navbar-brand img {
        border-radius: 50%; /* Round logo */
    }

    .navbar .nav-link {
        color: #008751 !important; /* White text for navbar links */
    }

    .navbar .nav-link:hover {
        color: #c3e6cb !important; /* Lighter hover color */
    }

    /* Dropdown Styling */
    .dropdown-menu {
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); /* Add shadow for depth */
    }

    .dropdown-header img {
        border-radius: 50%; /* Round profile image */
        border: 2px solid #008751; /* Border with theme color */
    }

    .dropdown-header h6 {
        color: #008751; /* Theme color for name */
    }

    .dropdown-item {
        font-size: 14px;
        padding: 10px 20px;
    }

    .dropdown-item:hover {
        background-color: #f1f1f1; /* Subtle hover background */
    }

    .dropdown-divider {
        border-color: #008751; /* Theme-colored divider */
    }

    /* User Image in Navbar */
    .img-circle {
        border-radius: 50%;
        border: 2px solid #ffffff; /* Add a white border to user image */
    }

    /* Hover effect for dropdown toggle */
    .dropdown-toggle:hover {
        color: #c3e6cb !important;
    }

    .styled-search-form {
            width: 100%; /* Adjust width as needed */
            max-width: 100%; /* Adjust max width */
            padding: 10px 15px; /* Add padding for better appearance */
            border: 1px solid #ccc; /* Border to mimic an input box */
            border-radius: 12.5px; /* Rounded edges */
            font-size: 16px; /* Adjust font size */
            font-family: Arial, sans-serif; /* Consistent font styling */
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1); /* Slight inner shadow for depth */
          
            outline: none; /* Remove default outline */
            transition: border-color 0.3s ease, box-shadow 0.3s ease; /* Smooth transition for focus */
        }
</style>
