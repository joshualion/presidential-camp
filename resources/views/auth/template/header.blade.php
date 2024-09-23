<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>
    @if (!$systemSettings->isEmpty() )
              @foreach ($systemSettings as $setting)
                {{ $setting->system_name }}
              @endforeach
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

  <style>
     body {
      background-image: url('aso-rock.jpg');
      background-size: cover;
      background-repeat: no-repeat;
      background-attachment: fixed;
    }
    a{
      color: #008751 !important;
    }

    .btn-primary{
      background: #008751 !important;
      border:#008751 !important;
    }

    .card-title{
      text-transform:uppercase;
    }

    .navbar-bg{
    background: #008751 !important;
    }

  </style>
</head>
<body class="hold-transition register-page">


<div class="register-box">
  <div class="register-logo">
    <a href="/"><img src="{{asset('assets/dist/img/logo2.png')}}" width="35%" height="35%"/></a>
  </div>
