@extends('backend.template.backend-master')

@section('content')
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">Edit Announcement</h3></div>
    <form method="POST" action="{{ route($profileData->access_level.'.announcements.update', $announcement) }}">
        @csrf
        @method('PUT')
        <div class="card-body">@include('backend.announcements.partials.form')</div>
        <div class="card-footer">
            <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Update Announcement</button>
            <a class="btn btn-default" href="{{ route($profileData->access_level.'.announcements.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
