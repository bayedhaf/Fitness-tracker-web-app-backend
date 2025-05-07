#!/bin/bash

# Kill any existing PHP server on port 8080
kill -9 $(lsof -t -i:8080) 2>/dev/null

# Start PHP development server with CORS support
php -S localhost:8080 -t . router.php