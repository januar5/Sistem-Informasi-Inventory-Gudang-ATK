<?php
session_start();

// Enable error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define Base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$domain = $_SERVER['HTTP_HOST'];
$folder = dirname($_SERVER['PHP_SELF']); // Adjust if needed
if ($folder == '\\' || $folder == '/') $folder = '';
define('BASE_URL', $protocol . '://' . $domain . $folder);

// Autoload (Simple)
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/helpers/Session.php';
require_once __DIR__ . '/helpers/Helper.php';
require_once __DIR__ . '/helpers/Csrf.php';
require_once __DIR__ . '/helpers/Validation.php';

// Middleware
require_once __DIR__ . '/middleware/AuthMiddleware.php';
require_once __DIR__ . '/middleware/AdminMiddleware.php';
require_once __DIR__ . '/middleware/PetugasMiddleware.php';


// If composer is installed
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Simple Router
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($base_path, '', $request_uri);
$path = parse_url($path, PHP_URL_PATH);
$path = trim($path, '/');

// Default Route
if (empty($path)) {
    $path = 'dashboard';
}

$segments = explode('/', $path);
$segment_0 = strtolower($segments[0]);
if ($segment_0 === 'incoming') {
    $controller_name = 'IncomingItemController';
} elseif ($segment_0 === 'outgoing') {
    $controller_name = 'OutgoingItemController';
} else {
    $controller_name = ucfirst($segment_0) . 'Controller';
}
$method_name = isset($segments[1]) ? $segments[1] : 'index';
$param = isset($segments[2]) ? $segments[2] : null;

$controller_file = __DIR__ . '/controllers/' . $controller_name . '.php';

if (file_exists($controller_file)) {
    require_once $controller_file;
    if (class_exists($controller_name)) {
        $controller = new $controller_name();
        if (method_exists($controller, $method_name)) {
            if ($param !== null) {
                $controller->$method_name($param);
            } else {
                $controller->$method_name();
            }
        } else {
            // Method not found
            require_once __DIR__ . '/views/errors/404.php';
        }
    } else {
         require_once __DIR__ . '/views/errors/404.php';
    }
} else {
    // Check if it's an Auth route that doesn't follow the exact pattern
    if ($path === 'login' || $path === 'logout') {
        require_once __DIR__ . '/controllers/AuthController.php';
        $controller = new AuthController();
        if ($path === 'login') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->processLogin();
            } else {
                $controller->login();
            }
        } else if ($path === 'logout') {
            $controller->logout();
        }
    } else {
        // Controller not found
        if(file_exists(__DIR__ . '/views/errors/404.php')){
            require_once __DIR__ . '/views/errors/404.php';
        } else {
            echo "404 Not Found";
        }
    }
}
