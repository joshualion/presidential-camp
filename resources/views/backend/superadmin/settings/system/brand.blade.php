{{-- Favicon --}}
<div class="form-group row">
    <label for="favicon" class="col-sm-2 col-form-label">Favicon</label>
    <div class="col-sm-10">
        <div class="align-items-center">
            <table cellpadding="1" class="table table-bordered table-responsive" role="none">
                <!-- Favicon Image Preview -->
                <td>
                    <img id="showimage" class="img-square img-responsive"
                         src="{{ !empty($SystemSetting->favicon) ? url('uploads/system_images/'.$SystemSetting->favicon) : url('favicon-32x32.png') }}"
                         alt="favicon" width="100%" height="100%">
                </td>
                <!-- Favicon Change Button -->
                <td colspan="2">
                    <label for="favicon" class="btn btn-primary ms-10 d-block"> Change Favicon</label>
                </td>
            </table>
            <!-- Hidden Favicon File Input -->
            <input type="file" class="form-control d-none" id="favicon" name="favicon" accept="image/x-icon,image/png,image/jpeg">
        </div>
    </div>
</div>

{{-- Logo --}}
<div class="form-group row">
    <label for="image" class="col-sm-2 col-form-label">Logo</label>
    <div class="col-sm-10">
        <div class="align-items-center">
            <table cellpadding="1"    class="table table-bordered table-responsive" role="none" >
             <td><img id="showLogo" class="img-square img-responsive" src="{{ !empty($SystemSetting->logo) ? url('uploads/system_images/'.$SystemSetting->logo) : url('logo.png') }}" alt="profile" width="50%" height="15%"></td>
             <td colspan="2"><label for="logo" class="btn btn-primary ms-10 d-block"> Change Logo</label></td>
             </table>
            <input type="file" class="form-control d-none" id="logo" name="logo">
        </div>
    </div>
</div>

{{-- Login Page Background --}}
<div class="form-group row">
    <label for="login_page_background" class="col-sm-2 col-form-label">Login Page Background</label>
    <div class="col-sm-10">
        <div class="align-items-center">
            <table cellpadding="1" align="center" class="table table-bordered table-responsive" role="none">
                <!-- Login Page Background Preview -->
                <td>
                    <img id="showImage" class="img-square img-responsive"
                         src="{{ !empty($SystemSetting->login_page_background) ? url('uploads/system_images/'.$SystemSetting->login_page_background) : url('aso-rock.jpg') }}"
                         alt="login background" width="45%" height="45%">
                </td>
                <!-- Change Background Button -->
                <td colspan="2">
                    <label for="login_page_background" class="btn btn-primary ms-10 d-block"> Change Background</label>
                </td>
            </table>
            <!-- Hidden File Input for Login Page Background -->
            <input type="file" class="form-control d-none" id="login_page_background" name="login_page_background" accept="image/png,image/jpeg">
        </div>
    </div>
</div>
{{-- Dark Theme Color --}}
<div class="form-group row">
    <label for="dark_theme_color" class="col-sm-3 col-form-label">Dark Brand Color</label>
    <div class="col-sm-2">
        <input type="color"
               class="form-control @error('dark_theme_color') is_invalid @enderror"
               name="dark_theme_color"
               id="dark_theme_color"
               value="{{ $SystemSetting->dark_theme_color }}"
               placeholder="System Name"
               oninput="document.getElementById('hexCode').value = this.value">
    </div>
    <div class="col-sm-2">
        <!-- This input will display the hex code -->
        <input type="text" id="hexCode" class="form-control" value="{{ $SystemSetting->dark_theme_color }}" readonly>
    </div>
    @error('dark_theme_color')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
</div>

{{-- Light Theme Color --}}
<div class="form-group row">
    <label for="light_theme_color" class="col-sm-3 col-form-label">Light Brand Color</label>
    <div class="col-sm-2">
        <input type="color"
               class="form-control @error('light_theme_color') is_invalid @enderror"
               name="light_theme_color"
               id="light_theme_color"
               value="{{ $SystemSetting->light_theme_color }}"
               placeholder="System Name"
               oninput="document.getElementById('hexCode').value = this.value">
    </div>
    <div class="col-sm-2">
        <!-- This input will display the hex code -->
        <input type="text" id="hexCode" class="form-control" value="{{ $SystemSetting->light_theme_color }}" readonly>
    </div>
    @error('light_theme_color')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Function to preview an image
        function previewImage(input, targetImgId) {
            if (input.files && input.files[0]) {
                let reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById(targetImgId).src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Favicon preview
        const faviconInput = document.getElementById("favicon");
        faviconInput.addEventListener("change", function () {
            previewImage(this, "showimage");
        });

        // Logo preview (fix target image ID)
        const logoInput = document.getElementById("logo");
        logoInput.addEventListener("change", function () {
            previewImage(this, "showLogo");
        });

        // Login page background preview
        const loginPageBackgroundInput = document.getElementById("login_page_background");
        loginPageBackgroundInput.addEventListener("change", function () {
            previewImage(this, "showImage");
        });
    });
</script>

