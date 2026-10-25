@php($evidenceRules = app(\App\Services\IncidentEvidenceService::class)->rules())
<fieldset class="border rounded p-3 mb-3" data-evidence-inputs>
<legend class="h5">Evidence <small class="text-muted">optional</small></legend>
<p class="small text-muted">Maximum {{ $evidenceRules['max_files'] }} files in total. Attachments are saved privately and available only to authorized users.</p>
@if(in_array($evidenceRules['mode'], ['images_and_videos', 'images_only'], true))
<div class="form-group"><label for="incident-pictures">Photographs — up to {{ $evidenceRules['max_images'] }}, {{ number_format($evidenceRules['max_image_kb'] / 1024, 1) }} MB each</label>
<input id="incident-pictures" type="file" name="pictures[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple></div>
@endif
@if(in_array($evidenceRules['mode'], ['images_and_videos', 'videos_only'], true))
<div class="form-group"><label for="incident-videos">Videos — up to {{ $evidenceRules['max_videos'] }}, {{ number_format($evidenceRules['max_video_kb'] / 1024, 1) }} MB each</label>
<input id="incident-videos" type="file" name="videos[]" class="form-control" accept="video/mp4,video/webm" multiple><small class="form-text text-muted">MP4 or WebM. Playback depends on your browser's codec support; a secure download is also available.</small></div>
@endif
@if($evidenceRules['mode'] === 'none')<p role="status">New attachments are disabled. You can still submit an incident report and view evidence submitted previously.</p>@endif
<div class="row" data-evidence-preview aria-live="polite"></div>
</fieldset>
<script>
document.querySelectorAll('[data-evidence-inputs]').forEach(group => {
    const preview = group.querySelector('[data-evidence-preview]');
    let urls = [];
    const clean = () => { urls.forEach(url => URL.revokeObjectURL(url)); urls = []; preview.replaceChildren(); };
    group.querySelectorAll('input[type=file]').forEach(input => input.addEventListener('change', () => {
        clean();
        group.querySelectorAll('input[type=file]').forEach(field => Array.from(field.files).forEach(file => {
            const card = document.createElement('div'); card.className = 'col-sm-6 col-lg-4 mb-2';
            const name = document.createElement('p'); name.className = 'small text-break'; name.textContent = file.name; card.append(name);
            if (['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                const url = URL.createObjectURL(file); urls.push(url);
                const img = document.createElement('img'); img.src = url; img.alt = 'Selected evidence preview'; img.className = 'img-thumbnail'; img.style.maxHeight = '180px'; card.append(img);
            } else if (['video/mp4', 'video/webm'].includes(file.type)) {
                const url = URL.createObjectURL(file); urls.push(url);
                const video = document.createElement('video'); video.src = url; video.controls = true; video.preload = 'metadata'; video.className = 'w-100'; card.append(video);
            }
            preview.append(card);
        }));
    }));
    window.addEventListener('pagehide', clean, {once:true});
});
</script>
