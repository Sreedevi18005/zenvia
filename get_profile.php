<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Fetch User Basic Info
$stmt = $conn->prepare("SELECT name, email, phone, address, profile_image, created_at FROM users WHERE id = :id");
$stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
$user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if ($user) {
    // 2. Fetch Service Details
    $sStmt = $conn->prepare("SELECT category, rate, bio, experience FROM service_details WHERE user_id = :id");
    $sStmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
    $service = $sStmt->execute()->fetchArray(SQLITE3_ASSOC);
    
    // Merge
    if ($service) {
        $user = array_merge($user, $service);
    }

    // 3. Fetch Reviews
    $rStmt = $conn->prepare("SELECT r.rating, r.comment, r.created_at, u.name as reviewer_name 
                             FROM reviews r 
                             JOIN users u ON r.reviewer_id = u.id 
                             WHERE r.provider_id = :id 
                             ORDER BY r.created_at DESC");
    $rStmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
    $reviewsResult = $rStmt->execute();
    
    $reviews = [];
    $totalRating = 0;
    $count = 0;
    
    while($row = $reviewsResult->fetchArray(SQLITE3_ASSOC)) {
        $reviews[] = $row;
        $totalRating += $row['rating'];
        $count++;
    }
    
    $user['reviews'] = $reviews;
    $user['average_rating'] = $count > 0 ? round($totalRating / $count, 1) : 0;
    $user['total_reviews'] = $count;

    echo json_encode(['status' => 'success', 'data' => $user]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
}

?>
