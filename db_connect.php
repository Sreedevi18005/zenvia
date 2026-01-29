<?php
$dbFile = __DIR__ . '/zenvia.db';

try {
    $conn = new SQLite3($dbFile);
    $conn->enableExceptions(true);
    // Set busy timeout for SQLite to avoid locks
    $conn->busyTimeout(5000);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}
