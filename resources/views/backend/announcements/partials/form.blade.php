@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="alert alert-info">
    <strong>Audience:</strong> {{ $scopeLabel }}. Your publishing scope is fixed by your administrator account.
</div>

<div class="form-group">
    <label for="title">Title</label>
    <input id="title" name="title" class="form-control" maxlength="255" required value="{{ old('title', $announcement->title ?? '') }}">
</div>

<div class="form-group">
    <label for="message">Announcement</label>
    <textarea id="message" name="message" class="form-control" rows="10" maxlength="20000" required>{{ old('message', $announcement->message ?? '') }}</textarea>
</div>

<div class="row">
    <div class="col-md-4 form-group">
        <label for="priority">Priority</label>
        <select id="priority" name="priority" class="form-control" required>
            @foreach([0 => 'Low', 1 => 'Normal', 2 => 'High', 3 => 'Urgent'] as $value => $label)
                <option value="{{ $value }}" @selected((int) old('priority', $announcement->priority ?? 1) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 form-group">
        <label for="published_at">Publish at</label>
        <input id="published_at" name="published_at" type="datetime-local" class="form-control"
               value="{{ old('published_at', isset($announcement) && $announcement->published_at ? $announcement->published_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-md-4 form-group">
        <label for="expires_at">Expires at <small class="text-muted">(optional)</small></label>
        <input id="expires_at" name="expires_at" type="datetime-local" class="form-control"
               value="{{ old('expires_at', isset($announcement) && $announcement->expires_at ? $announcement->expires_at->format('Y-m-d\TH:i') : '') }}">
    </div>
</div>

<input type="hidden" name="is_active" value="0">
<div class="custom-control custom-switch mb-3">
    <input id="is_active" name="is_active" type="checkbox" class="custom-control-input" value="1"
           @checked((bool) old('is_active', $announcement->is_active ?? true))>
    <label for="is_active" class="custom-control-label">Active / publish when scheduled</label>
</div>
