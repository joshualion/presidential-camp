@extends('backend.template.backend-master')
@section('content')
<section class="content pt-3 cm-ui"><div class="container-fluid"><div class="card"><div class="card-body"><h1 class="h3">Incident Evidence Settings</h1>
<p>Rules apply to new uploads. Historical evidence remains available to authorized users. Hosting upload limits also apply.</p>
@if(session('message'))<div class="alert alert-success" role="status">{{ session('message') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('superadmin.incident-evidence.update') }}">@csrf @method('PUT')
<div class="form-group"><label for="evidence-mode">Attachments allowed</label><select id="evidence-mode" name="mode" class="form-control">
@foreach(['images_and_videos' => 'Images and videos', 'images_only' => 'Images only', 'videos_only' => 'Videos only', 'none' => 'No attachments'] as $value => $label)<option value="{{ $value }}" @selected(old('mode', $rules['mode']) === $value)>{{ $label }}</option>@endforeach</select></div>
<div class="row">@foreach(['max_images' => ['Maximum images', 10], 'max_videos' => ['Maximum videos', 5], 'max_files' => ['Maximum total files', 10], 'max_image_kb' => ['Maximum image size (KB)', 10240], 'max_video_kb' => ['Maximum video size (KB)', 51200]] as $key => [$label, $maximum])<div class="col-md-6 form-group"><label for="{{ $key }}">{{ $label }}</label><input id="{{ $key }}" type="number" class="form-control" name="{{ $key }}" min="1" max="{{ $maximum }}" value="{{ old($key, $rules[$key]) }}" required></div>@endforeach</div>
<button class="btn btn-primary">Save evidence rules</button></form></div></div></div></section>
@endsection
