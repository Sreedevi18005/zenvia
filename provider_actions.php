<?php
session_start();
require 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? '';
$booking_id = $_POST['booking_id'] ?? null;

if ($action == 'update_status' && $booking_id) {
    $new_status = $_POST['status']; // accepted, rejected, completed
    
    // Validation
    if (!in_array($new_status, ['accepted', 'rejected', 'completed', 'cancelled'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
        exit();
    }

    try {
        if ($new_status == 'completed') {
            // Fetch rate to set total_amount
            $rStmt = $conn->prepare("SELECT rate FROM service_details WHERE user_id = :pid");
            $rStmt->bindValue(':pid', $_SESSION['user_id'], SQLITE3_INTEGER);
            $rRes = $rStmt->execute();
            $rRow = $rRes->fetchArray(SQLITE3_ASSOC);
            $total = $rRow['rate'] ?? 0;

            $stmt = $conn->prepare("UPDATE bookings SET status = :status, total_amount = :amt WHERE id = :bid AND provider_id = :pid");
            $stmt->bindValue(':amt', $total, SQLITE3_FLOAT);
        } else {
            $stmt = $conn->prepare("UPDATE bookings SET status = :status WHERE id = :bid AND provider_id = :pid");
        }

        $stmt->bindValue(':status', $new_status, SQLITE3_TEXT);
        $stmt->bindValue(':bid', $booking_id, SQLITE3_INTEGER);
        $stmt->bindValue(':pid', $_SESSION['user_id'], SQLITE3_INTEGER);
        $stmt->execute();
        
        if ($conn->changes() > 0) {
            $msg = "Booking $new_status successfully";
            if ($new_status == 'rejected') {
                $msg = "Booking cancelled. ₹30 will be credited to your account."; 
            }
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Action failedOrNoChange']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

    } elseif ($action == 'get_invoice') {
    // Generate Invoice Data
    if (!$booking_id) {
        echo json_encode(['status' => 'error', 'message' => 'Booking ID required']);
        exit();
    }

    $stmt = $conn->prepare("SELECT b.*, u.name as user_name, sd.rate as provider_rate 
                            FROM bookings b 
                            JOIN users u ON b.user_id = u.id 
                            JOIN service_details sd ON b.provider_id = sd.user_id 
                            WHERE b.id = :bid AND b.provider_id = :pid");
    $stmt->bindValue(':bid', $booking_id, SQLITE3_INTEGER);
    $stmt->bindValue(':pid', $_SESSION['user_id'], SQLITE3_INTEGER);
    $res = $stmt->execute();
    $booking = $res->fetchArray(SQLITE3_ASSOC);

    if ($booking) {
        $rate = $booking['provider_rate']; // Assuming flat rate for service for MVP
        $advance = $booking['advance_paid'];
        $balance = $rate - $advance;
        
        $invoice = [
            'booking_id' => $booking['id'],
            'customer' => $booking['user_name'],
            'date' => $booking['booking_date'],
            'service_charge' => $rate,
            'advance_paid' => $advance,
            'balance_due' => $balance,
            'total' => $rate
        ];
        echo json_encode(['status' => 'success', 'data' => $invoice]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found']);
    }

} elseif ($action == 'update_profile') {
    // Basic details
    $phone = $conn->escapeString($_POST['phone']);
    $email = $conn->escapeString($_POST['email']);
    $address = $conn->escapeString($_POST['address']);
    $bio = $conn->escapeString($_POST['bio']);
    $experience = $conn->escapeString($_POST['experience']);
    
    // Update Users Table
    $uStmt = $conn->prepare("UPDATE users SET phone=:ph, email=:em, address=:addr WHERE id=:id");
    $uStmt->bindValue(':ph', $phone, SQLITE3_TEXT);
    $uStmt->bindValue(':em', $email, SQLITE3_TEXT);
    $uStmt->bindValue(':addr', $address, SQLITE3_TEXT);
    $uStmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $uStmt->execute();
    
    // Update Service Details
    $sStmt = $conn->prepare("UPDATE service_details SET bio=:bio, experience=:exp WHERE user_id=:id");
    $sStmt->bindValue(':bio', $bio, SQLITE3_TEXT);
    $sStmt->bindValue(':exp', $experience, SQLITE3_TEXT);
    $sStmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $sStmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'Profile updated']);

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>
