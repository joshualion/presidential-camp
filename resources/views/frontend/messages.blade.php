@extends('frontend.layouts.app')

@section('content')
<div class="container py-4">
    <div id="app">
        <send-message
            :profile-data='@json($profileData)'
            dark-theme-color="{{ $SystemSetting->dark_theme_color ?? '#008751' }}"
        ></send-message>
    </div>
</div>
@endsection
