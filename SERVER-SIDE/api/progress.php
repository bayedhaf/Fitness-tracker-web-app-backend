<?php
declare(strict_types=1);
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers: *");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

require_once __DIR__ . '/authMiddleware.php';
require_once __DIR__ . '/User.php';

class ProgressController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    private function calculateAverageDuration(array $workouts): string {
        if (empty($workouts)) return '0 min';

        $total = array_reduce($workouts, function($sum, $workout) {
            $duration = $workout['duration'] ?? 0;
            $duration = is_object($duration) && method_exists($duration, '__toString')
                ? (string)$duration : $duration;

            return $sum + (is_string($duration) ? (int)preg_replace('/[^0-9]/', '', $duration) : (int)$duration);
        }, 0);

        return round($total / count($workouts)) . ' min';
    }

    private function getWorkoutTypeDistribution(array $workouts): array {
        $types = [];
        foreach ($workouts as $workout) {
            $type = $workout['type'] ?? 'Other';
            $type = is_object($type) && method_exists($type, '__toString') ? (string)$type : $type;
            $types[$type] = ($types[$type] ?? 0) + 1;
        }
        arsort($types);
        return array_slice($types, 0, 5);
    }

    private function calculateStreak(array $workouts): int {
        
        return count($workouts); 
    }

    public function getProgress(): void {
        try {
            $userId = authenticateRequest();
            $userData = $this->userModel->findById($userId);
            $workouts = $userData['workouts'] ?? [];

            $response = [
                'success' => true,
                'message' => 'Progress data retrieved successfully',
                'data' => [
                    'averageDuration' => $this->calculateAverageDuration($workouts),
                    'typeDistribution' => $this->getWorkoutTypeDistribution($workouts),
                    'streak' => $this->calculateStreak($workouts),
                ]
            ];
            echo json_encode($response);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}


$controller = new ProgressController();
$controller->getProgress();
