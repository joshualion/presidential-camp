<!-- Local Storage Toggle -->
<div class="form-group row">
    <label for="enable_local_storage" class="col-sm-3 col-form-label">Enable Local Storage</label>
    <div class="col-sm-9">
        <div class="custom-control custom-switch">
            <input type="hidden" name="enable_local_storage" value="0">
            <input type="checkbox" class="custom-control-input"
                   id="enable_local_storage"
                   name="enable_local_storage"
                   value="1"
                   {{ $SystemSetting->enable_local_storage ? 'checked' : '' }}>
            <label class="custom-control-label" for="enable_local_storage" id="enable_local_storage_label">
                {{ $SystemSetting->enable_local_storage == 1 ? 'Enabled' : 'Disabled' }}
            </label>
        </div>
    </div>
    @error('enable_local_storage')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
</div>

<!-- AWS S3 Toggle -->
<div class="form-group row">
    <label for="enable_s3_storage" class="col-sm-3 col-form-label">Enable AWS S3</label>
    <div class="col-sm-9">
        <div class="custom-control custom-switch">
            <input type="hidden" name="enable_s3_storage" value="0">
            <input type="checkbox" class="custom-control-input"
                   id="enable_s3_storage"
                   name="enable_s3_storage"
                   value="1"
                   {{ $SystemSetting->enable_s3_storage ? 'checked' : '' }}>
            <label class="custom-control-label" for="enable_s3_storage" id="enable_s3_storage_label">
                {{ $SystemSetting->enable_s3_storage == 1 ? 'Enabled' : 'Disabled' }}
            </label>
        </div>
    </div>
    @error('enable_s3_storage')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
</div>

<!-- AWS Credentials Section -->
<div id="aws_credentials_section" style="display: {{ $SystemSetting->enable_s3_storage == 1 ? 'block' : 'none' }};">
    <div class="form-group row">
        <label for="s3_bucket" class="col-sm-3 col-form-label">S3 Bucket Name</label>
        <div class="col-sm-9">
            <input type="text" class="form-control" id="s3_bucket" name="s3_bucket"
                   value="{{ $SystemSetting->s3_bucket }}" placeholder="Enter S3 Bucket Name">
        </div>
        @error('s3_bucket')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group row">
        <label for="s3_key" class="col-sm-3 col-form-label">AWS Access Key</label>
        <div class="col-sm-9">
            <input type="text" class="form-control" id="s3_key" name="s3_key"
                   value="{{ $SystemSetting->s3_key }}" placeholder="Enter AWS Access Key">
        </div>
        @error('s3_key')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group row">
        <label for="s3_secret" class="col-sm-3 col-form-label">AWS Secret Key Id</label>
        <div class="col-sm-9">
            <input type="password" class="form-control" id="s3_secret" name="s3_secret"
                   value="{{ $SystemSetting->s3_secret }}" placeholder="Enter AWS Secret Key">
        </div>
        @error('s3_secret')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group row">
        <label for="s3_region" class="col-sm-3 col-form-label">AWS Region</label>
        <div class="col-sm-9">
            <input type="text" class="form-control" id="s3_region" name="s3_region"
                   value="{{ $SystemSetting->s3_region }}" placeholder="Enter AWS Region">
        </div>
        @error('s3_region')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group row">
        <label for="s3_endpoint" class="col-sm-3 col-form-label">AWS S3 Endpoint</label>
        <div class="col-sm-9">
            <input type="text" class="form-control" id="s3_endpoint" name="s3_endpoint"
                   value="{{ $SystemSetting->s3_endpoint }}" placeholder="Enter S3 Endpoint">
        </div>
        @error('s3_endpoint')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror
    </div>
</div>

<!-- JavaScript for Toggling Storage Options -->
<script>
   document.addEventListener('DOMContentLoaded', function () {
    const enableS3Input = document.getElementById('enable_s3_storage');
    const enableLocalInput = document.getElementById('enable_local_storage');
    const awsCredentialsSection = document.getElementById('aws_credentials_section');

    // Toggle the visibility of AWS credentials section
    function toggleStorageOptions() {
        const isS3Enabled = enableS3Input.checked;
        awsCredentialsSection.style.display = isS3Enabled ? 'block' : 'none';
        enableLocalInput.checked = !isS3Enabled; // Disable local storage when S3 is enabled
    }

    // Initial state check
    toggleStorageOptions();

    // Add event listeners for toggles
    enableS3Input.addEventListener('change', toggleStorageOptions);
    enableLocalInput.addEventListener('change', function () {
        if (enableLocalInput.checked) {
            enableS3Input.checked = false; // Disable S3 when Local Storage is enabled
        }
        toggleStorageOptions();
    });
});

</script>
