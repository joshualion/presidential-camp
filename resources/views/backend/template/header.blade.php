<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>
    @if($SystemSetting)
    {{$pageTitle}} - {{$SystemSetting->system_name}}
    @else
    Political Campaign & Election Management Solution
    @endif
  </title>
  @vite(['resources/js/app.js'])

  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{asset('assets/plugins/fontawesome-free/css/all.min.css')}}" />
  <!-- Ionicons -->
  <link rel="stylesheet" href="{{asset('assets/plugins/ionicons/ionicons.min.css')}}" />
  <!-- IntelInput Phone -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/css/intlTelInput.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="{{asset('assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css')}}" />
  <!-- iCheck -->
  <link rel="stylesheet" href="{{asset('assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css')}}" />
  <!-- JQVMap -->
  <link rel="stylesheet" href="{{asset('assets/plugins/jqvmap/jqvmap.min.css')}}" />
  <!-- Theme style -->
  <link rel="stylesheet" href="{{asset('assets/dist/css/adminlte.min.css')}}" />
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="{{asset('assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css')}}" />
  <!-- Daterange picker -->
  <link rel="stylesheet" href="{{asset('assets/plugins/daterangepicker/daterangepicker.css')}}" />
  <!-- summernote -->
  <link rel="stylesheet" href="{{asset('assets/plugins/summernote/summernote-bs4.min.css')}}" />

      <!-- Include Leaflet CSS and JS -->
      <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />



  <!-- ChartJS CSS -->
  <link rel="stylesheet" href="{{asset('assets/plugins/chart.js/Chart.min.css')}}" />

   <!-- DataTables -->
  <link rel="stylesheet" href="{{asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css')}}">
  <link rel="stylesheet" href="{{asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css')}}">
  <link rel="stylesheet" href="{{asset('assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css')}}">

  <!-- SweetAlerts -->
  <link rel="stylesheet" href="{{asset('assets/plugins/sweetalert2/sweetalert2.min.css')}}" />


  <meta name="csrf-token" content="content">
   <meta name="csrf-token" content="{{ csrf_token() }}">

@php
    if (!empty($SystemSetting->favicon)) {
        $favicon = asset('uploads/system_images/' . $SystemSetting->favicon);
    } else {
        $favicon = null;
    }
@endphp

@if($favicon)
    <link rel="icon" type="image/png" href="{{ asset($favicon) }}">
    <link rel="apple-touch-icon" href="{{ asset($favicon) }}">
@else
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
@endif


<link rel="manifest" href="{{asset('/site.webmanifest')}}">

<!-- Toaster CSS -->
<link rel="stylesheet" type="text/css" href="{{asset('assets/plugins/toastr/toastr.min.css')}}" >

  <style>

    @php
      // Derive a readable foreground for the configured brand colour.
      $brandHex = trim((string) ($SystemSetting->dark_theme_color ?? '#008751'));
      if (preg_match('/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/D', $brandHex)) {
          $brandHex = '#'.ltrim($brandHex, '#');
          if (strlen($brandHex) === 4) {
              $brandHex = '#'.$brandHex[1].$brandHex[1].$brandHex[2].$brandHex[2].$brandHex[3].$brandHex[3];
          }
      } else {
          $brandHex = '#008751';
      }
      $channels = array_map('hexdec', str_split(substr($brandHex, 1), 2));
      $linear = array_map(static function ($channel) {
          $value = $channel / 255;
          return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
      }, $channels);
      $luminance = 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
      $brandForeground = (1.05 / ($luminance + 0.05)) >= (($luminance + 0.05) / 0.05) ? '#ffffff' : '#000000';
    @endphp
    :root {
      --cm-primary: {{ $brandHex }};
      --cm-primary-foreground: {{ $brandForeground }};
    }
    .main-header.navbar-white .nav-link,
    .main-header.navbar-white .btn-navbar,
    .page-item:not(.active) .page-link,
    .nav-tabs .nav-link:not(.active),
    .content-wrapper .text-primary { color: var(--cm-primary) !important; }
    /* Override the general link rule on dashboard widgets, including hover/focus. */
    .content-wrapper .small-box:not(.bg-white):not(.bg-light) a,
    .content-wrapper .info-box:not(.bg-white):not(.bg-light) a { color: #fff !important; }
    .content-wrapper .small-box.bg-white a,
    .content-wrapper .small-box.bg-light a,
    .content-wrapper .info-box.bg-white a,
    .content-wrapper .info-box.bg-light a,
    .content-wrapper .info-box:not([class*="bg-"]) a { color: var(--cm-primary) !important; }
    h1{
      color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
    }

    .os-scrollbar{
     background-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;

    }

    a:not(.btn):not(.nav-link):not(.page-link){
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
      color: var(--cm-primary-foreground) !important;
      background-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
      border-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
    }
    .dataTables_paginate .paginate_button:hover {
      color: var(--cm-primary-foreground) !important;
      background-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
      border-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
    }

    /* Keep toolbar layout separate from the individual button appearance. */
    .dt-buttons {
      text-align: center;
      margin-left: 15%;
    }
    .datable-print-buttons {
      background-color: #fafafa !important;
      color: #243b30 !important;
      border: 1px solid var(--cm-primary) !important;
      box-shadow: none;
    }

    .btn-primary{
      background: var(--cm-primary) !important;
      border: 1px solid var(--cm-primary) !important;
      color: var(--cm-primary-foreground) !important;

    }

    .card-title{
      text-transform:uppercase;
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
      color: var(--cm-primary-foreground) !important;
      border-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;
      background-color: {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important;

    }

    .nav-pills .nav-link:hover {
        color: var(--cm-primary-foreground) !important;
        background-color:  {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important; /* Darker green for hover */

    }

    .content-wrapper .row > [class*="col-"] > .small-box {
      display: flex;
      flex-direction: column;
      height: calc(100% - 20px);
      min-height: 145px;
      width: 100%;
    }

    .content-wrapper .row > [class*="col-"] > .small-box > .inner {
      flex: 1 1 auto;
    }

    .content-wrapper .row > [class*="col-"] > .small-box > .small-box-footer {
      margin-top: auto;
    }

    .content-wrapper .row > [class*="col-"] > .small-box .inner p {
      overflow-wrap: anywhere;
    }

</style>
   @livewireStyles
<link rel="stylesheet" href="{{ asset('assets/dist/css/campaign-ui.css') }}?v=1.1.1">
@stack('styles')
@include('shared.password-visibility')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<script>
  window.requireBankDetails = {{ ($SystemSetting->require_bank_details ?? false) ? 'true' : 'false' }};
  if (!window.requireBankDetails) {
    document.addEventListener('DOMContentLoaded', function () {
      ['bank', 'bank_account_number'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el && el.closest('.form-group')) {
          el.closest('.form-group').style.display = 'none';
        }
      });
    });
  }
</script>
<div class="wrapper">

  <!-- Preloader -->
  @desktop()
  {{-- <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="{{ !empty($SystemSetting->logo) ? url('uploads/system_images/'.$SystemSetting->logo) : url('logo.png') }}" alt="Logo" height="15%" width="15%">
  </div> --}}
  @enddesktop()
  @mobile()
  {{-- <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="{{ !empty($SystemSetting->favicon) ? url('uploads/system_images/'.$SystemSetting->favicon) : url('uploads/system_images/favicon.png') }}" alt="Logo" height="15%" width="15%">
  </div> --}}
  @endmobile()

