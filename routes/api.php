<?php

use App\Middleware\AuthMiddleware;

$auth = new AuthMiddleware();

$router->get('/', function() {
    echo json_encode(['message' => 'Welcome to SSJ Mukdahan API']);
});

// Public Routes (Protected by API Key)
$router->mount('/api', function() use ($router, $auth) {
    
    $router->before('GET|POST|PUT|DELETE', '/.*', function() use ($auth) {
        $auth->handleApiKey();
    });

    $router->get('/test', function() {
        echo json_encode(['message' => 'API Key works!']);
    });

    // Facilities
    // $router->get('/facilities', 'App\Controllers\FacilitiesController@index');
    
    // Announcements
    // $router->get('/announcements', 'App\Controllers\AnnouncementsController@index');
    
    // ... other routes
});

// Admin Routes (Protected by JWT)
$router->mount('/admin', function() use ($router, $auth) {
    
    $router->post('/login', 'App\Controllers\AuthController@login');

    $router->mount('/dashboard', function() use ($router, $auth) {
        $router->before('GET|POST|PUT|DELETE', '/.*', function() use ($auth) {
            $auth->handleJwt();
        });

        $router->get('/stats', function() {
            echo json_encode(['message' => 'Admin stats here']);
        });
    });
});

$router->set404(function() {
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'Route not found']);
});
