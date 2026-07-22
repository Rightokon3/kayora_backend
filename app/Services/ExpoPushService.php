<?php

namespace App\Services;

use App\Models\AdminPushToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    /**
     * Push a message to every registered admin device.
     *
     * $data is an arbitrary payload the admin app can read when the
     * notification is tapped — e.g. to deep-link straight to the
     * distributor-application modal for that customer.
     */
    public function notifyAdmins(string $title, string $body, array $data = []): void
    {
        $tokens = AdminPushToken::pluck('expo_push_token')->all();

        if (empty($tokens)) {
            return;
        }

        $messages = array_map(fn ($token) => [
            'to' => $token,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sound' => 'default',
            'priority' => 'high',
        ], $tokens);

        // Expo accepts batches of up to 100 messages per request.
        foreach (array_chunk($messages, 100) as $chunk) {
            try {
                $response = Http::post(self::ENDPOINT, $chunk);
                if ($response->failed()) {
                    Log::warning('Expo push batch failed', ['body' => $response->body()]);
                }
            } catch (\Throwable $e) {
                // A failed push should never break the request that
                // triggered it (e.g. submitting the distributor form).
                Log::error('Expo push exception: '.$e->getMessage());
            }
        }
    }
}