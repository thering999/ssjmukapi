<?php

// Path Helper
$rootPath = __DIR__;

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
