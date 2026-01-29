<?php
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID required']);
    exit();
}

$id = intval($_GET['id']);

try {
    // Provider Details
    $stmt = $conn->prepare("SELECT u.id, u.name, u.email, u.phone, u.profile_image, 
                            sd.category, sd.rate, sd.experience, sd.bio 
                            FROM users u 
                            LEFT JOIN service_details sd ON u.id = sd.user_id 
                            WHERE u.id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $res = $stmt->execute();
    $provider = $res->fetchArray(SQLITE3_ASSOC);
    
    if (!$provider) {
        echo json_encode(['status' => 'error', 'message' => 'Provider not found']);
        exit();
    }
    
    // Reviews
    $rStmt = $conn->prepare("SELECT r.rating, r.comment, r.created_at, u.name as reviewer_name 
                             FROM reviews r 
                             JOIN users u ON r.reviewer_id = u.id 
                             WHERE r.provider_id = :id AND r.is_disabled = 0
                             ORDER BY r.created_at DESC");
    $rStmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $rRes = $rStmt->execute();
    
    $reviews = [];
    $totalRating = 0;
    while ($row = $rRes->fetchArray(SQLITE3_ASSOC)) {
        $reviews[] = $row;
        $totalRating += $row['rating'];
    }
    
    $provider['reviews'] = $reviews;
    $provider['avg_rating'] = count($reviews) > 0 ? round($totalRating / count($reviews), 1) : 0;
    $provider['review_count'] = count($reviews);

    echo json_encode(['status' => 'success', 'data' => $provider]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
