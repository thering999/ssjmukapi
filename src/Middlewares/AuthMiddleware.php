<?php

namespace App\Middlewares;

use App\Support\Response;
use Exception;
use PDO;
use Throwable;

class AuthMiddleware
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Verify API key or JWT token from Authorization header
     * Expected format: Authorization: Bearer <api_key_or_jwt_token>
     */
    public function authenticate(): bool
    {
        // Try multiple places for the API key/header to accommodate different servers/clients
        $authHeader = '';

        // 1) Prefer getallheaders() (works in Apache/mod_php and some servers)
        if (function_exists('getallheaders')) {
            $all = getallheaders();
            foreach (['Authorization', 'authorization', 'X-API-Token', 'X-API-KEY', 'X-Api-Token'] as $k) {
                if (isset($all[$k]) && $all[$k] !== '') {
                    $authHeader = $all[$k];
                    break;
                }
            }
        }

        // 2) Fallback to common $_SERVER locations (PHP-FPM, CGI, etc.)
        if ($authHeader === '') {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_API_TOKEN'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
        }

        if (empty($authHeader)) {
            Response::error('UNAUTHORIZED', 'Missing Authorization header', 401);
            return false;
        }

        // Support three forms:
        // - Authorization: Bearer <api_key>
        // - Authorization: <api_key>            (raw token)
        // - X-API-Token: <api_key> or X-API-KEY: <api_key> (handled by header detection)
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            // If header contains spaces (and is not Bearer), reject as invalid format
            if (strpos($authHeader, ' ') !== false) {
                Response::error('UNAUTHORIZED', 'Invalid Authorization format. Expected: Bearer <api_key> or X-API-Token: <api_key>', 401);
                return false;
            }
            $token = $authHeader;
        }

        // We no longer support JWT in this middleware to prevent security risks.
        // Clients sending JWTs must use the endpoints protected by JwtAuthMiddleware.
        if (strpos($token, '.') !== false) {
            Response::error('UNAUTHORIZED', 'JWT authentication not supported in this endpoint context. Use API Key.', 401);
            return false;
        }

        // API key validation
        return $this->validateAPIKey($token);
    }

    /**
     * Validate API key against database
     */
    private function validateAPIKey(string $apiKey): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT id, name, is_active FROM api_keys WHERE api_key = :key AND is_active = 1 LIMIT 1');
            $stmt->bindValue(':key', $apiKey);
            $stmt->execute();
            $key = $stmt->fetch();

            if (!$key) {
                Response::error('UNAUTHORIZED', 'Invalid or inactive API key', 401);
                return false;
            }

            // Update last_used_at
            $stmt = $this->pdo->prepare('UPDATE api_keys SET last_used_at = NOW() WHERE id = :id');
            $stmt->bindValue(':id', $key['id'], PDO::PARAM_INT);
            $stmt->execute();

            // Store key info for logging/audit
            $_SERVER['AUTH_TYPE'] = 'API_KEY';
            $_SERVER['AUTH_KEY_ID'] = $key['id'];
            $_SERVER['AUTH_KEY_NAME'] = $key['name'];

            return true;
        } catch (Throwable $e) {
            Response::error('AUTH_ERROR', 'Authentication system error', 500, [
                'exception' => ($e instanceof Exception ? $e->getMessage() : 'error'),
            ]);
            return false;
        }
    }
}
