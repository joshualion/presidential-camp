@extends('backend.template.backend-master')
@section('content')
        <div class="card internal-communication-card">
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
@endsection
