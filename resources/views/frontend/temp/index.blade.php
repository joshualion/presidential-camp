<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Team Platform</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link id="theme-stylesheet" href="css/theme-light.css" rel="stylesheet">
    <style>
        /* Custom Styles */
        body {
            background-color: #f8f9fa;
        }

        /* Navbar Styles */
        .navbar {
            background-color: #28a745; /* Success green for navbar */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand, .nav-link {
            color: #ffffff !important; /* White text for navbar links */
        }

        .nav-link:hover {
            color: #c3e6cb !important; /* Light success color on hover */
        }

        /* Sidebar Styles */
        .sidebar {
            background-color: #e9f7ef; /* Light success background for sidebar */
            height: 100vh;
            padding-top: 1rem;
        }

        .sidebar .nav-link {
            color: #28a745 !important; /* Success green for sidebar links */
            padding: 10px 20px;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background-color: #c3e6cb; /* Lighter green for active and hover */
            color: #155724 !important;
        }

        /* General Styling */
        .card {
            border: 1px solid #c3e6cb; /* Card border color */
        }

        /* Custom Button Styling */
        .btn-success {
            background-color: #28a745 !important; /* Success button color */
            border-color: #28a745 !important;
        }

        .btn-success:hover {
            background-color: #218838 !important; /* Darker success color on hover */
            border-color: #1e7e34 !important;
        }

        .btn-light:hover {
            color: #ffffff !important;
            background-color: #28a745 !important;
        }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Campaign Team</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Posts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Comments</a>
                    </li>
                    <!-- User Profile Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i> Profile
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#">My Profile</a></li>
                            <li><a class="dropdown-item" href="#">Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#">Logout</a></li>
                        </ul>
                    </li>
                    <!-- Theme Switcher -->
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="theme-switch"><i class="fas fa-adjust"></i></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Layout -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar for Hierarchy -->
            <nav class="col-md-2 d-none d-md-block sidebar">
                <div class="sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link active" href="#">National</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Region</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">State</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">LGA</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Ward</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Polling Unit</a></li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newPostModal">New Post</button>
                </div>

                <!-- Event Section -->
                <div class="mb-4">
                    <h4>Upcoming Events</h4>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Election Rally</h5>
                            <p class="card-text">Join us for the campaign rally at the state headquarters on 20th October.</p>
                            <a href="#" class="btn btn-success">View Details</a>
                        </div>
                    </div>
                </div>

                <!-- Post Feed Section -->
                <div id="post-feed">
                    <h4>Recent Posts</h4>
                    <!-- Post Card -->
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <img src="https://via.placeholder.com/40" class="rounded-circle me-2" alt="User">
                                <span>User Name</span>
                            </div>
                            <small class="text-muted">2 hrs ago</small>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">Campaign Updates</h5>
                            <p class="card-text">We are seeing great support across the regions, let’s keep pushing forward!</p>
                            <img src="https://via.placeholder.com/600x200" class="img-fluid" alt="Post image">
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <div>
                                <a href="#" class="btn btn-sm btn-light"><i class="far fa-thumbs-up"></i> Like</a>
                                <a href="#" class="btn btn-sm btn-light"><i class="far fa-thumbs-down"></i> Dislike</a>
                            </div>
                            <a href="#" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#commentModal">Comment</a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- New Post Modal -->
    <div class="modal fade" id="newPostModal" tabindex="-1" aria-labelledby="newPostModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newPostModalLabel">Create a New Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="postTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="postTitle" placeholder="Post title">
                        </div>
                        <div class="mb-3">
                            <label for="postContent" class="form-label">Content</label>
                            <textarea class="form-control" id="postContent" rows="3" placeholder="What's on your mind?"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="postImage" class="form-label">Image</label>
                            <input class="form-control" type="file" id="postImage">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success">Post</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Comment Modal -->
    <div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="commentModalLabel">Add a Comment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <textarea class="form-control" rows="3" placeholder="Write a comment..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success">Comment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Theme Switcher Script -->
    <script src="js/theme-switcher.js"></script>
</body>
</html>
