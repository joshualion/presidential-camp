@if(app(\App\Services\Sms\Direct\DirectSmsAccess::class)->enabled($profileData) && app(\App\Services\Sms\SmsAddonAccess::class)->allowed())
<li class="nav-item">
  <a class="nav-link" href="{{ route($profileData->access_level.'.direct-sms.settings') }}">
    <i class="nav-icon fas fa-sms"></i><p>SMS Provider</p>
  </a>
</li>
@endif
