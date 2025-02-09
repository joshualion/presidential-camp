<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @if($SystemSetting)
        {{$pageTitle}} - {{$SystemSetting->system_name}}
        @else
        Political Campaign & Election Management Solution
        @endif
    </title>
     @vite(['resources/js/app.js'])

    <!-- Bootstrap 5 CSS -->
    <link id="bootstrap-css" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link id="fontawesome-css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

      <!-- SweetAlerts -->
  <link rel="stylesheet" href="{{asset('assets/plugins/sweetalert2/sweetalert2.min.css')}}" />

    <!-- Custom CSS -->    
    <link rel="stylesheet" href="{{asset('frontend/css/style.css')}}"> 
    <link id="theme-stylesheet" href="{{asset('frontend/css/theme-light.css')}}" rel="stylesheet">
    <style>
    
    html, body {
  height: 100%;
  margin: 0;
  overflow-y: auto; /* Ensure the scrollbar is for the entire page */
}

  .btn-xs{
    padding: 0.25rem 0.5rem; /* Adjust padding */
    font-size: 0.5rem; /* Smaller font size */
    line-height: 1; /* Compact height */
    border-radius: 0.2rem; /* Slightly smaller rounded corners */
  }

  /* SweetAlert Styling */
  .swal2-popup {
    font-family: 'Arial', sans-serif; /* Ensure it matches your theme */
    border-radius: 8px;
  }
  
  .swal2-title {
    color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important; /* Adjust title color */
  }
  
  .swal2-confirm {
    background-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important; /* Your theme’s primary button color */
    color: white !important;
    border-radius: 6px;
    padding: 8px 16px;
  }
  
  .swal2-cancel {
    background-color: #dc3545 !important; /* Your theme’s danger color */
    color: white !important;
    border-radius: 6px;
    padding: 8px 16px;
  }
  
  .swal2-confirm:hover {
    background-color:{{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
    color: {{ $SystemSetting->light_theme_color ?? ' #008751'}} !important;
  }
  
  .swal2-cancel:hover {
    background-color: #b52b32 !important;
  }
       
      /* Custom Styles */
    .navbar-nav .nav-link {
        transition: transform 0.3s, box-shadow 0.3s;
    }

   .navbar-nav .nav-link:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }


        
    h1{
      color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
    }

    .os-scrollbar{
     background-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
      
    }

    .embossed-image {
            position: relative;
            display: inline-block;
            filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.9)) 
                    drop-shadow(-2px -2px 4px rgba(255, 255, 255, 0.1));
            /* Adjust these values for different embossing effects */
        }

        

     .bg-primary{
     background-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
     
    }

    h5 {
      color: {{ $SystemSetting->dark_theme_color ?? '#008751' }} !important;
    }

    a{
      color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
    }

    .sidebar-light-primary{
      color: {{ $SystemSetting->light_theme_color ?? '#dff1ea'}} !important;
    }

    /* Specifically targeting the card-outline */
    .card.card-primary  .card-header{
    background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }} !important;
    border-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }} !important;
   }


    .page-item.active .page-link{
      color: #fff !important;
      background-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
      border-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
    }

    .username{
    color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
 }

    .dataTables_paginate .paginate_button:hover {
      color: #fff !important;
      background-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
      border-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
    }

    .datable-print-buttons,dt-buttons{
    text-align: center !important;
    margin-left: 15% !important;
    background: #ccc !important;
    color: #fff;
    background-color: #fafafa !important;
    border-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
    box-shadow: none;
    }

    .btn-primary{
      background: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
      border: {{ $SystemSetting->dark_theme_color ?? '#008751'}} !important;

    }

    .card-title{
      text-transform:uppercase;
      color: #c3e6cb;
    }

    .profile-sidebar-text{
        font-size:14px !important;
        color: {{ $SystemSetting->dark_theme_color ?? '#008751'}} !important;        
      
    }

    .navbar-bg{
    background: {{ $SystemSetting->dark_theme_color ?? '#008751'}} !important;
    }


    /* Hide the default file input */
    #image {
      display: none;
    }

    /* Style the custom button */
    .custom-file-upload {
      border: 1px solid #ccc;
      display: inline-block;
      padding: 6px 12px;
      cursor: pointer;
    }

    /* Style the chosen file name display */
    .file-name {
      margin-top: 4px;
      display: inline-block;
    }

    .nav-pills {
      color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
      border-color: {{ $SystemSetting->dark_theme_color ?? ' #dff1ea'}} !important;
      background-color: {{ $SystemSetting->light_theme_color ?? ' #dff1ea'}} !important;

    }

    .nav-pills .nav-link.active {
      color: {{ $SystemSetting->light_theme_color ?? ' #008751'}} !important;
      border-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
      background-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;

    }

    .nav-pills .nav-link:hover {
        color: {{ $SystemSetting->light_theme_color ?? ' #dff1ea'}} !important;
        background-color:  {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important; /* Darker green for hover */

    }
        body {
            background-color: #f8f9fa;
        }

         /* Specifically targeting the card-outline */
        .card .card-primary  .card-header{
            background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }} !important;
            border-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }} !important;
        }

        .profile-nav, a{
            text-transform: capitalize !important;
            color: {{ $SystemSetting->dark_theme_color ?? '#008751' }} !important;
            
        }
        

        /* Navbar Styles */      
        .navbar {
            background-color:#f8f9fa ; /* Success green for navbar */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand, .nav-link {
            color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important; /* White text for navbar links */
        }

        .nav-link:hover {
            color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important; /* Light success color on hover */
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
            color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important; /* Success green for sidebar links */
            padding: 2px 5px;
            border-radius: 2px;
            margin-bottom: 2px;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background-color: #c3e6cb; /* Lighter green for active and hover */
            color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important; 
        }

        /* General Styling */
        .card {
            border: {{ $SystemSetting->light_theme_color ?? ' #fafaf1'}} !important; /* Card border color */
        }

        /* Custom Button Styling */
        .btn-success {
            background-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important; /* Success button color */
            border-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
        }

        .btn-success:hover {
            background-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important; /* Darker success color on hover */
            border-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
        }

        .btn-light:hover {
            color: #ffffff !important;
            background-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
        }

        .logo {
          max-width: 100%;
          height: auto; /* Maintain aspect ratio */
          display: block; /* Remove any extra space below the image */
        }

    </style>
</head>
