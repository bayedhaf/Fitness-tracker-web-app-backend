<?php
require_once __DIR__ . '/User.php';
require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function authenticateRequest() {
    try {
        $authHeader = getAuthorizationHeader();
        if (!$authHeader) {
            throw new Exception('Authorization header missing', 401);
        }

        $token = extractBearerToken($authHeader);
        if (empty($token)) {
            throw new Exception('Token not provided', 401);
        }

        $user = new User();

        if ($user->isTokenBlacklisted($token)) {
            throw new Exception('Token revoked', 401);
        }

        $decoded = $user->validateToken($token);
        if (!$decoded) {
            throw new Exception('Invalid or expired token', 401);
        }

        if (!$user->findById($decoded['sub'])) {
            throw new Exception('User no longer exists', 401);
        }

        return $decoded['sub'];
    } catch (\DomainException $e) {
        throw new Exception('Malformed token: ' . $e->getMessage(), 401);
    } catch (\UnexpectedValueException $e) {
        throw new Exception('Invalid token: ' . $e->getMessage(), 401);
    } catch (Exception $e) {
        $code = $e->getCode() ?: 401;
        throw new Exception($e->getMessage(), $code);
    }
}

function authorizeRoles($allowedRoles = []) {
    $userId = authenticateRequest();
    $user = new User();
    $userData = $user->findById($userId);

    if (empty($allowedRoles)) {
        return $userId;
    }

    if (!$userData || !isset($userData['role']) || !in_array($userData['role'], $allowedRoles)) {
        throw new Exception('Insufficient permissions', 403);
    }

    return $userId;
}

function getAuthorizationHeader() {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return $_SERVER['HTTP_AUTHORIZATION'];
    }
    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }

    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            return $headers['Authorization'];
        }
    }

    return null;
}

function extractBearerToken($authHeader) {
    if (!preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        return null;
    }
    return trim($matches[1]);
}

function getAuthenticatedUser() {
    $userId = authenticateRequest();
    $user = new User();
    return $user->findById($userId);
}
