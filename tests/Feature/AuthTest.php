<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\JwtAuthMiddleware;
use App\Support\Response;
use PDO;
use PDOStatement;

class AuthTest extends TestCase
{
    private $pdo;
    private $stmt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = $this->createMock(PDO::class);
        $this->stmt = $this->createMock(PDOStatement::class);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAuthMiddlewareRejectsJwt()
    {
        // Mock Response::error to avoid exit()
        // Note: In a real integration test we would hit the endpoint. 
        // Here we are unit testing the middleware logic via a mock if possible, 
        // but since Response::error calls http_response_code and might exit, 
        // we might need to rely on the fact that we modified the code to return false.
        
        // However, Response::error usually outputs JSON. 
        // For this test, let's instantiate the middleware and call authenticate.
        
        // We need to mock $_SERVER['HTTP_AUTHORIZATION']
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.e30.signature';
        
        $middleware = new AuthMiddleware($this->pdo);
        
        // We expect it to return false because it detects a JWT
        ob_start(); // Capture output
        $result = $middleware->authenticate();
        $output = ob_get_clean();
        
        $this->assertFalse($result);
        $this->assertStringContainsString('JWT authentication not supported', $output);
    }

    public function testAuthMiddlewareAcceptsApiKey()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer valid_api_key';
        
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn(['id' => 1, 'name' => 'Test Key', 'is_active' => 1]);
        
        $middleware = new AuthMiddleware($this->pdo);
        
        $result = $middleware->authenticate();
        
        $this->assertTrue($result);
    }

    public function testJwtAuthMiddlewareAcceptsValidJwt()
    {
        $secret = 'test_secret';
        $payload = [
            'sub' => '123',
            'iat' => time(),
            'exp' => time() + 3600
        ];
        $jwt = \Firebase\JWT\JWT::encode($payload, $secret, 'HS256');
        
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $jwt;
        
        $middleware = new JwtAuthMiddleware($secret);
        
        $result = $middleware->authenticate();
        
        $this->assertTrue($result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testJwtAuthMiddlewareRejectsInvalidSignature()
    {
        $secret = 'test_secret';
        $payload = ['sub' => '123'];
        $jwt = \Firebase\JWT\JWT::encode($payload, 'wrong_secret', 'HS256');
        
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $jwt;
        
        $middleware = new JwtAuthMiddleware($secret);
        
        ob_start();
        $result = $middleware->authenticate();
        ob_get_clean();
        
        $this->assertFalse($result);
    }
}
