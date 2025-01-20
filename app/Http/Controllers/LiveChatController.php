<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\LiveChat;

class LiveChatController extends Controller
{
    public function __construct(){
        $pageTitle = 'Live Chat';
        View::share('pageTitle', $pageTitle);
    }

     // Function to get the profile data
     private function getProfileData()
     {
         $id = Auth::user()->id;
         return User::find($id);
     }

     private function filterMemberByAccessLevel()
     {
         $profileData = $this->getProfileData();
     
         // Define access level hierarchy
         $accessLevels = [
             'superadmin',
             'nationaladmin',
             'regionaladmin',
             'stateadmin',
             'lgaadmin',
             'wardadmin',
             'puadmin',
             'user',
         ];
     
         // Determine the current access level index
         $currentLevelIndex = array_search($profileData->access_level, $accessLevels);
         if ($currentLevelIndex === false) {
             throw new \Exception('Invalid access level: ' . $profileData->access_level);
         }
     
         // Include all access levels below the current one
         $includedLevels = array_slice($accessLevels, $currentLevelIndex);
     
         // Start building the query
         $membersQuery = User::query()->whereIn('access_level', $includedLevels);
     
         // Apply geographical constraints based on the current user's access level
         if ($profileData->access_level === 'regionaladmin') {
             $membersQuery->where('region_id', $profileData->region_id);
         } elseif ($profileData->access_level === 'stateadmin') {
             $membersQuery->where('state_id', $profileData->state_id);
         } elseif ($profileData->access_level === 'lgaadmin') {
             $membersQuery->where('lga_id', $profileData->lga_id);
         } elseif ($profileData->access_level === 'wardadmin') {
             $membersQuery->where('ward_id', $profileData->ward_id);
         } elseif ($profileData->access_level === 'pollingunitadmin') {
             $membersQuery->where('polling_unit_id', $profileData->polling_unit_id);
         }
     
         return $membersQuery;
     }


     // send page

    //  public function index(){
    //     $profileData = $this->getProfileData();
    //     $receivers = $this->filterMemberByAccessLevel();
    //     $conversations = LiveChat::where('sender_id',$profileData->id)

    //     return view('backend.'.$profileData->access_level.'.livechat.send', compact('profileData','receivers', 'conversations'));
    //  }

    public function index(Request $request)
    {
        $profileData = $this->getProfileData();
        $receivers = $this->filterMemberByAccessLevel();
    
        // Fetch conversations
        $conversations = LiveChat::where('sender_id', $profileData->id)
            ->orWhere('receiver_id', $profileData->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($chat) use ($profileData) {
                return $chat->sender_id === $profileData->id ? $chat->receiver_id : $chat->sender_id;
            })
            ->map(function ($chats, $key) {
                $receiver = User::find($key); // Assuming User model exists
                return [
                    'receiver' => $receiver ? $receiver->only(['id', 'name']) : null,
                    'messages' => $chats,
                ];
            })
            ->filter(); // Remove empty/null receivers
    
        // Ensure $conversations is not null
        $conversations = $conversations->isEmpty() ? [] : $conversations;
    
        return view('backend.' . $profileData->access_level . '.livechat.send', compact('profileData', 'receivers', 'conversations'));
    }
    

public function fetchMessages(Request $request)
{
    $profileData = $this->getProfileData();
    $receiverId = $request->input('receiver_id');

    $messages = LiveChat::where(function ($query) use ($profileData, $receiverId) {
        $query->where('sender_id', $profileData->id)
              ->where('receiver_id', $receiverId);
    })->orWhere(function ($query) use ($profileData, $receiverId) {
        $query->where('sender_id', $receiverId)
              ->where('receiver_id', $profileData->id);
    })->orderBy('created_at', 'asc')->get();

    return response()->json($messages);
}

public function sendMessage(Request $request)
{
    $profileData = $this->getProfileData();
    $request->validate([
        'receiver_id' => 'required|exists:users,id',
        'message' => 'required|string',
    ]);

    $message = LiveChat::create([
        'sender_id' => $profileData->id,
        'receiver_id' => $request->input('receiver_id'),
        'message' => $request->input('message'),
    ]);

    return response()->json($message);
}

}
