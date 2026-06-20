@extends('backend.template.backend-master')
@section('content')
<div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Internal Communication</h1>
          </div>
        </div>
      </div>
    </div>
    <section class="content">
      <div class="container-fluid">
        <div class="card">
          <div class="card-body p-0">
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
            <div id="backend-send-message">
              <send-message
                :profile-data='@json($profileData)'
                :routes='@json($messageRoutes)'
                dark-theme-color="{{ $SystemSetting->dark_theme_color ?? '#008751' }}"
              ></send-message>
            </div>
          </div>
        </div>
      </div>
    </section>
</div>
@endsection
