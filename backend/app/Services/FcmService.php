<?php

namespace App\Services;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class FcmService
{
    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        try {
            $messaging = $this->getMessaging();

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(FcmNotification::create($title, $body))
                ->withData(array_map('strval', $data))
                ->withAndroidConfig(
                    \Kreait\Firebase\Messaging\AndroidConfig::fromArray([
                        'notification' => [
                            'icon' => 'ic_notification',
                            'color' => '#10B981',
                            'channel_id' => 'bus_tracker_notifications',
                            'default_sound' => true,
                        ],
                    ])
                );

            $messaging->send($message);
        } catch (\Throwable $e) {
            logger()->error('FCM sendToToken failed: ' . $e->getMessage());
        }
    }

    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): void
    {
        try {
            $messaging = $this->getMessaging();

            foreach (array_chunk($tokens, 500) as $chunk) {
                $message = CloudMessage::new()
                    ->withNotification(FcmNotification::create($title, $body))
                    ->withData(array_map('strval', $data))
                    ->withAndroidConfig(
                        \Kreait\Firebase\Messaging\AndroidConfig::fromArray([
                            'notification' => [
                                'icon' => 'ic_notification',
                                'color' => '#10B981',
                                'channel_id' => 'bus_tracker_notifications',
                                'default_sound' => true,
                            ],
                        ])
                    );

                $messaging->sendMulticast($message, $chunk);
            }
        } catch (\Throwable $e) {
            logger()->error('FCM sendToTokens failed: ' . $e->getMessage());
        }
    }

    public function sendToTopic(string $topic, string $title, string $body, array $data = []): void
    {
        try {
            $messaging = $this->getMessaging();

            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(FcmNotification::create($title, $body))
                ->withData(array_map('strval', $data))
                ->withAndroidConfig(
                    \Kreait\Firebase\Messaging\AndroidConfig::fromArray([
                        'notification' => [
                            'icon' => 'ic_notification',
                            'color' => '#10B981',
                            'channel_id' => 'bus_tracker_notifications',
                            'default_sound' => true,
                        ],
                    ])
                );

            $messaging->send($message);
        } catch (\Throwable $e) {
            logger()->error('FCM sendToTopic failed: ' . $e->getMessage());
        }
    }

    protected function getMessaging(): Messaging
    {
        $credentialsPath = config('services.firebase.credentials', storage_path('app/firebase-credentials.json'));

        $factory = (new \Kreait\Firebase\Factory())
            ->withServiceAccount($credentialsPath);

        return $factory->createMessaging();
    }
}
