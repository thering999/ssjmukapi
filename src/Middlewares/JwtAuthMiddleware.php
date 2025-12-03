<?php

namespace App\Middlewares;

use App\Support\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

class JwtAuthMiddleware
{
    private $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function authenticate(): bool
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            Response::error('UNAUTHORIZED', 'Token not provided', 401);
            return false;
        }

        $jwt = $matches[1];

        try {
            $decoded = JWT::decode($jwt, new Key($this->secret, 'HS256'));
            // Optionally attach user info to request/global state if needed
            // $_SERVER['USER'] = (array) $decoded;
            return true;
        } catch (Throwable $e) {
            Response::error('UNAUTHORIZED', 'Invalid or expired token: ' . $e->getMessage(), 401);
            return false;
        }
    }
}
