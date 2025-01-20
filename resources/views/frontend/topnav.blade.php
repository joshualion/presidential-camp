<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <div class="row w-100 align-items-center">
            <!-- Logo: 5 columns -->
            <div class="col-3 col-lg-3 d-flex align-items-center">
                <a class="navbar-brand" href="/">
                    <img src="{{ !empty($SystemSetting->logo) ? url('uploads/system_images/'.$SystemSetting->logo) : url('uploads/system_images/logo.png') }}" class="logo img-responsive" @desktop() width="50%" @enddesktop() @handheld() width="100%" @endhandheld() height="auto" />
                </a>
            </div>

            <!-- Search Form: 10 columns -->
            <div class="col-6 col-lg-6">
                <form class="d-flex " role="search">
                    <input class="form-control me-2 w-100 styled-search-form" type="search" placeholder="Search" aria-label="Search">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            @desktop()
            <!-- Profile Dropdown: 5 columns -->
            <div class="col-3 col-lg-3 d-flex justify-content-end">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{(!empty($profileData->photo)) ? url('uploads/member_images/'.$profileData->photo) : url('uploads/no_image.jpg')}}" class="img-circle" alt="profile picture" width="40" height="40">
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end profile-nav text-white" aria-labelledby="navbarDropdown">
                            <li class="dropdown-header text-center">
                                <img src="{{(!empty($profileData->photo)) ? url('uploads/member_images/'.$profileData->photo) : url('uploads/no_image.jpg')}}" alt="User Image" class="img-circle mb-2" width="60" height="60" />
                                <h6 class="mb-0">{{$profileData->firstname}} {{$profileData->lastname}}</h6>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{route($profileData->access_level.'.dashboard')}}"> <i class="fas fa-laptop nav-icon"></i> Dashboard</a></li>
                            <li><a class="dropdown-item" href="#"> <i class="fas fa-users nav-icon"></i> Followers: {{$followersCount}}</a></li>
                            <li><a class="dropdown-item" href="#"> <i class="fas fa-biking nav-icon"></i> Following: {{$followingCount}}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-dark" href="{{route($profileData->access_level.'.logout')}}">
                                    <i class="fas fa-power-off nav-icon"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            @enddesktop()
        </div>
    </div>
</nav>




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
