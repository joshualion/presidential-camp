<?php

namespace App\Http\Controllers;

use App\Services\AccessLevelRouteService;
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
            ->route($this->messagesRoute($request))
            ->with('status', 'Legacy Live Chat now uses Internal Communication.');
    }
    

    public function fetchMessages(Request $request)
    {
        return response()->json([
            'message' => 'Legacy Live Chat is disabled. Use Internal Communication.',
            'redirect' => route($this->messagesRoute($request)),
        ], 410);
    }

    public function sendMessage(Request $request)
    {
        return response()->json([
            'message' => 'Legacy Live Chat is disabled. Use Internal Communication.',
            'redirect' => route($this->messagesRoute($request)),
        ], 410);
    }

    private function messagesRoute(Request $request): string
    {
        return app(AccessLevelRouteService::class)
            ->sharedRouteForUser($request->user(), 'messages.page') ?? 'login';
    }

}
