<?php

namespace App\Services;

use Exception;

class AlertService
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(array $payload): array
    {
        $endpoint = trim((string)($this->config['endpoint'] ?? ''));
        $clientKey = trim((string)($this->config['client_key'] ?? ''));
        $secretKey = trim((string)($this->config['secret_key'] ?? ''));
        $timeout = (int)($this->config['timeout'] ?? 15);

        if ($endpoint === '' || $clientKey === '' || $secretKey === '') {
            throw new Exception('MOPH Alert credentials not configured');
        }

        $ch = curl_init($endpoint);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
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
            throw new Exception('Failed to call MOPH Alert: ' . $error);
        }

        $decoded = json_decode((string)$respBody, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return ['status' => $status, 'data' => $decoded];
        }

        return ['status' => $status, 'data' => ['raw' => (string)$respBody]];
    }

    public function sendTemplate(array $payload): array
    {
        // Template endpoint is usually derived from the main endpoint
        $endpoint = trim((string)($this->config['endpoint'] ?? ''));
        $templateEndpoint = str_replace('/messages', '/template', $endpoint);
        
        // Temporarily swap endpoint for this call
        $originalEndpoint = $this->config['endpoint'];
        $this->config['endpoint'] = $templateEndpoint;
        
        try {
            return $this->send($payload);
        } finally {
            $this->config['endpoint'] = $originalEndpoint;
        }
    }
}
