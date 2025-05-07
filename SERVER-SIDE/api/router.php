<?php
// Set CORS headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers: *");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

// Route requests to appropriate PHP files
$request = $_SERVER['REQUEST_URI'];
if ($request === '/register.php' || $request === '/register') {
    require 'register.php';
} else {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'Endpoint not found']);
}