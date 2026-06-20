<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class LiveChatController extends Controller
{
    public function __construct(){
        $pageTitle = 'Live Chat';
        View::share('pageTitle', $pageTitle);
    }

    public function index(Request $request)
    {
        return redirect()
            ->route('messages.page')
            ->with('status', 'Legacy Live Chat now uses Internal Communication.');
    }
    

    public function fetchMessages(Request $request)
    {
        return response()->json([
            'message' => 'Legacy Live Chat is disabled. Use Internal Communication.',
            'redirect' => route('messages.page'),
        ], 410);
    }

    public function sendMessage(Request $request)
    {
        return response()->json([
            'message' => 'Legacy Live Chat is disabled. Use Internal Communication.',
            'redirect' => route('messages.page'),
        ], 410);
    }

}
