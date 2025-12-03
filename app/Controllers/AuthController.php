<?php

namespace App\Controllers;

use App\Config\Database;
use Firebase\JWT\JWT;
use PDO;

class AuthController extends BaseController
{
    public function login()
    {
        $input = $this->getInput();
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->errorResponse('Username and password are required');
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $payload = [
                'iss' => 'ssjmuk-api',
                'aud' => 'ssjmuk-admin',
                'iat' => time(),
                'exp' => time() + (60 * 60 * 24), // 1 day
                'sub' => $user['id'],
                'role' => $user['role']
            ];

            $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

            $this->successResponse([
                'token' => $jwt,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ]
            ], 'Login successful');
        } else {
            $this->errorResponse('Invalid credentials', 401);
        }
    }
}
