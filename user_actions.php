<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($action == 'fetch_received_bookings') {
    // Fetch bookings for properties owned by this user
    $sql = "SELECT b.*, 
            u.name as booker_name, u.phone as booker_phone, u.email as booker_email,
            p.title as property_title, p.location as property_location
            FROM bookings b
            JOIN properties p ON b.property_id = p.id
            JOIN users u ON b.user_id = u.id
            WHERE p.owner_id = :uid
            ORDER BY b.booking_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $bookings = [];
    while($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $bookings[] = $row;
    }
    echo json_encode(['status' => 'success', 'bookings' => $bookings]);

} elseif ($action == 'confirm_booking') {
    $booking_id = $_POST['booking_id'];
    
    // Verify ownership before confirming
    $checkSql = "SELECT b.id FROM bookings b 
                 JOIN properties p ON b.property_id = p.id 
                 WHERE b.id = :bid AND p.owner_id = :uid";
    $stmt = $conn->prepare($checkSql);
    $stmt->bindValue(':bid', $booking_id, SQLITE3_INTEGER);
    $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
    $res = $stmt->execute();
    
    if ($res->fetchArray(SQLITE3_ASSOC)) {
        $upStmt = $conn->prepare("UPDATE bookings SET status='confirmed' WHERE id=:bid");
        $upStmt->bindValue(':bid', $booking_id, SQLITE3_INTEGER);
        $upStmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Booking confirmed successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized or booking not found']);
    }

} elseif ($action == 'fetch_bookings') {
    $sql = "SELECT b.*, 
            u.name as provider_name, s.category as service_category, s.rate as service_rate,
            p.title as property_title,
            r.rating as review_rating, r.comment as review_comment, r.id as review_id, r.is_disabled as review_disabled
            FROM bookings b
            LEFT JOIN users u ON b.provider_id = u.id
            LEFT JOIN service_details s ON s.user_id = u.id
            LEFT JOIN properties p ON b.property_id = p.id
            LEFT JOIN reviews r ON r.booking_id = b.id
            WHERE b.user_id = :uid
            ORDER BY b.booking_date DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $bookings = [];
    while($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $bookings[] = $row;
    }
    echo json_encode(['status' => 'success', 'bookings' => $bookings]);

} elseif ($action == 'cancel_booking') {
    $booking_id = $_POST['booking_id'];
    
    // Check if pending
    $stmt = $conn->prepare("SELECT status FROM bookings WHERE id=:bid AND user_id=:uid");
    $stmt->bindValue(':bid', $booking_id, SQLITE3_INTEGER);
    $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
    $res = $stmt->execute();
    $booking = $res->fetchArray(SQLITE3_ASSOC);
    
    if ($booking && $booking['status'] == 'pending') {
        $upStmt = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE id=:bid");
        $upStmt->bindValue(':bid', $booking_id, SQLITE3_INTEGER);
        $upStmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Booking cancelled. Advance will be refunded.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Cannot cancel this booking.']);
    }

} elseif ($action == 'delete_property') {
    $prop_id = $_POST['property_id'];
    
    // Soft delete
    $stmt = $conn->prepare("UPDATE properties SET is_deleted=1 WHERE id=:pid AND owner_id=:uid");
    $stmt->bindValue(':pid', $prop_id, SQLITE3_INTEGER);
    $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
    $stmt->execute();
    
    echo json_encode(['status' => 'success', 'message' => 'Property deleted']);

} elseif ($action == 'update_profile') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    // Check required
    if(empty($name) || empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Name and Phone are required']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE users SET name=:name, phone=:phone, address=:address WHERE id=:uid");
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    $stmt->bindValue(':address', $address, SQLITE3_TEXT);
    $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
    
    if ($stmt->execute()) {
        $_SESSION['name'] = $name; // Update session
        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update profile']);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>
