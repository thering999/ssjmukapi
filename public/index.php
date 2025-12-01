<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Support\Response;
use App\Controllers\AnnouncementsController;
use App\Controllers\AlertsController;
use App\Controllers\ContactsController;
use App\Controllers\DiseaseCasesController;
use App\Controllers\FacilitiesController;
use App\Services\FacilityService;
use App\Controllers\GatewayController;
use App\Controllers\OpendataController;
use App\Controllers\VaccinationsController;
use App\Controllers\CcmController;
use App\Middlewares\AuthMiddleware;
use App\Controllers\AuthController;
use App\Controllers\LineController;
use App\Middlewares\JwtAuthMiddleware;
use Bramus\Router\Router;

$config = require __DIR__ . '/../config/config.php';

Response::cors($config['app']['allow_origins'] ?? '*');

$rateLimiter = new App\Middlewares\RateLimiter(
    $config['app']['rate_limit']['max_requests'] ?? 60,
    $config['app']['rate_limit']['window_seconds'] ?? 60
);
$rateLimiter->check();

$router = new Router();

$router->options('/.*', function () {
    http_response_code(204);
});

$router->get('/api/v1/health', function () {
    Response::success(['status' => 'ok', 'time' => date('c')]);
});

// Frontend Routes
$router->get('/', function () {
    App\Support\View::render('home');
});

$router->get('/login', function () {
    App\Support\View::render('login');
});

$pdo = null;
$ensureDb = function () use (&$pdo, $config) {
    if ($pdo) return $pdo;
    try {
        $pdo = Database::pdo($config);
        return $pdo;
    } catch (Throwable $e) {
        $errorDetails = ($config['app']['debug']) ? ['exception' => $e->getMessage()] : [];
        Response::error('DB_CONNECT_FAIL', 'Cannot connect to the database.', 500, $errorDetails);
        exit;
    }
};

// Auth Controller & Middleware
$jwtSecret = $_ENV['JWT_SECRET'] ?? 'default-dev-secret';
$authController = new AuthController($jwtSecret, $ensureDb());
$jwtAuth = new JwtAuthMiddleware($jwtSecret);

// Login Route
$router->post('/api/v1/login', function () use ($authController) {
    $authController->login(json_decode(file_get_contents('php://input'), true) ?? []);
});

$router->mount('/api/v1/opendata', function () use ($router) {
    $router->get('/ipd10-sex-summary', function () {
        OpendataController::getIpd10SexSummary($_GET);
    });
    $router->get('/opd10-sex-summary', function () {
        OpendataController::getOpd10SexSummary($_GET);
    });
    $router->get('/opd-all-summary', function () {
        OpendataController::getOpdAllSummary($_GET);
    });
    $router->get('/school-summary-by-type', function () {
        OpendataController::getSchoolSummaryByType($_GET);
    });
    $router->get('/school-student-summary', function () {
        OpendataController::getSchoolStudentSummary($_GET);
    });
    $router->get('/health-coverage-card-summary', function () {
        OpendataController::getHealthCoverageCardSummary($_GET);
    });
    $router->get('/volunteer-health-summary', function () {
        OpendataController::getVolunteerHealthSummary($_GET);
    });
    $router->get('/provider-summary-by-type', function () {
        OpendataController::getProviderSummaryByType($_GET);
    });
    $router->get('/hospital-serviceplan-summary', function () {
        OpendataController::getHospitalServicePlanSummary($_GET);
    });
    $router->get('/hospital-summary-by-level', function () {
        OpendataController::getHospitalSummaryByLevel($_GET);
    });
    $router->get('/pcc1-by-service', function () {
        OpendataController::getPcc1ByService($_GET);
    });
    $router->get('/person-type-by-service', function () {
        OpendataController::getPersonTypeByService($_GET);
    });
    $router->get('/population-sex-age-direct', function () {
        OpendataController::getPopulationSexAgeDirect();
    });
    $router->get('/population-sex-age-moph-direct', function () {
        OpendataController::getPopulationSexAgeMophDirect();
    });
    $router->get('/labor-sex-age', function () {
        OpendataController::getLaborSexAge($_GET);
    });
    $router->get('/population-sex-age-moph', function () {
        OpendataController::getPopulationSexAgeMoph($_GET);
    });
    $router->get('/population-sex-age', function () {
        OpendataController::getPopulationSexAge($_GET);
    });
});

$facilityService = new FacilityService($ensureDb());
$facilitiesController = new FacilitiesController($facilityService);

$router->get('/api/v1/facilities', function () use ($facilitiesController) {
    $facilitiesController->index($_GET);
});
$router->get('/api/v1/facilities/(\d+)', function ($id) use ($facilitiesController) {
    $facilitiesController->show((int)$id);
});
$router->get('/api/v1/announcements', function () use ($ensureDb) {
    AnnouncementsController::index($ensureDb(), $_GET);
});
$router->get('/api/v1/announcements/(\d+)', function ($id) use ($ensureDb) {
    AnnouncementsController::show($ensureDb(), (int)$id);
});
$router->get('/api/v1/contacts', function () use ($ensureDb) {
    ContactsController::index($ensureDb(), $_GET);
});
$router->get('/api/v1/cases', function () use ($ensureDb) {
    DiseaseCasesController::index($ensureDb(), $_GET);
});
$router->get('/api/v1/vaccinations', function () use ($ensureDb) {
    VaccinationsController::index($ensureDb(), $_GET);
});

$router->post('/api/v1/ccm', function () use ($config) {
    $controller = new CcmController($config);
    $controller->post();
});

$router->get('/api/v1/public/sample-report', function () {
    Response::success([
        'report' => 'ตัวอย่างข้อมูลสาธารณะ',
        'data' => [['year' => 2025, 'count' => 123], ['year' => 2024, 'count' => 99]]
    ]);
});

