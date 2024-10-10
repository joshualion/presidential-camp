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
    <link rel="stylesheet" href="styles.css"> <!-- Include your custom styles -->

    <!-- Custom CSS -->
    <link id="theme-stylesheet" href="css/theme-light.css" rel="stylesheet">
    <style>
        /* Custom Styles */
        body {
            background-color: #f8f9fa;
        }

        /* Navbar Styles */
        .navbar {
            background-color:#f8f9fa ; /* Success green for navbar */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand, .nav-link {
            color: #008751 !important; /* White text for navbar links */
        }

        .nav-link:hover {
            color: #008751 !important; /* Light success color on hover */
        }

        /* Sidebar Styles */
        .sidebar {
            background-color: #f8f9fa; /* Light success background for sidebar */

        }

        .img-circle {
    border-radius: 50%;
    }

    .dropdown-header {
        padding: 1rem;
    }

    .dropdown-header h6 {
        margin-bottom: 0;
    }


        /* Sidebar Styles */
         .right-sidebar {
            background-color: #f8f9fa; /* Light success background for sidebar */

        }

        .sidebar .nav-link {
            color: #008751 !important; /* Success green for sidebar links */
            padding: 2px 5px;
            border-radius: 2px;
            margin-bottom: 2px;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
           // background-color: #c3e6cb; /* Lighter green for active and hover */
            color: #155724 !important;
        }

        /* General Styling */
        .card {
            border: 1px solid #c3e6cb; /* Card border color */
        }

        /* Custom Button Styling */
        .btn-success {
            background-color: #008751 !important; /* Success button color */
            border-color: #008751 !important;
        }

        .btn-success:hover {
            background-color: #218838 !important; /* Darker success color on hover */
            border-color: #1e7e34 !important;
        }

        .btn-light:hover {
            color: #ffffff !important;
            background-color: #008751 !important;
        }
    </style>
</head>
