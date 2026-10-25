@extends('backend.template.backend-master')
@section('content')
<section class="content pt-3 cm-ui"><div class="container-fluid"><h1 class="h3">Election incidents</h1><p>{{ $election->name }} — {{ $pollingUnit->name }}</p>
@forelse($incidents as $incident)
<article class="card mb-3"><div class="card-header"><h2 class="h5 mb-0">{{ ucfirst(str_replace('_', ' ', $incident->incident_type ?? 'Incident')) }} <span class="badge badge-secondary">{{ ucfirst($incident->severity ?? 'medium') }}</span></h2></div>
<div class="card-body"><p class="small text-muted">Reported by {{ $incident->agent?->firstname }} {{ $incident->agent?->lastname }} · {{ $incident->created_at?->format('j M Y, H:i') }} · {{ ucfirst($incident->status ?? 'open') }}</p>
<p style="white-space:pre-wrap">{{ $incident->remarks ?: 'No details provided.' }}</p>
@if($incident->polling_unit_result_id)<p class="small">Linked to polling-unit result #{{ $incident->polling_unit_result_id }}</p>@endif
<div class="row">
@foreach(['picture' => $incident->pictureEvidences, 'video' => $incident->videoEvidences] as $kind => $items)
@foreach($items as $evidence)
@php($file = app(\App\Services\IncidentEvidenceService::class)->locate($evidence))
<div class="col-sm-6 col-lg-4 mb-3" data-evidence-card><div class="border rounded p-2 h-100">
<p class="small">{{ $evidence->original_name ?: ucfirst($kind).' evidence' }}</p>
@if($file)
@php($url = route('election.evidence.show', ['kind' => $kind, 'id' => $evidence->id]))
@if($kind === 'picture')<a href="{{ $url }}" target="_blank" rel="noopener noreferrer"><img src="{{ $url }}" alt="Incident photograph" class="img-fluid rounded" loading="lazy" style="max-height:240px;object-fit:contain" data-evidence-media></a>
@elseif($file['playable'])<video controls preload="metadata" class="w-100 rounded" data-evidence-media><source src="{{ $url }}" type="{{ $file['mime'] }}">Download the evidence if your browser cannot play it.</video>
@else<p>This video format is available as a secure download.</p>@endif
<p class="small text-muted mt-2" data-evidence-error hidden>Preview unavailable. Try the secure download or contact your administrator.</p>
<p class="mt-2 mb-0"><a href="{{ $url }}?download=1">Download evidence</a></p>
@else<p class="text-muted" role="status">Evidence unavailable — the file is missing or cannot be safely opened. Contact your administrator.</p>@endif
</div></div>
@endforeach
@endforeach
</div>
@if($incident->pictureEvidences->isEmpty() && $incident->videoEvidences->isEmpty())<p class="text-muted">No attachments submitted.</p>@endif
</div></article>
@empty<div class="alert alert-info">No incidents reported for this polling unit.</div>@endforelse
</div></section>
<script>document.querySelectorAll('[data-evidence-media]').forEach(media => media.addEventListener('error', () => { media.hidden = true; const message = media.closest('[data-evidence-card]').querySelector('[data-evidence-error]'); message.hidden = false; }, true));</script>
@endsection
