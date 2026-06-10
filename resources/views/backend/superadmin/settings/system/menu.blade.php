<ul class="nav flex-column nav-pills" id="myTab" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="general-settings-tab" href="#general-settings" role="tab">System</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="brand-settings-tab" href="#brand-settings" role="tab">Logo & Custom Colors </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" id="community-settings-tab" href="#community-settings" role="tab">Frontend Settings</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="cloud_storage-settings-tab" href="#cloud_storage-settings" role="tab">File Storage Settings</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="community-rules-tab" href="#community-rules" role="tab">Community Rules</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="social-media-tab" href="#social-media" role="tab">Social Media </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="terms-settings-tab" href="#terms-settings" role="tab">Terms & Conditions</a>
    </li>

    <li class="nav-item" >
        <a class="nav-link" id="privacy-settings-tab" href="#privacy-settings" role="tab">Privacy Policy</a>
    </li>


    <li class="nav-item">
        <a class="nav-link" id="disclaimer-settings-tab" href="#disclaimer-settings" role="tab"> Disclaimer </a>
    </li>

    @if($profileData->access_level == 'superadmin')

    <li class="nav-item">
        <a class="nav-link" id="copyright-settings-tab" href="#copyright-settings" role="tab">Copyright </a>
    </li>

    @endif

</ul>


