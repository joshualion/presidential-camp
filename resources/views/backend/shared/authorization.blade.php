@extends('backend.template.backend-master')
@section('content')
<section class="content pt-3"><div class="container-fluid">
@if(session('message'))<div class="alert alert-success" role="status">{{ session('message') }}</div>@endif
@if(isset($errors) && $errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<div class="card card-primary">
<div class="card-header"><h3 class="card-title">Roles &amp; Permission</h3><div class="card-tools">
<a class="btn btn-default btn-sm" href="{{ route('superadmin.settings.roles') }}">Manage Roles</a>
<a class="btn btn-default btn-sm" href="{{ route('superadmin.settings.addrole.permission') }}">User Roles &amp; Permissions</a>
<a class="btn btn-default btn-sm" href="{{ route('superadmin.settings.permissions') }}">Manage Permissions</a>
</div></div><div class="card-body">
@if(request('section') !== 'users' && ! $selectedUser)
@if($selectedRole || request()->boolean('create'))
<h2 class="h4">{{ $selectedRole ? 'Edit Role: '.$selectedRole->name : 'Create Role' }}</h2>
<form method="post" action="{{ $selectedRole ? route('superadmin.authorization.roles.update', $selectedRole) : route('superadmin.authorization.roles.store') }}">
@csrf @if($selectedRole) @method('PUT') @endif
<div class="form-group"><label for="role-name">Role name</label><input id="role-name" class="form-control" name="name" value="{{ old('name', $selectedRole?->name) }}" required maxlength="100"></div>
@include('backend.shared.role-permission-matrix', ['selectedPermissions' => old('permissions', $rolePermissions)])
<button class="btn btn-primary">Save Role Permissions</button>
<a class="btn btn-default" href="{{ route('superadmin.settings.rolespermission') }}">Back to Roles</a>
</form>
@else
<div class="d-flex flex-wrap justify-content-between mb-3"><h2 class="h4">All Roles Permission</h2><a class="btn btn-primary" href="{{ route('superadmin.settings.role.add') }}"><i class="fas fa-plus-square mr-1"></i>Create Role</a></div>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Role</th><th>Permissions</th><th>Action</th></tr></thead><tbody>
@foreach($roles as $role)<tr><td>{{ $role->name }}</td><td>{{ count(app(\App\Services\CampaignCapabilityService::class)->rolePermissions($role)) }} permissions</td><td><a class="btn btn-primary btn-sm" href="{{ route('superadmin.settings.role.permission.edit', encrypt($role->id)) }}"><i class="fas fa-pencil-alt mr-1"></i>Edit</a> <form class="d-inline" method="post" action="{{ route('superadmin.settings.role.delete', encrypt($role->id)) }}" onsubmit="return confirm('Delete this role and remove its assignments?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Delete</button></form></td></tr>@endforeach
</tbody></table></div>
@endif
</div></div>
<div class="card card-primary"><div class="card-header"><h3 class="card-title">Default Roles for New Users</h3></div><div class="card-body">
<form method="post" action="{{ route('superadmin.authorization.defaults') }}" class="row">@csrf
<div class="col-md-5 form-group"><label for="default-level">Administrative level</label><select id="default-level" name="access_level" class="form-control">@foreach($levels as $level => $label)<option value="{{ $level }}">{{ $label }} — {{ isset($defaults[$level]) ? $roles->firstWhere('id', $defaults[$level])?->name : 'Default role' }}</option>@endforeach</select></div>
<div class="col-md-5 form-group"><label for="default-role">Role</label><select id="default-role" name="role_id" class="form-control">@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select></div><div class="col-md-2 align-self-center"><button class="btn btn-primary">Save Default</button></div></form>
@else
<h2 class="h4">User Roles &amp; Permissions</h2><form method="get" action="{{ route('superadmin.settings.addrole.permission') }}"><label for="user-search">Find by name or email</label><div class="input-group mb-3"><input id="user-search" name="search" value="{{ request('search') }}" class="form-control" maxlength="100"><button class="btn btn-primary">Search</button></div></form>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Name</th><th>Email</th><th>Action</th></tr></thead><tbody>@foreach($users as $member)<tr><td>{{ $member->firstname }} {{ $member->lastname }}</td><td>{{ $member->email }}</td><td><a class="btn btn-primary btn-sm" href="{{ route('superadmin.settings.addrole.permission', ['user' => $member->uuid]) }}">Edit Permissions</a></td></tr>@endforeach</tbody></table></div>{{ $users->links() }}
@endif
</div></div>
@if($selectedUser)
<div class="card card-primary"><div class="card-header"><h3 class="card-title">{{ $selectedUser->firstname }} {{ $selectedUser->lastname }}</h3></div><div class="card-body">
@if($selectedUser->access_level === 'superadmin')<p>Super Admin has full system access.</p>@endif
<form method="post" action="{{ route('superadmin.authorization.users.update', $selectedUser) }}">@csrf @method('PUT')
<input type="hidden" name="roles" value=""><input type="hidden" name="grants" value=""><input type="hidden" name="restrictions" value="">
<fieldset class="mb-3"><legend class="h5">Assigned Roles</legend>@foreach($roles as $role)<label class="mr-3 font-weight-normal"><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($selectedUser->roles->contains('id', $role->id))> {{ $role->name }}</label>@endforeach</fieldset>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Permission</th><th>Grant</th><th>Restrict</th><th>Effective Access</th></tr></thead><tbody>
@foreach($userCatalog as $permission)<tr><td>{{ $permission }}</td><td><input type="checkbox" name="grants[]" value="{{ $permission }}" aria-label="Grant {{ $permission }}" @checked($selectedUser->permissions->contains('name', $permission))></td><td><input type="checkbox" name="restrictions[]" value="{{ $permission }}" aria-label="Restrict {{ $permission }}" @disabled($selectedUser->access_level === 'superadmin') @checked($selectedUser->access_level !== 'superadmin' && in_array($permission, $restrictions, true))></td><td>{{ $effective[$permission]['allowed'] ? 'Available' : 'Denied' }} — {{ $effective[$permission]['reason'] }}</td></tr>@endforeach
</tbody></table></div><button class="btn btn-primary">Save User Permissions</button></form>
</div></div>
@endif
</div></section>
@endsection
