<?php

namespace App\Services;

use Exception;

class LineService
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(string $userId, string $message, string $imageUrl = ''): array
    {
        $token = trim((string)($this->config['notify_token'] ?? ''));
        $timeout = (int)($this->config['timeout'] ?? 15);

        if ($token === '') {
            throw new Exception('Line Notify token not configured');
        }

        $ch = curl_init('https://notify-api.line.me/api/notify');
        
        $postData = [
            'message' => $message,
            // Note: Line Notify API does not actually support 'userId' in the payload for direct messaging in the same way as push API.
            // It uses the token which is bound to a specific user or group.
            // However, the original code had 'userId' in the payload. I will keep it if it's a custom proxy, 
            // but standard Line Notify doesn't use it.
            // Wait, the original code used `https://notify-api.line.me/api/notify`.
            // Line Notify sends to the user/group connected to the token.
            // If the original code was trying to send to multiple users, it implies multiple tokens or a different API.
            // But the original code loop: `self::sendToLineUser($userId, ...)` and put `userId` in postData.
            // This is suspicious. Line Notify ignores unknown parameters.
            // If the user wants to send to specific users, they usually need the Messaging API (push message), not Notify.
            // But I will faithfully reproduce the logic of the original controller, just refactored.
            // The original code passed 'userId' in postData.
            'userId' => $userId, 
        ];
        
        if ($imageUrl !== '') {
            $postData['imageThumbnail'] = $imageUrl;
            $postData['imageFullsize'] = $imageUrl;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_TIMEOUT => max(5, $timeout),
        ]);

        $respBody = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new Exception('Curl error: ' . $error);
        }

        if ($status !== 200) {
            $decoded = json_decode($respBody, true);
            $msg = $decoded['message'] ?? $respBody ?? 'HTTP ' . $status;
            throw new Exception($msg);
        }

        return ['success' => true];
    }
}
