<?php

namespace App\Middlewares;

use App\Support\Response;

class RateLimiter
{
    private $maxRequests;
    private $windowSeconds;
    private $storageDir;

    public function __construct(int $maxRequests = 100, int $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->storageDir = __DIR__ . '/../../storage/cache/ratelimit';

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0777, true);
        }
    }

    public function check(): bool
    {
        $key = $this->getClientKey();
        $file = $this->storageDir . '/' . $key . '.json';
        $now = time();

        // Default bucket
        $bucket = ['count' => 0, 'reset' => $now + $this->windowSeconds];

        // Read existing bucket
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            if ($content) {
                $data = json_decode($content, true);
                if (is_array($data)) {
                    $bucket = $data;
                }
            }
        }

        // Reset if window expired
        if ($now >= $bucket['reset']) {
            $bucket['count'] = 0;
            $bucket['reset'] = $now + $this->windowSeconds;
        }

        $bucket['count']++;

        // Save bucket
        file_put_contents($file, json_encode($bucket));

        if ($bucket['count'] > $this->maxRequests) {
            $this->sendRateLimitHeaders($bucket);
            Response::error(
                'RATE_LIMIT_EXCEEDED',
                'Too many requests. Please try again later.',
                429,
                ['retry_after' => $bucket['reset'] - $now]
            );
            exit;
        }

        $this->sendRateLimitHeaders($bucket);
        return true;
    }

    private function getClientKey(): string
    {
        // Use IP address as identifier (could be enhanced with API key)
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return md5($ip . $userAgent);
    }

    private function sendRateLimitHeaders(array $bucket): void
    {
        header('X-RateLimit-Limit: ' . $this->maxRequests);
        header('X-RateLimit-Remaining: ' . max(0, $this->maxRequests - $bucket['count']));
        header('X-RateLimit-Reset: ' . $bucket['reset']);
    }
}
