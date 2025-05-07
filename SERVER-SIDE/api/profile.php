<?php
declare(strict_types=1);


error_reporting(E_ALL);
ini_set('display_errors', '1');


header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 3600");
header("Content-Type: application/json");

require_once __DIR__ . '/User.php';

require_once __DIR__ . '/authMiddleware.php';

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    
    $userId = authenticateRequest(); 

    $userService = new User();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            $userData = $userService->getProfile($userId);

            if (!$userData) {
                throw new Exception("User not found", 404);
            }

            $response = [
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => [
                    'id' => $userData['_id'],
                    'firstName' => $userData['firstName'],
                    'lastName' => $userData['lastName'],
                    'email' => $userData['email'],
                    'age' => $userData['age'],
                    'weight' => $userData['weight'],
                    'height' => $userData['height'],
                    'image' => $userData['image'] ?? 'https://i.pravatar.cc/300'
                ]
            ];
            http_response_code(200);
            break;

        case 'PUT':
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON format", 400);
            }

            if (empty($data['firstName']) || empty($data['lastName'])) {
                throw new Exception("First name and last name are required", 400);
            }

            if (!is_numeric($data['age']) || !is_numeric($data['weight']) || !is_numeric($data['height'])) {
                throw new Exception("Age, weight, and height must be numeric", 400);
            }

            $updateData = [
                'firstName' => $data['firstName'],
                'lastName' => $data['lastName'],
                'email' => $data['email'],
                'age' => (int)$data['age'],
                'weight' => (float)$data['weight'],
                'height' => (float)$data['height'],
                'updatedAt' => new MongoDB\BSON\UTCDateTime()
            ];

            if (!empty($data['password'])) {
                if (strlen($data['password']) < 6) {
                    throw new Exception("Password must be at least 6 characters", 400);
                }
                $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if (!empty($data['image'])) {
                $updateData['image'] = $data['image'];
            }

            $updatedUser = $userService->updateProfile($userId, $updateData);

            if (!$updatedUser) {
                throw new Exception("Profile update failed", 500);
            }

            $response = [
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $updatedUser['_id'],
                    'firstName' => $updatedUser['firstName'],
                    'lastName' => $updatedUser['lastName'],
                    'email' => $updatedUser['email'],
                    'age' => $updatedUser['age'],
                    'weight' => $updatedUser['weight'],
                    'height' => $updatedUser['height'],
                    'image' => $updatedUser['image'] ?? 'https://i.pravatar.cc/300'
                ]
            ];
            http_response_code(200);
            break;

        default:
            throw new Exception("Method Not Allowed", 405);
    }
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    $response['message'] = $e->getMessage();
    error_log("ERROR: " . $e->getMessage());
}

echo json_encode($response, JSON_PRETTY_PRINT);
