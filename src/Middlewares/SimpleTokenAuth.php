<?php

namespace App\Middlewares;

class SimpleTokenAuth
{
    private $validTokens;

    public function __construct(array $tokens)
    {
        $this->validTokens = $tokens;
    }

    public function authenticate(): bool
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $token = $headers['X-API-Token'] ?? $headers['x-api-token'] ?? ($_SERVER['HTTP_X_API_TOKEN'] ?? null);

        if (!$token || !in_array($token, $this->validTokens, true)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized', 'message' => 'Missing or invalid API token']);
            return false;
        }
        return true;
    }
}
