<?php

namespace App\Controllers;

use App\Support\Response;

class GatewayController
{
    /**
     * Generic proxy for forwarding requests to configured services.
     */
    public static function proxy(string $serviceName, array $requestData, array $servicesConfig): void
    {
        // 1. Find the service configuration
        $serviceConfig = $servicesConfig[$serviceName] ?? null;

        if ($serviceConfig === null) {
            Response::error('SERVICE_NOT_FOUND', "The requested service '{$serviceName}' is not configured.", 404);
            return;
        }

        // 2. Validate essential configuration
        $targetEndpoint = trim((string)($serviceConfig['target_endpoint'] ?? ''));
        $clientKey = trim((string)($serviceConfig['client_key'] ?? ''));
        $secretKey = trim((string)($serviceConfig['secret_key'] ?? ''));
        $timeout = (int)($serviceConfig['timeout'] ?? 15);

        if ($targetEndpoint === '') {
            Response::error('CONFIG_ERROR', "Target endpoint for '{$serviceName}' is not configured.", 500);
            return;
        }

        // 3. Build the request to the target service
        $headers = [
            'Content-Type: application/json',
        ];
        // Add credentials if they are configured for this service
        if ($clientKey !== '') {
            $headers[] = 'client-key: ' . $clientKey;
        }
        if ($secretKey !== '') {
            $headers[] = 'secret-key: ' . $secretKey;
        }

        // 4. Perform HTTP POST request using cURL
        $ch = curl_init($targetEndpoint);
        $jsonPayload = json_encode($requestData, JSON_UNESCAPED_UNICODE);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_TIMEOUT => max(5, $timeout),
        ]);

        $responseBody = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpStatusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 5. Handle cURL errors
        if ($errno) {
            Response::error('GATEWAY_HTTP_ERROR', "Failed to call the target service '{$serviceName}': " . $error, 502);
            return;
        }

        // 6. Forward the response from the target service
        // Try to decode JSON, if not, return raw response.
        $decodedResponse = json_decode((string)$responseBody, true);
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($httpStatusCode);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedResponse)) {
            echo json_encode($decodedResponse);
        } else {
            // If the response is not valid JSON, wrap it
            echo json_encode(['success' => false, 'error' => 'INVALID_TARGET_RESPONSE', 'raw_response' => (string)$responseBody]);
        }
    }
}
