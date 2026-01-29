<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $provider_id = $_POST['provider_id'];
    $booking_date = $_POST['date'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $payment_id = isset($_POST['payment_id']) ? $_POST['payment_id'] : 'pay_offline'; 
    $amount = 30.00; // Advance Amount

    if (empty($provider_id) || empty($booking_date) || empty($address) || empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields missing']);
        exit();
    }

    try {
        // Verify Payment (Optional: You would verify with Razorpay API here using Key Secret)
        // For this demo, we assume the payment_id is valid.

        // Get Provider Rate (Optional, for total amount calculation logic if needed later)
        // $stmtRate = $conn->prepare("SELECT rate FROM service_details WHERE user_id = :pid");
        // ...

        $stmt = $conn->prepare("INSERT INTO bookings (user_id, provider_id, booking_date, status, total_amount, advance_paid, payment_id, customer_address, customer_phone) 
                                VALUES (:uid, :pid, :bdate, 'pending', 0, :amt, :payid, :addr, :phone)");
        
        $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(':pid', $provider_id, SQLITE3_INTEGER);
        $stmt->bindValue(':bdate', $booking_date, SQLITE3_TEXT);
        // Total amount is 0 initially or set to provider rate? The prompt says "bill... balance amount of each provider". 
        // We will store just the advance for now and 0 for total, or maybe update total when provider completes?
        // Let's set total_amount to 0 for now as it's dynamic.
        $stmt->bindValue(':amt', $amount, SQLITE3_FLOAT);
        $stmt->bindValue(':payid', $payment_id, SQLITE3_TEXT);
        $stmt->bindValue(':addr', $address, SQLITE3_TEXT);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Booking Confirmed!', 'booking_id' => $conn->lastInsertRowID()]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
