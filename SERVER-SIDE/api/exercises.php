<?php
header("Content-Type: application/json");
require_once 'Exercise.php';

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    $exercise = new Exercise();
    $page = $_GET['page'] ?? 1;
    $perPage = $_GET['perPage'] ?? 8;
    $query = $_GET['query'] ?? null;

    if ($query) {
        $result = $exercise->searchExercises($query, $page, $perPage);
    } else {
        $result = $exercise->getAllExercises($page, $perPage);
    }

    $response = [
        'success' => true,
        'data' => [
            'exercises' => $result['exercises'],
            'pagination' => [
                'currentPage' => (int)$result['page'],
                'perPage' => (int)$result['perPage'],
                'total' => $result['total'],
                'totalPages' => $result['totalPages']
            ]
        ]
    ];

    http_response_code(200);
} catch (Exception $e) {
    http_response_code(404);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>