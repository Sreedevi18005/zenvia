<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$booking_id = $conn->escapeString($_POST['booking_id']);
$status = $conn->escapeString($_POST['status']);

$sql = "UPDATE bookings SET status = '$status' WHERE id = $booking_id";

if ($conn->exec($sql)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Update failed']);
}
?>
