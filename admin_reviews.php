<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

// Ideally add admin check here
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { ... }

$action = $_GET['action'] ?? '';

if ($action == 'fetch_all') {
    $sql = "SELECT r.id, r.rating, r.comment, r.created_at, u.name as reviewer_name, p.name as provider_name 
            FROM reviews r 
            JOIN users u ON r.reviewer_id = u.id 
            JOIN users p ON r.provider_id = p.id 
            ORDER BY r.created_at DESC";
    $result = $conn->query($sql);
    
    $reviews = [];
    while($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $reviews[] = $row;
    }
    echo json_encode(['status' => 'success', 'reviews' => $reviews]);

} elseif ($action == 'delete') {
    if (!isset($_POST['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'ID required']);
        exit();
    }
    $id = $conn->escapeString($_POST['id']);
    $conn->exec("DELETE FROM reviews WHERE id = $id");
    echo json_encode(['status' => 'success', 'message' => 'Review deleted']);

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>
