<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$type = $_POST['type'];
$date = $conn->escapeString($_POST['date']);
$total_amount = $conn->escapeString($_POST['total_amount']);
$payment_id = isset($_POST['payment_id']) ? $conn->escapeString($_POST['payment_id']) : null;

if ($type == 'service') {
    $advance_paid = 30.00;
    $provider_id = $conn->escapeString($_POST['provider_id']);
    
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, provider_id, booking_date, status, total_amount, advance_paid, payment_id) VALUES (:uid, :pid, :date, 'pending', :total, :adv, :payid)");
    $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':pid', $provider_id, SQLITE3_INTEGER);
    $stmt->bindValue(':date', $date, SQLITE3_TEXT);
    $stmt->bindValue(':total', $total_amount, SQLITE3_FLOAT);
    $stmt->bindValue(':adv', $advance_paid, SQLITE3_FLOAT);
    $stmt->bindValue(':payid', $payment_id, SQLITE3_TEXT);

} elseif ($type == 'property') {
    $advance_paid = isset($_POST['advance_paid']) ? $conn->escapeString($_POST['advance_paid']) : ($total_amount * 0.02);
    $property_id = $conn->escapeString($_POST['property_id']);
    
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, property_id, booking_date, status, total_amount, advance_paid, payment_id) VALUES (:uid, :pid, :date, 'pending', :total, :adv, :payid)");
    $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':pid', $property_id, SQLITE3_INTEGER);
    $stmt->bindValue(':date', $date, SQLITE3_TEXT);
    $stmt->bindValue(':total', $total_amount, SQLITE3_FLOAT);
    $stmt->bindValue(':adv', $advance_paid, SQLITE3_FLOAT);
    $stmt->bindValue(':payid', $payment_id, SQLITE3_TEXT);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid booking type']);
    exit();
}

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Booking confirmed!', 'advance' => $advance_paid]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error processing booking']);
}
?>
