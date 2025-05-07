<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
header("Content-Type: application/json");

require_once __DIR__ . '/db.php';
require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONDocument;
use MongoDB\Model\BSONArray;


ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
header("Content-Type: application/json");

require_once __DIR__ . '/db.php';
require __DIR__ . '/vendor/autoload.php';

class User {
    private $usersCollection;
    private $blacklistCollection;
    private $dashboardSnapshots;
    private $secretKey;
    private $cacheTtl;

    public function __construct() {
        $db = Database::getInstance()->getDb();
        $this->usersCollection = $db->users;
        $this->blacklistCollection = $db->token_blacklist;
        $this->dashboardSnapshots = $db->dashboard_snapshots;
        $this->secretKey = getenv('JWT_SECRET') ?: 'secretkey123';
        $this->cacheTtl = 300;
    }

    private function isValidMongoId($id): bool {
        try {
            new ObjectId($id);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function clearUserCache($userId): void {
        foreach (['user_', 'dashboard_', 'progress_'] as $prefix) {
            apcu_delete($prefix . $userId);
        }
    }

    private function convertBsonToArray($document) {
        if ($document instanceof BSONDocument || $document instanceof BSONArray) {
            $document = $document->getArrayCopy();
        }

        if (is_array($document) || is_object($document)) {
            $result = [];
            foreach ($document as $key => $value) {
                $result[$key] = $this->convertBsonToArray($value);
            }
            return $result;
        }

        return $document;
    }

    public function generateToken($userId, array $additionalClaims = []): string {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600;
        $payload = array_merge([
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'sub' => (string)$userId,
            'iss' => $_SERVER['HTTP_HOST'] ?? 'fitness-app'
        ], $additionalClaims);

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function validateToken(string $token): ?array {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            return (array)$decoded;
        } catch (Exception $e) {
            error_log('JWT Validation Error: ' . $e->getMessage());
            return null;
        }
    }

    public function isTokenBlacklisted(string $token): bool {
        if (empty($token)) return true;
        $tokenHash = hash('sha256', $token);
        return $this->blacklistCollection->countDocuments([
            'tokenHash' => $tokenHash,
            'expiresAt' => ['$gt' => new UTCDateTime()]
        ]) > 0;
    }

    public function blacklistToken(string $token, ?int $expiryTime = null): bool {
        if (empty($token)) return false;
        $decoded = $this->validateToken($token);
        if (!$decoded) return false;

        $expiryTime = $expiryTime ?: ($decoded['exp'] ?? time() + 3600);
        $tokenHash = hash('sha256', $token);

        $this->blacklistCollection->insertOne([
            'tokenHash' => $tokenHash,
            'userId' => new ObjectId($decoded['sub']),
            'expiresAt' => new UTCDateTime($expiryTime * 1000),
            'blacklistedAt' => new UTCDateTime(),
            'reason' => 'logout'
        ]);
        return true;
    }

    public function createUser(array $userData): array {
        $required = ['firstName', 'lastName', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                throw new Exception("Missing required field: $field", 400);
            }
        }

        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format", 400);
        }

        if ($this->findByEmail($userData['email'])) {
            throw new Exception("Email already registered", 409);
        }

        $userDoc = [
            'firstName' => $userData['firstName'],
            'lastName' => $userData['lastName'],
            'email' => strtolower($userData['email']),
            'password' => password_hash($userData['password'], PASSWORD_DEFAULT),
            'age' => $userData['age'] ?? null,
            'weight' => $userData['weight'] ?? null,
            'height' => $userData['height'] ?? null,
            'image' => $userData['image'] ?? 'https://i.pravatar.cc/300',
            'createdAt' => new UTCDateTime(),
            'updatedAt' => new UTCDateTime(),
            'role' => $userData['role'] ?? 'user',
            'stats' => ['workoutsCompleted' => 0, 'totalCalories' => 0, 'currentStreak' => 0],
            'verified' => false,
            'workoutHistory' => []
        ];

        $result = $this->usersCollection->insertOne($userDoc);

        return [
            'id' => (string)$result->getInsertedId(),
            'firstName' => $userDoc['firstName'],
            'email' => $userDoc['email'],
            'token' => $this->generateToken($result->getInsertedId())
        ];
    }

    public function updateProfile(string $userId, array $updateData): array {
        if (!$this->isValidMongoId($userId)) {
            throw new Exception("Invalid user ID format", 400);
        }

        $updateData = array_filter($updateData, fn($value) => $value !== null);

        if (isset($updateData['password'])) {
            if (strlen($updateData['password']) < 6) {
                throw new Exception("Password must be at least 6 characters", 400);
            }
            $updateData['password'] = password_hash($updateData['password'], PASSWORD_DEFAULT);
        }

        $updateData['updatedAt'] = new UTCDateTime();

        $result = $this->usersCollection->updateOne(
            ['_id' => new ObjectId($userId)],
            ['$set' => $updateData]
        );

        if ($result->getModifiedCount() === 0) {
            throw new Exception("No changes made", 304);
        }

        $this->clearUserCache($userId);
        return $this->findById($userId);
    }

    public function findById(string $id): array {
        if (!$this->isValidMongoId($id)) throw new Exception("Invalid user ID", 400);

        $cacheKey = 'user_' . $id;
        if (apcu_exists($cacheKey)) return apcu_fetch($cacheKey);

        $user = $this->usersCollection->findOne(['_id' => new ObjectId($id)]);
        if (!$user) throw new Exception("User not found", 404);

        $userArray = $this->convertBsonToArray($user);
        apcu_store($cacheKey, $userArray, $this->cacheTtl);
        return $userArray;
    }

    public function findByEmail(string $email): ?array {
        $email = strtolower($email);
        $user = $this->usersCollection->findOne(['email' => $email]);
        return $user ? $this->convertBsonToArray($user) : null;
    }

    public function getProfile(string $userId): array {
        $user = $this->findById($userId);
        
        return [
            '_id' => $user['_id'],
            'firstName' => $user['firstName'],
            'lastName' => $user['lastName'],
            'email' => $user['email'],
            'age' => $user['age'] ?? null,
            'weight' => $user['weight'] ?? null,
            'height' => $user['height'] ?? null,
            'image' => $user['image'] ?? 'https://i.pravatar.cc/300'
        ];
    }

    public function getDashboardData(string $userId): array {
        if (!$this->isValidMongoId($userId)) {
            throw new Exception("Invalid user ID", 400);
        }

        $user = $this->usersCollection->findOne(
            ['_id' => new ObjectId($userId)],
            ['projection' => [
                'firstName' => 1, 'lastName' => 1, 'stats' => 1,
                'workoutHistory' => 1, 'age' => 1, 'weight' => 1,
                'height' => 1, 'image' => 1, 'createdAt' => 1
            ]]
        );

        if (!$user) throw new Exception("User not found", 404);

        $timestamp = isset($user['createdAt']) && $user['createdAt'] instanceof UTCDateTime
            ? $user['createdAt']->toDateTime()->getTimestamp()
            : null;

        $data = $this->convertBsonToArray($user);
        $data['stats']['totalWorkouts'] = count($data['workoutHistory'] ?? []);
        $data['stats']['joinedDaysAgo'] = $timestamp ? floor((time() - $timestamp) / 86400) : null;

        return $data;
    }
     
    

}


