<!-- Footer -->
<footer class="text mt-4 py-3" style="background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }} !important; color: white;">
    <div class="container">
        <div class="row align-items-center">
            <!-- Left Section -->
        @desktop()
            <div class="col-12 col-md-6 mb-2 mb-md-0">
                <p class="text-center text-md-start">
                    &copy; @php echo date('Y'); @endphp 
                    @if($SystemSetting) {{ $SystemSetting->system_name }} @endif. All Rights Reserved.
                </p>
            </div>
        @enddesktop()
            <!-- Right Section -->
            <div class="col-12 col-md-6">
                <ul class="list-inline text-center text-md-end mb-0">
                    <li class="list-inline-item">
                        <a href="{{ route('community.legal', ['page' => 'privacy-policy']) }}" class="text-white" style="text-decoration: none;">Privacy Policy</a>
                    </li>
                    <li class="list-inline-item">
                        <a href="{{ route('community.legal', ['page' => 'terms-and-conditions']) }}" class="text-white" style="text-decoration: none;">Terms & Conditions</a>
                    </li>
                    <li class="list-inline-item">
                        <a href="{{ route('community.legal', ['page' => 'disclaimer']) }}" class="text-white" style="text-decoration: none;">Disclaimer</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>


<!-- Include Bootstrap JS and jQuery -->
<script src="{{asset('frontend/js/bootstrap-bundle.min.js')}}"></script>
<script src="{{asset('frontend/js/jquery.min.js')}}"></script>
<!-- SweetAlerts -->
<script src="{{asset('assets/plugins/sweetalert2/sweetalert2.min.js')}}"></script>

{{-- Light Box  --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
    // Create lightbox elements
    const lightboxOverlay = document.createElement('div');
    lightboxOverlay.id = 'lightbox-overlay';

    const lightboxImage = document.createElement('img');
    lightboxImage.id = 'lightbox-image';

    const lightboxClose = document.createElement('span');
    lightboxClose.id = 'lightbox-close';
    lightboxClose.innerHTML = '&times;';

    lightboxOverlay.appendChild(lightboxImage);
    lightboxOverlay.appendChild(lightboxClose);
    document.body.appendChild(lightboxOverlay);

    // Add click event to images that are not inside <a> tags
    const images = document.querySelectorAll('img:not(a img)');
    images.forEach(img => {
        img.addEventListener('click', function () {
            lightboxImage.src = this.src;
            lightboxOverlay.style.display = 'flex';
        });
    });

    // Close lightbox when clicking the overlay or close button
    lightboxOverlay.addEventListener('click', function (e) {
        if (e.target === lightboxOverlay || e.target === lightboxClose) {
            lightboxOverlay.style.display = 'none';
        }
    });
});
</script>

 <!-- JavaScript to handle fallback -->
 <script>
    // Function to check if a CSS file is loaded successfully
    function isCSSLoaded(cssId) {
        const css = document.getElementById(cssId);
        if (!css) return false;

        // Check if the CSS file is loaded by testing a computed style
        const testElement = document.createElement('div');
        document.body.appendChild(testElement);
        const styleBefore = window.getComputedStyle(testElement).display;
        testElement.style.display = 'none';
        const styleAfter = window.getComputedStyle(testElement).display;
        document.body.removeChild(testElement);

        return styleAfter !== styleBefore;
    }

    // Check Bootstrap CSS
    if (!isCSSLoaded('bootstrap-css')) {
        const localBootstrap = document.createElement('link');
        localBootstrap.id = 'bootstrap-css';
        localBootstrap.rel = 'stylesheet';
        localBootstrap.href = "{{ asset('frontend/css/bootstrap.min.css') }}";
        document.head.appendChild(localBootstrap);
    }

    // Check FontAwesome CSS
    if (!isCSSLoaded('fontawesome-css')) {
        const localFontAwesome = document.createElement('link');
        localFontAwesome.id = 'fontawesome-css';
        localFontAwesome.rel = 'stylesheet';
        localFontAwesome.href = "{{ asset('frontend/css/fontawesome.min.css') }}";
        document.head.appendChild(localFontAwesome);
    }
</script>

