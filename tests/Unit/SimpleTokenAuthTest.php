<?php

namespace Tests\Unit;
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Middlewares\SimpleTokenAuth;

class SimpleTokenAuthTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up global state
        unset($_SERVER['HTTP_X_API_TOKEN']);
    }

    public function test_authenticate_returns_true_with_valid_token()
    {
        // Simulate header via $_SERVER fallback
        $_SERVER['HTTP_X_API_TOKEN'] = 'valid-token';
        
        $auth = new SimpleTokenAuth(['valid-token']);
        
        $this->assertTrue($auth->authenticate());
    }

    public function test_authenticate_returns_false_with_invalid_token()
    {
        $_SERVER['HTTP_X_API_TOKEN'] = 'invalid-token';
        
        $auth = new SimpleTokenAuth(['valid-token']);
        
        // Suppress output from echo
        ob_start();
        $result = $auth->authenticate();
        ob_end_clean();

        $this->assertFalse($result);
    }

    public function test_authenticate_returns_false_with_missing_token()
    {
        unset($_SERVER['HTTP_X_API_TOKEN']);
        
        $auth = new SimpleTokenAuth(['valid-token']);
        
        ob_start();
        $result = $auth->authenticate();
        ob_end_clean();

        $this->assertFalse($result);
    }
}
