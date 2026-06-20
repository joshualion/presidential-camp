@extends('frontend.layouts.app')

@section('content')
<div class="container py-4">
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
    <div id="app">
        <send-message
            :profile-data='@json($profileData)'
            :routes='@json($messageRoutes)'
            dark-theme-color="{{ $SystemSetting->dark_theme_color ?? '#008751' }}"
        ></send-message>
    </div>
</div>
@endsection
