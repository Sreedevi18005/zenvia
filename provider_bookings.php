<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'provider') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$provider_id = $_SESSION['user_id'];

$sql = "SELECT b.*, u.name as user_name, u.phone as user_phone, u.address as user_address 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        WHERE b.provider_id = $provider_id 
        ORDER BY b.booking_date DESC";

$result = $conn->query($sql);

$bookings = [];
while($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $bookings[] = $row;
}

echo json_encode(['status' => 'success', 'bookings' => $bookings]);
?>
