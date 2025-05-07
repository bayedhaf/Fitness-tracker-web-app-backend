<?php
require_once 'db.php';

class Exercise {
    private $collection;

    public function __construct() {
        $db = Database::getInstance()->getDb();
        $this->collection = $db->exercises;
    }

    public function getAllExercises($page = 1, $perPage = 8) {
        try {
            $skip = ($page - 1) * $perPage;
            
            $exercises = $this->collection->find(
                [],
                [
                    'limit' => $perPage,
                    'skip' => $skip,
                    'projection' => [
                        'title' => 1,
                        'images' => 1,
                        'level' => 1,
                        'rating' => 1,
                        'description' => 1
                    ]
                ]
            )->toArray();

            $total = $this->collection->countDocuments();

            return [
                'exercises' => $exercises,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to fetch exercises: " . $e->getMessage());
        }
    }

    public function getExerciseById($id) {
        try {
            $exercise = $this->collection->findOne([
                '_id' => new MongoDB\BSON\ObjectId($id)
            ]);

            if (!$exercise) {
                throw new Exception("Exercise not found");
            }

            return $exercise;
        } catch (Exception $e) {
            throw new Exception("Invalid exercise ID: " . $e->getMessage());
        }
    }

    public function searchExercises($query, $page = 1, $perPage = 8) {
        try {
            $skip = ($page - 1) * $perPage;
            
            $filter = [
                '$text' => ['$search' => $query]
            ];

            $exercises = $this->collection->find(
                $filter,
                [
                    'limit' => $perPage,
                    'skip' => $skip,
                    'projection' => [
                        'score' => ['$meta' => 'textScore'],
                        'title' => 1,
                        'images' => 1,
                        'level' => 1
                    ],
                    'sort' => ['score' => ['$meta' => 'textScore']]
                ]
            )->toArray();

            $total = $this->collection->countDocuments($filter);

            return [
                'exercises' => $exercises,
                'total' => $total,
                'query' => $query,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ];
        } catch (Exception $e) {
            throw new Exception("Search failed: " . $e->getMessage());
        }
    }
}
?>