<?php
// Simple .env loader and config provider

function loadEnv(string $file): array {
    $vars = [];
    if (!is_file($file)) {
        return $vars;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        // strip quotes
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
            $val = substr($val, 1, -1);
        }
        $vars[$key] = $val;
    }
    return $vars;
}

function env(string $key, $default = null) {
    static $vars = null;
    if ($vars === null) {
        $projectRoot = dirname(__DIR__);
        $envFile = $projectRoot . DIRECTORY_SEPARATOR . '.env';
        $vars = is_file($envFile) ? loadEnv($envFile) : [];
    }
    return $vars[$key] ?? $default;
}


return [
    'app' => [
        'env' => env('APP_ENV', 'local'),
        'debug' => (bool) env('APP_DEBUG', 0),
        'allow_origins' => env('APP_ALLOW_ORIGINS', '*'),
        'rate_limit' => [
            'max_requests' => (int) env('RATE_LIMIT_MAX', 60),
            'window_seconds' => (int) env('RATE_LIMIT_WINDOW', 60),
        ],
    ],
    'db' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => (int) env('DB_PORT', 3306),
        'name' => env('DB_NAME', 'ssjmukapi'),
        'user' => env('DB_USER', 'root'),
        'pass' => env('DB_PASS', '123456'),
        'charset' => 'utf8mb4',
    ],
    'services' => [
        'moph_alert' => [
            'endpoint' => env('MOPH_ALERT_ENDPOINT', env('ALERT_ENDPOINT', 'https://morpromt2c.moph.go.th/alert/v3.1/messages')),
            'client_key' => env('MOPH_ALERT_CLIENT_KEY', env('ALERT_CLIENT_KEY', '')),
            'secret_key' => env('MOPH_ALERT_SECRET_KEY', env('ALERT_SECRET_KEY', '')),
            'timeout' => (int) env('MOPH_ALERT_TIMEOUT', env('ALERT_TIMEOUT', 15)),
        ],
        'moph_notify' => [
            'endpoint' => env('MOPH_NOTIFY_ENDPOINT', env('NOTIFY_ENDPOINT', 'https://morpromt2f.moph.go.th/api/notify/messages')),
            'client_key' => env('MOPH_NOTIFY_CLIENT_KEY', env('NOTIFY_CLIENT_KEY', '')),
            'secret_key' => env('MOPH_NOTIFY_SECRET_KEY', env('NOTIFY_SECRET_KEY', '')),
            'timeout' => (int) env('MOPH_NOTIFY_TIMEOUT', env('NOTIFY_TIMEOUT', 15)),
        ],
        'moph_ccm' => [
            'endpoint' => env('MOPH_CCM_ENDPOINT', env('CCM_ENDPOINT', 'https://ccm-api.moph.go.th/api/v1/messages')),
            'client_key' => env('MOPH_CCM_CLIENT_KEY', env('CCM_CLIENT_KEY', '')),
            'secret_key' => env('MOPH_CCM_SECRET_KEY', env('CCM_SECRET_KEY', '')),
            'timeout' => (int) env('MOPH_CCM_TIMEOUT', env('CCM_TIMEOUT', 15)),
        ],
        'line_notify' => [
            'notify_token' => env('LINE_NOTIFY_TOKEN', ''),
            'timeout' => (int) env('LINE_NOTIFY_TIMEOUT', 15),
        ],
    ],
];
