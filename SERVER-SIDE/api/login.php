<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once 'User.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (empty($data['email']) || empty($data['password'])) {
        throw new Exception("Email and password are required");
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    $user = new User();
    $existingUser = $user->findByEmail($data['email']);

    if (!$existingUser) {
        throw new Exception("User not found");
    }

    if (!password_verify($data['password'], $existingUser['password'])) {
        throw new Exception("Invalid credentials");
    }

    
    if (isset($existingUser['_id']) && is_object($existingUser['_id']) && method_exists($existingUser['_id'], '__toString')) {
        $userId = (string) $existingUser['_id'];
    } elseif (isset($existingUser['_id']['$oid'])) {
        $userId = $existingUser['_id']['$oid'];
    } else {
        $userId = $existingUser['_id'] ?? '';
    }

    
    $token = $user->generateToken($userId);

    $userData = [
        'id' => $userId,
        'firstName' => $existingUser['firstName'] ?? '',
        'lastName' => $existingUser['lastName'] ?? '',
        'email' => $existingUser['email'] ?? '',
        'age' => $existingUser['age'] ?? '',
        'weight' => $existingUser['weight'] ?? '',
        'height' => $existingUser['height'] ?? '',
        'image' => $existingUser['image'] ?? ''
    ];

    $response = [
        'success' => true,
        'message' => 'Login successful',
        'data' => $userData,
        'token' => $token
    ];

    http_response_code(200);
} catch (Exception $e) {
    http_response_code(401);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
