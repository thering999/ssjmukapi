<?php

namespace App\Controllers;

use App\Support\Response;
use Firebase\JWT\JWT;
use Rakit\Validation\Validator;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Auth", description: "Authentication endpoints")]
class AuthController
{
    private $secret;
    private $pdo;

    public function __construct(string $secret, \PDO $pdo)
    {
        $this->secret = $secret;
        $this->pdo = $pdo;
    }

    #[OA\Post(
        path: "/login",
        tags: ["Auth"],
        summary: "Login to get JWT token",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["username", "password"],
                properties: [
                    new OA\Property(property: "username", type: "string", example: "admin"),
                    new OA\Property(property: "password", type: "string", example: "password")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "token", type: "string"),
                        new OA\Property(property: "expires_in", type: "integer")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    public function login(array $data): void
    {
        $validator = new Validator();
        $validation = $validator->validate($data, [
            'username' => 'required',
            'password' => 'required'
        ]);

        if ($validation->fails()) {
            Response::error('AUTH_FAIL', 'Username and password are required', 400, $validation->errors()->toArray());
            return;
        }

        $username = $data['username'];
        $password = $data['password'];

        try {
            $stmt = $this->pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = :username LIMIT 1');
            $stmt->bindValue(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $issuedAt = time();
                $expirationTime = $issuedAt + 3600; // 1 hour
                $payload = [
                    'iss' => 'ssjmukapi',
                    'iat' => $issuedAt,
                    'exp' => $expirationTime,
                    'sub' => $user['username'],
                    'role' => $user['role'],
                    'uid' => $user['id']
                ];

                $jwt = JWT::encode($payload, $this->secret, 'HS256');

                Response::success([
                    'token' => $jwt,
                    'expires_in' => 3600
                ]);
                return;
            }
        } catch (\Throwable $e) {
            // Log error if needed
        }

        Response::error('AUTH_FAIL', 'Invalid username or password', 401);
    }
}
