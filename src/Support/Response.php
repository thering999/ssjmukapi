<?php

namespace App\Support;

class Response
{
    public static function json($payload, int $status = 200, array $headers = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        foreach ($headers as $k => $v) {
            header($k . ': ' . $v);
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    public static function success($data = null, array $meta = [], int $status = 200, array $headers = []): void
    {
        self::json([
            'success' => true,
            'meta' => (object) $meta,
            'data' => $data,
        ], $status, $headers);
    }

    public static function error(string $code, string $message, int $status = 400, array $extra = []): void
    {
        self::json([
            'success' => false,
            'error' => array_merge([
                'code' => $code,
                'message' => $message,
            ], $extra),
        ], $status);
    }

    public static function cors(string $allowOrigins = '*'): void
    {
        header('Access-Control-Allow-Origin: ' . $allowOrigins);
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 600');
    }

    public static function paginationLinks(string $baseUrl, int $page, int $perPage, int $total): array
    {
        $links = [];
        $lastPage = (int) ceil($total / $perPage);

        // Self
        $links[] = "<{$baseUrl}?page={$page}&per_page={$perPage}>; rel=\"self\"";

        // First
        $links[] = "<{$baseUrl}?page=1&per_page={$perPage}>; rel=\"first\"";

        // Last
        $links[] = "<{$baseUrl}?page={$lastPage}&per_page={$perPage}>; rel=\"last\"";

        // Prev
        if ($page > 1) {
            $prevPage = $page - 1;
            $links[] = "<{$baseUrl}?page={$prevPage}&per_page={$perPage}>; rel=\"prev\"";
        }

        // Next
        if ($page < $lastPage) {
            $nextPage = $page + 1;
            $links[] = "<{$baseUrl}?page={$nextPage}&per_page={$perPage}>; rel=\"next\"";
        }

        return ['Link' => implode(', ', $links)];
    }

    public static function cacheHeaders(int $maxAge = 300, bool $public = true): array
    {
        $directive = $public ? 'public' : 'private';
        return [
            'Cache-Control' => "{$directive}, max-age={$maxAge}",
            'Expires' => gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT',
        ];
    }

    public static function etag(string $content): array
    {
        $etag = '"' . md5($content) . '"';

        // Check if client has matching ETag
        $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ($ifNoneMatch === $etag) {
            http_response_code(304);
            exit;
        }

        return ['ETag' => $etag];
    }
}
