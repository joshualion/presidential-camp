<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>
    @if ($SystemSetting)
        {{$SystemSetting->system_name}}
    @else
    Political Campaign & Election Management Solution
    @endif
</title>

   <!-- IntelInput Phone -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/css/intlTelInput.css">

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{asset('assets/plugins/fontawesome-free/css/all.min.css')}}">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="{{asset('assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css')}}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{asset('assets/dist/css/adminlte.min.css')}}">
  <!-- Toaster CSS -->
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" >


{{-- Favicon --}}

<link rel="apple-touch-icon" sizes="180x180" href="{{asset('/apple-touch-icon.png')}}">
<link rel="icon" type="image/png" sizes="32x32" href="{{asset('/favicon-32x32.png')}}">
<link rel="icon" type="image/png" sizes="16x16" href="{{asset('/favicon-16x16.png')}}">
<link rel="manifest" href="{{asset('/site.webmanifest')}}">
  <style>
     body {
        background-image: url("{{ asset('uploads/system_images/' . ($SystemSetting ? $SystemSetting->login_page_background : 'aso-rock.jpg')) }}");
      background-size: cover;
      background-repeat: no-repeat;
      background-attachment: fixed;
    }
    a{
      color: {{ $SystemSetting ? $SystemSetting->dark_theme_color : '#165828' }} !important;
    }

    .btn-primary{
      background: {{ $SystemSetting ? $SystemSetting->dark_theme_color : '#165828' }} !important;
      border:{{ $SystemSetting ? $SystemSetting->dark_theme_color : '#165828' }} !important;
    }

    .card-title{
      text-transform:uppercase;
    }

    .navbar-bg{
    background:{{ $SystemSetting ? $SystemSetting->dark_theme_color : '#165828' }} !important;
    }

  </style>
</head>
<body class="hold-transition register-page">


<div class="register-box">
  <div class="register-logo">
    <a href="/">
        <img src="{{ !empty($SystemSetting->logo) ? url('uploads/system_images/'.$SystemSetting->logo) : url('logo.png') }}" alt="Logo" class="brand-image" width="40%" height="40%" >

    </a>

    </div>
