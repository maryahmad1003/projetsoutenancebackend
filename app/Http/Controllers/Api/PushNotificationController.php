<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'subscription.endpoint' => 'required|string',
            'subscription.keys.p256dh' => 'required|string',
            'subscription.keys.auth' => 'required|string',
        ]);

        $user = $request->user();
        
        $subscription = [
            'endpoint' => $request->input('subscription.endpoint'),
            'keys' => $request->input('subscription.keys'),
        ];

        $user->push_subscription = $subscription;
        $user->save();

        Log::info('[PUSH] Subscription saved for user ' . $user->id);

        return response()->json(['success' => true]);
    }

    public function unsubscribe(Request $request)
    {
        $user = $request->user();
        $user->push_subscription = null;
        $user->save();

        Log::info('[PUSH] Unsubscribed user ' . $user->id);

        return response()->json(['success' => true]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'url' => 'nullable|string',
        ]);

        $users = User::whereNotNull('push_subscription')
            ->where('est_actif', true)
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            if ($user->push_subscription) {
                try {
                    $this->sendPushNotification($user->push_subscription, [
                        'title' => $request->input('title'),
                        'body' => $request->input('body'),
                        'url' => $request->input('url', '/'),
                    ]);
                    $sent++;
                } catch (\Exception $e) {
                    Log::error('[PUSH] Failed for user ' . $user->id . ': ' . $e->getMessage());
                    $failed++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }

    protected function sendPushNotification(array $subscription, array $data): void
    {
        $endpoint = $subscription['endpoint'];
        $p256dh = $subscription['keys']['p256dh'];
        $auth = $subscription['keys']['auth'];

        $payload = json_encode([
            'title' => $data['title'],
            'body' => $data['body'],
            'icon' => '/logo192.png',
            'badge' => '/favicon.ico',
            'tag' => 'docsecur-notif',
            'data' => $data['url'] ?? '/',
        ]);

        $headers = [
            'Content-Type' => 'application/json',
            'TTL' => 86400,
            'Authorization' => ' vapid-p256dh=' . $p256dh . ', auth=' . $auth,
        ];

        try {
            Http::withHeaders($headers)->post($endpoint, [
                'notification' => json_decode($payload, true),
            ]);
        } catch (\Exception $e) {
            Log::error('[PUSH] Notification error: ' . $e->getMessage());
            throw $e;
        }
    }
}
