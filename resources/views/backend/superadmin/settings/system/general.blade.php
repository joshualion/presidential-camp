@extends('backend.template.backend-master')
@section('content')


@php
 $packages = app(\App\Services\PackageGovernanceService::class)->options();
@endphp

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">System Settings</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-tool" data-card-widget="remove">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                @include('backend.'.$profileData->access_level.'.settings.system.menu')
                            </div>

                            <div class="col-md-9">

                                    <div class="tab-content" id="myTabContent">
                                        <form method="POST" action="{{ route($profileData->access_level.'.settings.system.update') }}" enctype="multipart/form-data">
                                            @csrf
                                            @method('POST')
                                            <input type="hidden" name="id" value="{{ $SystemSetting->id }}" />


                                             <div class="tab-pane fade show active" id="general-settings" role="tabpanel">
                                                @include('backend.'.$profileData->access_level.'.settings.system.general_settings')


                                            </div>


                                            <div class="tab-pane fade show" id="brand-settings" role="tabpanel">

                                                @include('backend.'.$profileData->access_level.'.settings.system.brand')

                                            </div>

                                            <div class="tab-pane fade show" id="community-settings" role="tabpanel">

                                                @include('backend.'.$profileData->access_level.'.settings.system.frontend_community')

                                            </div>

                                            <div class="tab-pane fade show" id="cloud_storage-settings" role="tabpanel">

                                                @include('backend.'.$profileData->access_level.'.settings.system.cloud_storage')

                                            </div>

                                            <div class="tab-pane fade show" id="community-rules" role="tabpanel">

                                                @include('backend.'.$profileData->access_level.'.settings.system.community_rules')

                                            </div>

                                            <div class="tab-pane fade show" id="social-media" role="tabpanel">

                                                @include('backend.'.$profileData->access_level.'.settings.system.social_media')

                                            </div>

                                            <div class="tab-pane fade show" id="terms-settings" role="tabpanel">

                                                @include('backend.'.$profileData->access_level.'.settings.system.terms')

                                            </div>

                                            <div class="tab-pane fade show" id="privacy-settings" role="tabpanel">

                                                @include('backend.'.$profileData->access_level.'.settings.system.privacy')

                                            </div>

                                            <div class="tab-pane fade show" id="disclaimer-settings" role="tabpanel">

                                                @include('backend.'.$profileData->access_level.'.settings.system.disclaimer')

                                            </div>

                                            <div class="tab-pane fade show" id="copyright-settings" role="tabpanel">

                                                @include('backend.'.$profileData->access_level.'.settings.system.copyright')

                                            </div>


                                            <div class="form-group row">
                                                <div class="col-sm-9 off-set-6">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class='fas fa-save' style='font-size:24px;color:white'> Save </i>
                                                    </button>
                                                </div>
                                            </div>

                                        </form>

                                    </div>


                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>

<!-- jQuery -->
<script src="{{asset('assets/plugins/jquery/jquery.min.js')}}"></script>
<script>
    $(document).ready(function() {
        // Hide all tab content initially
        $('.tab-pane').hide();

        // Get the hash from the URL
        var hash = window.location.hash || '#general-settings'; // Default to general settings if no hash is found

        // Show the tab content that corresponds to the hash
        $('.tab-pane' + hash).show();

        // Activate the corresponding tab
        $('#myTab a[href="' + hash + '"]').addClass('active');

        // Click event for tabs
        $('#myTab a').on('click', function(event) {
            event.preventDefault(); // Prevent default anchor click behavior
            var target = $(this).attr('href'); // Get the href attribute of the clicked tab

            // Remove active class from all tabs and hide all tab content
            $('#myTab a').removeClass('active');
            $('.tab-pane').hide();

            // Add active class to the clicked tab and show the target tab content
            $(this).addClass('active');
            $(target).show();

            // Update the URL hash
            window.location.hash = target; // This updates the URL without reloading the page
        });
    });
</script>


    <!-- TinyMCE Editor Integration -->
    <script src="https://cdn.tiny.cloud/1/cgksa1lzpz0juy9k9hf7tg6zdsh2j5a1s2thsbc7uz5kx2by/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>


    <script>
        tinymce.init({
          selector: 'textarea',
          plugins: 'code anchor autolink charmap codesample emoticons featured_image link lists media searchreplace table visualblocks visualchars wordcount',
          toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link featured_image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
        });
    </script>

@endsection
