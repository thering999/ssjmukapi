<?php

namespace App\Controllers;

use App\Support\Response;
use PDO;

class NotifyController
{
    /**
     * Generic proxy to MOPH Notify API.
     * Requires auth. Forwards JSON body as-is with client-key/secret-key headers.
     */
    public static function send(PDO $pdo, $data, array $notifyConfig): void
    {
        $endpoint = trim((string)($notifyConfig['endpoint'] ?? ''));
        $clientKey = trim((string)($notifyConfig['client_key'] ?? ''));
        $secretKey = trim((string)($notifyConfig['secret_key'] ?? ''));
        $timeout = (int)($notifyConfig['timeout'] ?? 15);

        if ($endpoint === '' || $clientKey === '' || $secretKey === '') {
            Response::error('CONFIG_ERROR', 'MOPH Notify credentials not configured', 500);
            return;
        }

        // Accept any JSON object/array body per Notify spec variants
        if (!is_array($data)) {
            Response::error('VALIDATION_ERROR', 'Request body must be JSON object/array', 400);
            return;
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'client-key: ' . $clientKey,
                'secret-key: ' . $secretKey,
            ],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_TIMEOUT => max(5, $timeout),
        ]);
        $respBody = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            Response::error('NOTIFY_HTTP_ERROR', 'Failed to call MOPH Notify: ' . $error, 502, [
                'endpoint' => $endpoint,
            ]);
            return;
        }

        $decoded = json_decode((string)$respBody, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $http = ($status >= 200 && $status < 600) ? $status : 200;
            Response::success($decoded, [], $http);
        } else {
            $http = ($status >= 200 && $status < 600) ? $status : 502;
            Response::success(['raw' => (string)$respBody], [], $http);
        }
    }
}
