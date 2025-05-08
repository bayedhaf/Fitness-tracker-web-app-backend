<?php
declare(strict_types=1);

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);


require_once __DIR__ . '/authMiddleware.php';
require_once __DIR__ . '/User.php';


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [
    'success' => false,
    'data' => null,
    'message' => '',
    'meta' => [
        'apiVersion' => '1.2.0',
        'requestId' => uniqid()
    ]
];

try {
   
    $userId = authenticateRequest();

    
    $rawInput = file_get_contents('php://input');
    $input = [];
    if (!empty($rawInput)) {
        $input = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input', 400);
        }
    }

 
    if (!isset($input['userId']) || $input['userId'] !== $userId) {
        $input['userId'] = $userId;
    }

  
    $userService = new User();
    $dashboardData = $userService->getDashboardData($userId);

    
    if (empty($dashboardData['workoutHistory'])) {
        $dashboardData['workoutHistory'] = [
            [
                'name' => 'Morning Run',
                'duration' => '30 min',
                'calories' => 320,
                'date' => date('M d', strtotime('-2 days'))
            ],
            [
                'name' => 'HIIT Workout',
                'duration' => '45 min',
                'calories' => 450,
                'date' => date('M d', strtotime('-4 days'))
            ],
            [
                'name' => 'Yoga Session',
                'duration' => '60 min',
                'calories' => 200,
                'date' => date('M d', strtotime('-7 days'))
            ]
        ];
    }

 
    $response['success'] = true;
    $response['data'] = $dashboardData;
    $response['message'] = 'Dashboard data retrieved successfully';
    $response['meta']['userId'] = $userId;
    $response['meta']['generatedAt'] = date('c');

    http_response_code(200);

} catch (Throwable $e) {
  
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;

    $response['message'] = $e->getMessage();
    $response['error'] = [
        'type' => get_class($e),
        'code' => $statusCode
    ];

    error_log(sprintf(
        '[%s] %s in %s:%d',
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    http_response_code($statusCode);
} finally {

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');

  
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
}
