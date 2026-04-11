<?php

namespace App\Services;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Kreait\Firebase\Messaging\AndroidConfig;

class FcmService
{
    /**
     * @return array<string, mixed>
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): array
    {
        try {
            $messaging = $this->getMessaging();

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(FcmNotification::create($title, $body))
                ->withData(array_map('strval', $data))
                ->withAndroidConfig($this->buildAndroidConfig());

            $messaging->send($message);

            return [
                'success' => true,
                'target' => 'token',
                'token_count' => 1,
            ];
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'target' => 'token',
                'token_count' => 1,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ];

            logger()->error('FCM sendToToken failed', $result);

            return $result;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        $tokens = array_values(array_filter($tokens));

        if ($tokens === []) {
            $result = [
                'success' => false,
                'target' => 'tokens',
                'token_count' => 0,
                'error' => 'No tokens provided',
            ];

            logger()->warning('FCM sendToTokens skipped', $result);

            return $result;
        }

        try {
            $messaging = $this->getMessaging();
            $successCount = 0;
            $failureCount = 0;

            foreach (array_chunk($tokens, 500) as $chunk) {
                $message = CloudMessage::new()
                    ->withNotification(FcmNotification::create($title, $body))
                    ->withData(array_map('strval', $data))
                    ->withAndroidConfig($this->buildAndroidConfig());

                $report = $messaging->sendMulticast($message, $chunk);
                $successCount += $report->successes()->count();
                $failureCount += $report->failures()->count();
            }

            return [
                'success' => $failureCount === 0,
                'target' => 'tokens',
                'token_count' => count($tokens),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
            ];
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'target' => 'tokens',
                'token_count' => count($tokens),
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ];

            logger()->error('FCM sendToTokens failed', $result);

            return $result;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): array
    {
        try {
            $messaging = $this->getMessaging();

            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(FcmNotification::create($title, $body))
                ->withData(array_map('strval', $data))
                ->withAndroidConfig($this->buildAndroidConfig());

            $messaging->send($message);

            return [
                'success' => true,
                'target' => 'topic',
                'topic' => $topic,
            ];
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'target' => 'topic',
                'topic' => $topic,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ];

            logger()->error('FCM sendToTopic failed', $result);

            return $result;
        }
    }

    protected function getMessaging(): Messaging
    {
        $credentialsPath = config('services.firebase.credentials');

        if (!is_string($credentialsPath) || $credentialsPath === '' || !is_file($credentialsPath)) {
            throw new \RuntimeException('Firebase credentials file not found: ' . (string) $credentialsPath);
        }

        $factory = (new \Kreait\Firebase\Factory())
            ->withServiceAccount($credentialsPath);

        return $factory->createMessaging();
    }

    protected function buildAndroidConfig(): AndroidConfig
    {
        return AndroidConfig::fromArray([
            'notification' => [
                'icon' => 'ic_notification',
                'color' => '#10B981',
                'channel_id' => 'bus_tracker_notifications',
                'default_sound' => true,
            ],
        ]);
    }
}
