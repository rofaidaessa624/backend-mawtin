<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FirebaseNotificationService
{
    /**
     * إرسال إشعار لمستخدم واحد
     */
    public function sendToClient($client, $title, $body)
    {
        if (!$client || !$client->device_token) {
            return;
        }

        $response = Http::withToken(env('FIREBASE_SERVER_KEY'))
            ->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $client->device_token,

                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                    'icon'  => asset('mawtin-icon.png'),
                    'click_action' => env('FRONTEND_URL'),
                ],

                'data' => [
                    'title' => $title,
                    'body'  => $body,
                    'type'  => 'general',
                ],
            ]);

        return $response->json();
    }

    /**
     * إرسال لكل العملاء (اختياري مهم جداً للـ admin actions)
     */
    public function sendToAll($clients, $title, $body)
    {
        foreach ($clients as $client) {
            $this->sendToClient($client, $title, $body);
        }
    }
}