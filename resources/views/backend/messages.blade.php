@extends('backend.template.backend-master')
@section('content')
<div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Internal Communication</h1>
          </div>
        </div>
      </div>
    </div>
    <section class="content">
      <div class="container-fluid">
        <div class="card">
          <div class="card-body">
            <div id="backend-send-message">
              <send-message
                :profile-data='@json($profileData)'
                dark-theme-color="{{ $SystemSetting->dark_theme_color ?? '#008751' }}"
              ></send-message>
            </div>
          </div>
        </div>
      </div>
    </section>
</div>
@endsection
