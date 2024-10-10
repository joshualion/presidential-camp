<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="#">
            <img src="img/logo.png" class="logo img-responsive" width="10%" height="10%" />
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="img/no_image.jpg" alt="User Image" class="img-circle" width="30" height="30" />
                        <span class="ms-2">John Doe</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li class="dropdown-header text-center">
                            <img src="img/no_image.jpg" alt="User Image" class="img-circle mb-2" width="60" height="60" />
                            <h6 class="mb-0">John Doe</h6>
                            <small>johndoe@example.com</small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">Change Password</a></li>
                        <li><a class="dropdown-item" href="#">Followers: 120</a></li>
                        <li><a class="dropdown-item" href="#">Following: 80</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#">Logout</a></li>
                    </ul>
                </li>
            </ul>
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
</style>
