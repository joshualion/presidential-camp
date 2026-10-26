@extends('backend.template.backend-master')
@section('content')
<section class="content pt-3 cm-ui"><div class="container-fluid">
<h1>Roles &amp; Capabilities</h1>
<p>Access levels determine administrative routes and geography. Roles grant actions within that boundary. Individual restrictions take precedence.</p>
@if(session('message'))<div class="alert alert-success" role="status">{{ session('message') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<div class="card"><div class="card-body"><h2 class="h4">Reusable roles</h2>
@foreach($roles as $role)
<details class="mb-3 border rounded p-3"><summary>{{ $role->name }} <small>{{ $role->capabilities_managed ? 'Managed actions' : 'Legacy compatibility' }}</small></summary>
<form method="post" action="{{ route('superadmin.authorization.roles.update', $role) }}">@csrf @method('PUT')
<label for="role-{{ $role->id }}">Role name</label><input id="role-{{ $role->id }}" class="form-control mb-2" name="name" value="{{ $role->name }}" required maxlength="100">
<input type="hidden" name="permissions" value="">
<div class="row">@foreach($catalog as $permission)<div class="col-md-4"><label class="font-weight-normal"><input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked($role->permissions->contains('name', $permission))> {{ $permission }}</label></div>@endforeach</div>
<p class="small">Saving adopts explicit action permissions for users of this role. Review all actions first; unchecked actions are unavailable.</p><button class="btn btn-primary">Save role</button></form></details>
@endforeach
<details><summary>Create role</summary><form method="post" action="{{ route('superadmin.authorization.roles.store') }}">@csrf
<label for="new-role-name">Name</label><input id="new-role-name" class="form-control mb-2" name="name" required maxlength="100"><input type="hidden" name="permissions" value="">
<div class="row">@foreach($catalog as $permission)<div class="col-md-4"><label class="font-weight-normal"><input type="checkbox" name="permissions[]" value="{{ $permission }}"> {{ $permission }}</label></div>@endforeach</div>
<button class="btn btn-primary">Create role</button></form></details>
</div></div>
<div class="card"><div class="card-body"><h2 class="h4">Default roles for new users</h2><p>Only Super Admin can choose defaults. User creation by other administrators applies the configured role on the server.</p>
<form method="post" action="{{ route('superadmin.authorization.defaults') }}" class="row">@csrf
<div class="col-md-4"><label for="default-level">Access level</label><select id="default-level" name="access_level" class="form-control">@foreach($levels as $level => $label)<option value="{{ $level }}">{{ $label }} — {{ isset($defaults[$level]) ? $roles->firstWhere('id', $defaults[$level])?->name : 'Legacy default' }}</option>@endforeach</select></div>
<div class="col-md-5"><label for="default-role">Default named role</label><select id="default-role" name="role_id" class="form-control">@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select></div><div class="col-md-3 align-self-end"><button class="btn btn-primary">Save default</button></div></form>
</div></div>
<div class="card"><div class="card-body"><h2 class="h4">Existing users</h2><form method="get"><label for="user-search">Find by name or email</label><div class="input-group"><input id="user-search" name="search" value="{{ request('search') }}" class="form-control" maxlength="100"><button class="btn btn-outline-primary">Search</button></div></form>
<ul class="mt-3">@foreach($users as $member)<li><a href="{{ route('superadmin.authorization.index', ['user' => $member->uuid]) }}">{{ $member->firstname }} {{ $member->lastname }}</a> — {{ $member->email }} ({{ $member->access_level }})</li>@endforeach</ul>{{ $users->links() }}
</div></div>
@if($selectedUser)
<div class="card"><div class="card-body"><h2 class="h4">{{ $selectedUser->firstname }} {{ $selectedUser->lastname }}</h2><p>Access level: <strong>{{ $selectedUser->access_level }}</strong>. Geographic and record policies remain mandatory.</p>
<form method="post" action="{{ route('superadmin.authorization.users.update', $selectedUser) }}">@csrf @method('PUT')
<input type="hidden" name="roles" value=""><input type="hidden" name="grants" value=""><input type="hidden" name="restrictions" value="">
<fieldset><legend class="h5">Assigned roles</legend>@foreach($roles as $role)<label class="mr-3 font-weight-normal"><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($selectedUser->roles->contains('id', $role->id))> {{ $role->name }}</label>@endforeach</fieldset>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Capability</th><th>Grant</th><th>Restrict</th><th>Effective result and source</th></tr></thead><tbody>
@foreach($catalog as $permission)<tr><td>{{ $permission }}</td><td><input type="checkbox" name="grants[]" value="{{ $permission }}" aria-label="Grant {{ $permission }}" @checked($selectedUser->permissions->contains('name', $permission))></td><td><input type="checkbox" name="restrictions[]" value="{{ $permission }}" aria-label="Restrict {{ $permission }}" @checked(in_array($permission, $restrictions, true))></td><td>{{ $effective[$permission]['allowed'] ? 'Available' : 'Denied' }} — {{ $effective[$permission]['reason'] }}</td></tr>@endforeach
</tbody></table></div><p>Saving replaces the selected user's role assignments and adopts explicit capabilities. Super Admin access is tied to account type and cannot be delegated through a permission.</p><button class="btn btn-primary">Save user capabilities</button></form>
</div></div>
@endif
</div></section>
@endsection
