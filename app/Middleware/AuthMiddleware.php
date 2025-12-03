<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Config\Database;
use PDO;

class AuthMiddleware
{
    public function handleApiKey()
    {
        $headers = getallheaders();
        $apiKey = $headers['X-API-KEY'] ?? null;

        if (!$apiKey) {
            $this->unauthorized('API Key missing');
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM api_keys WHERE key_value = ? AND is_active = 1");
        $stmt->execute([$apiKey]);

        if (!$stmt->fetch()) {
            $this->unauthorized('Invalid API Key');
        }
    }

    public function handleJwt()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $this->unauthorized('Token missing');
        }

        $jwt = $matches[1];
        $secret = $_ENV['JWT_SECRET'];

        try {
            $decoded = JWT::decode($jwt, new Key($secret, 'HS256'));
            // Optionally attach user data to request if needed
            // $_REQUEST['user'] = $decoded;
        } catch (\Exception $e) {
            $this->unauthorized('Invalid Token: ' . $e->getMessage());
        }
    }

    private function unauthorized($message)
    {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => true, 'message' => $message]);
        exit();
    }
}
