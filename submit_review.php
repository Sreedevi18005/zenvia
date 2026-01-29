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
$provider_id = $conn->escapeString($_POST['provider_id']);
$booking_id = isset($_POST['booking_id']) ? $conn->escapeString($_POST['booking_id']) : null;
$rating = $conn->escapeString($_POST['rating']);
$comment = $conn->escapeString($_POST['comment']);

// Validate
if (empty($provider_id) || empty($rating) || empty($booking_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit();
}

// Check if review already exists for this booking
$check = $conn->prepare("SELECT id FROM reviews WHERE booking_id = :bid AND reviewer_id = :rid");
$check->bindValue(':bid', $booking_id, SQLITE3_INTEGER);
$check->bindValue(':rid', $reviewer_id, SQLITE3_INTEGER);
$existing = $check->execute()->fetchArray(SQLITE3_ASSOC);

if ($existing) {
    // UPDATE
    $stmt = $conn->prepare("UPDATE reviews SET rating = :rate, comment = :comment, is_disabled = 0 WHERE id = :id");
    $stmt->bindValue(':rate', $rating, SQLITE3_INTEGER);
    $stmt->bindValue(':comment', $comment, SQLITE3_TEXT);
    $stmt->bindValue(':id', $existing['id'], SQLITE3_INTEGER);
} else {
    // INSERT
    $stmt = $conn->prepare("INSERT INTO reviews (reviewer_id, provider_id, booking_id, rating, comment) VALUES (:rid, :pid, :bid, :rate, :comment)");
    $stmt->bindValue(':rid', $reviewer_id, SQLITE3_INTEGER);
    $stmt->bindValue(':pid', $provider_id, SQLITE3_INTEGER);
    $stmt->bindValue(':bid', $booking_id, SQLITE3_INTEGER);
    $stmt->bindValue(':rate', $rating, SQLITE3_INTEGER);
    $stmt->bindValue(':comment', $comment, SQLITE3_TEXT);
}

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Review saved successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save review']);
}
?>
