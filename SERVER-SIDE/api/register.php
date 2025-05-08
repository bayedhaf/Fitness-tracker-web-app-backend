<?php

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Max-Age: 3600");
header("Content-Type: application/json");
require_once __DIR__ . '/User.php';

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

$response = [
    'success' => false,
    'message' => '',
    'errors' => [],
    'data' => null
];

try {
 
    $json = file_get_contents('php://input');
    if (empty($json)) {
        throw new Exception("Request body is empty", 400);
    }
    
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON format", 400);
    }

  
    $requiredFields = ['firstName', 'lastName', 'email', 'password', 'age', 'weight', 'height'];
    $validationErrors = [];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $validationErrors[$field] = 'This field is required';
        }
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $validationErrors['email'] = 'Invalid email format';
    }
    
    if (strlen($data['password']) < 6) {
        $validationErrors['password'] = 'Password must be at least 6 characters';
    }
    
    $numericFields = ['age', 'weight', 'height'];
    foreach ($numericFields as $field) {
        if (!is_numeric($data[$field])) {
            $validationErrors[$field] = 'Must be a number';
        }
    }
    
    if (!empty($validationErrors)) {
        $response['errors'] = $validationErrors;
        throw new Exception("Validation failed", 400);
    }

    // Create user
    $user = new User();
    $createdUser = $user->createUser([
        'firstName' => trim($data['firstName']),
        'lastName' => trim($data['lastName']),
        'email' => strtolower(trim($data['email'])),
        'password' => $data['password'],
        'age' => (int)$data['age'],
        'weight' => (float)$data['weight'],
        'height' => (float)$data['height'],
        'image' => $data['image'] ?? 'https://i.pravatar.cc/300'
    ]);

    $response = [
        'success' => true,
        'message' => 'Registration successful',
        'data' => $createdUser
    ];
    http_response_code(201);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 400);
    $response['message'] = $e->getMessage();
    error_log("Registration Error: " . $e->getMessage());
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
