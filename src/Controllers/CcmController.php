<?php

namespace App\Controllers;

use App\Support\Response;

class CcmController
{
    private $config;
    public function __construct($config)
    {
        $this->config = $config['moph_ccm'] ?? [];
    }

    // POST /api/v1/ccm
    public function post()
    {
        // Auth check is handled by middleware
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            Response::error('INVALID_JSON', 'Request body must be a valid JSON object or array.', 400);
            return;
        }
        // Check config
        $endpoint = $this->config['target_endpoint'] ?? '';
        $clientKey = $this->config['client_key'] ?? '';
        $secretKey = $this->config['secret_key'] ?? '';
        $timeout = $this->config['timeout'] ?? 15;
        if (!$endpoint || !$clientKey || !$secretKey) {
            Response::error('CONFIG_ERROR', 'CCM endpoint or credentials not configured.', 500);
            return;
        }
        // Compose request
        $headers = [
            'Content-Type: application/json',
            'client-key: ' . $clientKey,
            'secret-key: ' . $secretKey,
        ];
        // Some APIs require keys in body, some in header. Adjust as needed.
        // If CCM API requires keys in body, merge here:
        // $input['client_key'] = $clientKey;
        // $input['secret_key'] = $secretKey;
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) {
            Response::error('CCM_REQUEST_ERROR', $err, 502);
            return;
        }

        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $responseData = $response; // Fallback to raw string if not JSON
        }

        if ($httpCode >= 400) {
            Response::error('CCM_API_ERROR', 'Upstream API returned an error', $httpCode, ['upstream_response' => $responseData]);
            return;
        }

        Response::success($responseData, [], $httpCode);
    }
}
