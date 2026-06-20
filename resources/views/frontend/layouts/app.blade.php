<!DOCTYPE html>
<html lang="en">
<head>
    @include('frontend.header')
    <style>
        .styled-search-form {
            border-radius: 20px;
            padding-left: 15px;
        }

        @media (min-width: 992px) {
            .navbar-collapse {
            display: flex !important;
            }
       }

    </style>
</head>
<body class="d-flex flex-column min-vh-100" >

    <!-- Include Navbar -->
    @include('frontend.topnav')

    <div class="container-fluid flex-grow-3">
        <div class="row">
            <div class="col-lg-12" >
                <div class="container mt-4">
                    <div class="row">

                              <!-- Include Left Sidebar  -->
                            @desktop()
                            @if(Route::currentRouteName() === "timeline")
                                <div class="col-md-3 sidebar">

                                        @include('frontend.sidebar')

                                </div>
                            @endif
                            @enddesktop()
                            <!-- Include Left Sidebar  -->


                            <!-- Yield for content -->
                            @yield('content')
                            <!-- Yield for content -->




                            <!-- Include Right Sidebar -->
                            @if(Route::currentRouteName() === "timeline")
                            <div class="col-md-3 sidebar">
                                  @include('frontend.right-sidebar')

                            </div>
                            @endif
                            <!-- Include Right Sidebar -->

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    @include('frontend.footer')

    <div id="vue-widgets" class="position-relative">
        @isset($profileData)
            @php
                $messageRoutes = [
                    'conversations' => route('messages.conversations'),
                    'conversationMessages' => url('/messages/conversations/__CONVERSATION__/messages'),
                    'conversationSend' => url('/messages/conversations/__CONVERSATION__/messages'),
                    'conversationTyping' => url('/messages/conversations/__CONVERSATION__/typing'),
                    'conversationRead' => url('/messages/conversations/__CONVERSATION__/read'),
                    'recipients' => route('messages.recipients'),
                    'start' => route('messages.start'),
                    'ensure' => route('messages.ensure'),
                    'noImage' => asset('uploads/no_image.jpg'),
                    'memberImageBase' => asset('uploads/member_images'),
                ];
            @endphp
            <messenger-widget
                :profile-data='@json($profileData)'
                :routes='@json($messageRoutes)'
                theme-color="{{ $SystemSetting->dark_theme_color ?? '#008751' }}"
            ></messenger-widget>
        @endisset
    </div>

    @stack('scripts')

</body>
</html>
