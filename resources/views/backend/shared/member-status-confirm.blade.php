@extends('backend.template.backend-master')
@section('content')
<section class="content pt-3"><div class="container-fluid"><div class="card"><div class="card-body"><h1 class="h4">Confirm account status change</h1><p>{{ $member->firstname }} {{ $member->lastname }} — {{ $member->status === 'active' ? 'Suspend account?' : 'Activate account?' }}</p>
<form method="post" action="{{ route($profileData->access_level.'.member.suspend', $member->uuid) }}">@csrf<button class="btn btn-warning">Confirm change</button></form></div></div></div></section>
@endsection
