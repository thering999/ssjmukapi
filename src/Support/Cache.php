<?php

namespace App\Support;

class Cache
{
    private static $cacheDir = __DIR__ . '/../../storage/cache/data';

    public static function remember(string $key, int $ttlSeconds, callable $callback)
    {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0777, true);
        }

        $file = self::$cacheDir . '/' . md5($key) . '.json';

        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);

            if (isset($data['expires_at']) && $data['expires_at'] > time()) {
                return $data['payload'];
            }
        }

        $value = $callback();

        file_put_contents($file, json_encode([
            'expires_at' => time() + $ttlSeconds,
            'payload' => $value
        ]));

        return $value;
    }
}
