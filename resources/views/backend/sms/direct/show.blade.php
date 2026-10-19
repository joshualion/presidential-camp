@extends('backend.template.backend-master')
@section('content')
<section class="content pt-3 cm-ui"><div class="container-fluid">
@if(session('message'))<div class="alert alert-info" role="status">{{ session('message') }}</div>@endif
<div class="card"><div class="card-header"><h3 class="card-title">SMS delivery report</h3></div><div class="card-body"><p>{{ $batch->provider }} · {{ $batch->created_at->format('j M Y H:i') }} · Sender {{ $batch->sender_id }}</p>
@if($full)<div class="border rounded p-3 mb-3" style="white-space:pre-wrap">{{ $batch->message }}</div>@else<div class="alert alert-info">This view contains only recipients within your oversight. The full batch content and totals are restricted.</div>@endif
<p class="text-muted">Accepted means the provider acknowledged submission. Unconfirmed means the outcome could not be established; these messages are not automatically resent.</p>
@if($profileData->access_level==='superadmin' || $batch->created_by===$profileData->id)<form method="post" action="{{ route($prefix.'sync',$batch) }}">@csrf<button class="btn btn-outline-primary mb-3">Refresh provider delivery reports</button></form>@endif
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Recipient</th><th>Name</th><th>Delivery status</th><th>Submitted</th></tr></thead><tbody>@foreach($messages as $message)<tr><td>{{ $message->phone }}</td><td>{{ $message->name??'—' }}</td><td>{{ str_replace('_',' ',$message->status) }}</td><td>{{ $message->submitted_at?->format('j M Y H:i')??'—' }}</td></tr>@endforeach</tbody></table></div>{{ $messages->links() }}
@if($full && $events->count())<h5>Audit events</h5><ul>@foreach($events as $event)<li>{{ $event->created_at }} · {{ str_replace('.',' ',$event->event) }}</li>@endforeach</ul>@endif
<a href="{{ route($prefix.'index') }}" class="btn btn-outline-secondary">Back to SMS overview</a></div></div>
</div></section>
@endsection
