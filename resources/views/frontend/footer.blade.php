<!-- Footer -->
<footer class="text mt-4 py-3" style="background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }} !important; color: white;">
    <div class="container">
        <div class="row align-items-center">
            <!-- Left Section -->
            <div class="col-12 col-md-6 mb-2 mb-md-0">
                <p class="text-center text-md-start">
                    &copy; @php echo date('Y'); @endphp 
                    @if($SystemSetting) {{ $SystemSetting->system_name }} @endif. All Rights Reserved.
                </p>
            </div>
            <!-- Right Section -->
            <div class="col-12 col-md-6">
                <ul class="list-inline text-center text-md-end mb-0">
                    <li class="list-inline-item">
                        <a href="#" class="text-white" style="text-decoration: none;">Privacy Policy</a>
                    </li>
                    <li class="list-inline-item">
                        <a href="#" class="text-white" style="text-decoration: none;">Terms & Conditions</a>
                    </li>
                    <li class="list-inline-item">
                        <a href="#" class="text-white" style="text-decoration: none;">Disclaimer</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>


<!-- Include Bootstrap JS and jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
