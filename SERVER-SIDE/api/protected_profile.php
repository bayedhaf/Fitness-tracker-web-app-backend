<?php
header("Content-Type: application/json");
require_once 'authMiddleware.php';
require_once 'User.php';

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Authenticate user
    $userId = authenticateRequest();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $user = new User();

    switch ($method) {
        case 'GET':
            $profile = $user->getProfile($userId);
            unset($profile['password']);
            $response = ['success' => true, 'data' => $profile];
            break;

        case 'PUT':
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            $updatedProfile = $user->updateProfile($userId, $data);
            unset($updatedProfile['password']);
            
            $response = [
                'success' => true,
                'message' => 'Profile updated',
                'data' => $updatedProfile
            ];
            break;

        default:
            throw new Exception("Method not allowed");
    }

    http_response_code(200);
} catch (Exception $e) {
    http_response_code(401);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>