@extends('backend.template.backend-master')
@section('content')
<section class="content cm-ui"><div class="container-fluid">
    @if(session('finance_status'))<div class="alert alert-success" role="status">{{ session('finance_status') }}</div>@endif
    @if($errors->any())
        <ul class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    <h2 class="h4 text-primary">Finance navigation</h2>
    <p>Choose which Finance pages are available to a role or access level. Existing capabilities control what users can do on each page. Users keep their campaign and geographic restrictions.</p>
    <form method="GET" action="{{ route('finance.settings') }}" class="mb-3">
        <label for="finance-subject">Role or access level</label>
        <select name="subject" id="finance-subject" class="form-control">
            <option value="">Select a role or access level</option>
            @foreach($subjects as $key => $label)
                <option value="{{ $key }}" @selected($subject === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-primary mt-2">Load settings</button>
    </form>
    <form method="POST" action="{{ route('finance.settings.save') }}">
        @csrf
        <input type="hidden" name="version" value="{{ $current?->id ?? 0 }}">
        @if($subject)
            <fieldset class="card card-body mb-3">
                <legend class="h5 text-primary">{{ $subjects[$subject] }}</legend>
                <input type="hidden" name="subject" value="{{ $subject }}">
                @foreach($sections as $key => $label)
                    <label class="d-block"><input type="checkbox" name="menus[]" value="{{ $key }}" @checked(in_array($key, old('menus', $menus[$subject] ?? array_keys($sections)), true))> {{ $label }}</label>
                @endforeach
            </fieldset>
        @endif
        <h2 class="h4 text-primary">Funding approvals</h2>
        <p>Set up to ten amount thresholds in kobo. Each request uses the highest threshold it meets and keeps that chain when settings change. Enter a zero threshold to cover all lower amounts. Every stage requires a different approver with the Finance approval capability and access to the request’s jurisdiction.</p>
        @foreach(range(0, min(9, count($rules))) as $index)
            @php($rule = $rules[$index] ?? [])
            <fieldset class="card card-body mb-3">
                <legend class="h5 text-primary">Approval rule {{ $index + 1 }}</legend>
                <label>Minimum amount in kobo<input name="rules[{{ $index }}][minimum_minor]" class="form-control" type="number" min="0" max="100000000000000" value="{{ old('rules.'.$index.'.minimum_minor', $rule['minimum_minor'] ?? '') }}"></label>
                @foreach(range(0, 4) as $stage)
                    <label>Stage {{ $stage + 1 }}<select class="form-control" name="rules[{{ $index }}][stages][]">
                        <option value="">Skip this stage</option>
                        @foreach($stageSubjects as $key => $label)
                            <option value="{{ $key }}" @selected(old('rules.'.$index.'.stages.'.$stage, $rule['stages'][$stage] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select></label>
                @endforeach
            </fieldset>
        @endforeach
        <button class="btn btn-primary mb-3">Save Finance settings</button>
    </form>
    @if($current)<p class="small">Policy version {{ $current->id }}, saved {{ $current->created_at }} by user {{ $current->actor_id }}.</p>@endif
    <h2 class="h4 text-primary">Finance appointments</h2>
    <p>Choose a member and an existing Finance account as the jurisdiction reference. Appointments can qualify a member for an approval stage in that jurisdiction. Give the member Finance view and approval capabilities through Roles &amp; Permissions.</p>
    <form method="POST" action="{{ route('finance.appointments.store') }}" class="card card-body mb-3">
        @csrf
        <label>Member ID<input name="user_id" type="number" min="1" class="form-control" required></label>
        <label>Reference account ID<input name="account_id" type="number" min="1" class="form-control" required></label>
        <label>Finance office<select name="office" class="form-control" required>
            @foreach($offices as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select></label>
        <label>Jurisdiction level<select name="boundary_column" class="form-control" required>
            @foreach($boundaries as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select></label>
        <label>Expires at the beginning of this date<input name="expires_at" type="date" class="form-control"></label>
        <button class="btn btn-primary">Create appointment</button>
    </form>
    <div class="table-responsive"><table class="table">
        <thead><tr><th>Member</th><th>Office</th><th>Jurisdiction</th><th>Expires</th><th>Action</th></tr></thead>
        <tbody>@forelse($appointments as $appointment)
            <tr>
                <td>{{ $appointment->user_id }}</td><td>{{ $offices[$appointment->office] }}</td>
                <td>{{ $boundaries[$appointment->boundary_column] }} {{ $appointment->boundary_id }}</td>
                <td>{{ $appointment->expires_at ?? 'Open-ended' }}</td>
                <td>@if($appointment->revoked_at) Revoked {{ $appointment->revoked_at }}
                    @else<form method="POST" action="{{ route('finance.appointments.revoke', $appointment->id) }}">@csrf<button class="btn btn-outline-danger btn-sm">Revoke</button></form>@endif
                </td>
            </tr>
        @empty<tr><td colspan="5">No Finance appointments yet.</td></tr>@endforelse</tbody>
    </table></div>
    {{ $appointments->links('pagination::bootstrap-4') }}
</div></section>
@endsection
