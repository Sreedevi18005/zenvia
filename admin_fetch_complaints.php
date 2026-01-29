<?php
include 'db_connect.php';
session_start();
header('Content-Type: application/json');

// Admin check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$result = $conn->query("SELECT * FROM complaints ORDER BY created_at DESC");

$complaints = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $complaints[] = $row;
}

echo json_encode(['status' => 'success', 'complaints' => $complaints]);
?>
