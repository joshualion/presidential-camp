@extends('frontend.layouts.app')

@section('content')
<div class="container py-4">
    @php
        $messageRoutes = [
            'conversations' => route(auth()->user()->access_level.'.messages.conversations'),
            'conversationMessages' => route(auth()->user()->access_level.'.messages.conversation.messages', ['conversation' => '__CONVERSATION__']),
            'conversationSend' => route(auth()->user()->access_level.'.messages.conversation.send', ['conversation' => '__CONVERSATION__']),
            'conversationTyping' => route(auth()->user()->access_level.'.messages.conversation.typing', ['conversation' => '__CONVERSATION__']),
            'conversationRead' => route(auth()->user()->access_level.'.messages.conversation.read', ['conversation' => '__CONVERSATION__']),
            'recipients' => route(auth()->user()->access_level.'.messages.recipients'),
            'start' => route(auth()->user()->access_level.'.messages.start'),
            'ensure' => route(auth()->user()->access_level.'.messages.ensure'),
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
