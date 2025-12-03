<?php

// Path Helper
$possibleRoots = [
    __DIR__ . '/..', // Standard: public/index.php -> project root
    __DIR__,         // Flat: index.php at project root
    $_SERVER['DOCUMENT_ROOT'] . '/..',
    $_SERVER['DOCUMENT_ROOT']
];

$rootPath = null;
foreach ($possibleRoots as $path) {
    if (file_exists($path . '/routes/api.php')) {
        $rootPath = $path;
        break;
    }
}

if ($rootPath === null) {
    header('Content-Type: text/plain');
    echo "CRITICAL ERROR: Cannot find project root.\n";
    echo "Current Directory: " . __DIR__ . "\n";
    echo "Searched for routes/api.php in:\n";
    foreach ($possibleRoots as $path) {
        echo "- " . realpath($path) . " (" . $path . ")\n";
    }
    echo "\nDirectory Contents of " . __DIR__ . ":\n";
    print_r(scandir(__DIR__));
    exit(1);
}

require_once $rootPath . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($rootPath);
$dotenv->load();

// CORS Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$router = new \Bramus\Router\Router();

// Load Routes
require_once $rootPath . '/routes/api.php';

$router->run();
