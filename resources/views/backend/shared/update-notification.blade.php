@if(auth()->user()?->access_level === 'superadmin')
@php($notificationUpdate = app(\App\Services\OfficialUpdateService::class)->status())
@if(!empty($notificationUpdate['available']) && !empty($notificationUpdate['verified']))
<div class="container-fluid pt-3" data-update-notification><div class="alert alert-danger alert-dismissible mb-0" role="status" aria-live="polite">
<i class="fas fa-cloud-download-alt mr-2" aria-hidden="true"></i><strong>Update available: {{ $notificationUpdate['latest_version'] }}</strong>
<a class="btn btn-danger btn-sm ml-2" href="{{ route('superadmin.updates') }}">Review Update</a>
<button type="button" class="close" data-dismiss="alert" aria-label="Dismiss update notification"><span aria-hidden="true">&times;</span></button>
</div></div>
@endif
@endif
