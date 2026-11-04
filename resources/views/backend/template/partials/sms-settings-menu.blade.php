@if(app(\App\Services\Sms\Direct\DirectSmsAccess::class)->enabled($profileData) && app(\App\Services\Sms\SmsAddonAccess::class)->allowed(false))
<li class="nav-item {{ request()->routeIs($profileData->access_level.'.direct-sms.settings*') ? 'menu-open' : '' }}">
  <a href="#" class="nav-link"><i class="nav-icon fas fa-cogs"></i><p>Settings<i class="right fas fa-angle-left"></i></p></a>
  <ul class="nav nav-treeview">
    @include('backend.template.partials.sms-provider-menu-item')
  </ul>
</li>
@endif
