<?php
header("Content-Type: application/json");
require_once 'Exercise.php';

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    if (!isset($_GET['id'])) {
        throw new Exception("Exercise ID is required");
    }

    $exercise = new Exercise();
    $exerciseData = $exercise->getExerciseById($_GET['id']);

    $response = [
        'success' => true,
        'data' => $exerciseData
    ];

    http_response_code(200);
} catch (Exception $e) {
    http_response_code(404);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>