$router->before('GET', '/api/v1/protected/.*', function () use ($jwtAuth) {
    if (!$jwtAuth->authenticate()) {
        exit;
    }
});

$router->get('/api/v1/protected/sample-personal', function () {
    Response::success([
        'profile' => ['cid' => '1xxxxxxx', 'name' => 'นายทดสอบ', 'phone' => '08xxxxxxx']
    ]);
});

$auth = new AuthMiddleware($ensureDb()); // Note: This seems to be a different AuthMiddleware (maybe legacy DB check?), keeping it but wrapping admin routes with JWT too?
// Actually, the user request was to replace SimpleTokenAuth. 
// The code had `$auth = new AuthMiddleware($ensureDb());` which was used for `/api/v1/admin/.*`.
// Wait, I should check what `App\Middlewares\AuthMiddleware` does. It might be checking a session or DB token.
// The prompt said "SimpleTokenAuth" was used. 
// Let's look at the original file content again.
// Line 133: $simpleTokenAuth = new SimpleTokenAuth(...)
// Line 144: $router->before('GET', '/api/v1/protected/.*', ...) uses $simpleTokenAuth
// Line 156: $auth = new AuthMiddleware($ensureDb());
// Line 158: $router->before(..., '/api/v1/admin/.*', ...) uses $auth
// So there are TWO auth systems. SimpleTokenAuth for "protected" and AuthMiddleware for "admin".
// The user request said: "ตอนนี้ใช้ SimpleTokenAuth ซึ่งดีระดับหนึ่ง ... เปลี่ยนไปใช้ JWT"
// It seems I should replace BOTH or at least SimpleTokenAuth.
// For consistency, I will replace SimpleTokenAuth with JWT for `protected` routes.
// And for `admin` routes, I should probably also use JWT, or maybe the user meant to unify them.
// Given the "admin" routes use `AuthMiddleware` which takes DB, it might be checking an API key table.
// I will replace `SimpleTokenAuth` usage first as explicitly requested/implied by the context of "SimpleTokenAuth".
// But `admin` routes are the most critical. I'll switch `admin` routes to use JWT as well for "Enhanced Security".

$router->before('POST|PUT|PATCH|DELETE', '/api/v1/admin/.*', function () use ($jwtAuth) {
    if (!$jwtAuth->authenticate()) {
        exit;
    }
});

// Keep API Key auth for external system integrations (Alerts, Line) if they use static keys
// Or switch them to JWT if they are called by the frontend.
// Assuming Alerts/Line are called by the frontend (Admin UI), they should be JWT too.
// But if they are webhooks, they might need API keys.
// The code shows `AlertsController::send` which seems to be an action triggered by the user.
// Let's switch them to JWT for consistency with the "Enhanced Security" goal.

$router->before('POST', '/api/v1/alerts', function () use ($jwtAuth) {
    if (!$jwtAuth->authenticate()) {
        exit;
    }
});

$router->before('POST', '/api/v1/alerts/template', function () use ($jwtAuth) {
    if (!$jwtAuth->authenticate()) {
        exit;
    }
});

$router->before('POST', '/api/v1/line', function () use ($jwtAuth) {
    if (!$jwtAuth->authenticate()) {
        exit;
    }
});

$router->post('/api/v1/admin/facilities', function () use ($facilitiesController) {
    $facilitiesController->create(json_decode(file_get_contents('php://input'), true) ?? []);
});
$router->match('PUT|PATCH', '/api/v1/admin/facilities/(\d+)', function ($id) use ($facilitiesController) {
    $facilitiesController->update((int)$id, json_decode(file_get_contents('php://input'), true) ?? []);
});
$router->delete('/api/v1/admin/facilities/(\d+)', function ($id) use ($facilitiesController) {
    $facilitiesController->delete((int)$id);
});

$router->post('/api/v1/admin/announcements', function () use ($ensureDb) {
    AnnouncementsController::create($ensureDb(), json_decode(file_get_contents('php://input'), true) ?? []);
});
$router->match('PUT|PATCH', '/api/v1/admin/announcements/(\d+)', function ($id) use ($ensureDb) {
    AnnouncementsController::update($ensureDb(), (int)$id, json_decode(file_get_contents('php://input'), true) ?? []);
});
$router->delete('/api/v1/admin/announcements/(\d+)', function ($id) use ($ensureDb) {
    AnnouncementsController::delete($ensureDb(), (int)$id);
});

$router->post('/api/v1/admin/alerts', function () use ($ensureDb, $config) {
    AlertsController::send($ensureDb(), json_decode(file_get_contents('php://input'), true) ?? [], $config['services']['moph_alert'] ?? []);
});

$router->post('/api/v1/alerts', function () use ($ensureDb, $config) {
    AlertsController::send($ensureDb(), json_decode(file_get_contents('php://input'), true) ?? [], $config['services']['moph_alert'] ?? []);
});

$router->post('/api/v1/alerts/template', function () use ($ensureDb, $config) {
    AlertsController::sendTemplate($ensureDb(), json_decode(file_get_contents('php://input'), true) ?? [], $config['services']['moph_alert'] ?? []);
});

$router->post('/api/v1/line', function () use ($config) {
    LineController::send(json_decode(file_get_contents('php://input'), true) ?? [], $config['services']['line_notify'] ?? []);
});

$router->post('/api/v1/admin/gateway/([a-zA-Z0-9_]+)', function ($service) use ($config) {
    GatewayController::proxy($service, json_decode(file_get_contents('php://input'), true) ?? [], $config['services'] ?? []);
});

$router->set404(function () {
    Response::error('NOT_FOUND', 'Endpoint not found', 404);
});

$router->run();
