<?php
require 'vendor/autoload.php';

class Database {
    private static $instance = null;
    private $client;
    private $db;

    private function __construct() {
        try {
            $this->client = new MongoDB\Client("mongodb+srv://bayedhaf:baye1234@fitnessapp.laqxqeu.mongodb.net/?retryWrites=true&w=majority&appName=FitnessApp");
            $this->db = $this->client->fitness_app;
        } catch (MongoDB\Driver\Exception\Exception $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getDb() {
        return $this->db;
    }
}
?>