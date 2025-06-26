<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;

class BroadcastController extends Controller
{
    /**
     * Authenticate the incoming request for a given channel.
     */
    public function authenticate(Request $request)
    {
        Log::info('🔐 Broadcasting authentication request', [
            'channel' => $request->channel_name,
            'user_id' => $request->user()->id ?? 'guest'
        ]);

        // Verify the channel name format
        if (!$this->validateChannelName($request->channel_name, $request->user())) {
            Log::warning('⚠️ Invalid channel name format', [
                'channel' => $request->channel_name,
                'user_id' => $request->user()->id ?? 'guest'
            ]);
            
            return response()->json(['error' => 'Invalid channel'], 403);
        }

        // Authenticate the channel
        $response = Broadcast::auth($request);

        Log::info('✅ Broadcasting authentication successful', [
            'channel' => $request->channel_name,
            'user_id' => $request->user()->id
        ]);

        return $response;
    }

    /**
     * Validate the channel name format
     */
    private function validateChannelName(string $channelName, $user): bool
    {
        // Check if user is authenticated
        if (!$user) {
            return false;
        }

        // Validate private channel format: private-user.{user_id}.chat.{chat_id}
        $pattern = '/^private-user\.' . $user->id . '\.chat\.\d+$/';
        
        return preg_match($pattern, $channelName) === 1;
    }
} 