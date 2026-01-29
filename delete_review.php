<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

$reviewer_id = $_SESSION['user_id'];
$review_id = $conn->escapeString($_POST['review_id']);

if (empty($review_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing review ID']);
    exit();
}

// Only allow deleting own reviews
$stmt = $conn->prepare("DELETE FROM reviews WHERE id = :id AND reviewer_id = :rid");
$stmt->bindValue(':id', $review_id, SQLITE3_INTEGER);
$stmt->bindValue(':rid', $reviewer_id, SQLITE3_INTEGER);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Review deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete review']);
}
?>
