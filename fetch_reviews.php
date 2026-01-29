<?php
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['provider_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Provider ID required']);
    exit();
}

$provider_id = $conn->escapeString($_GET['provider_id']);
$show_all = isset($_GET['show_all']) && $_GET['show_all'] == '1';

$where_clause = "r.provider_id = $provider_id";
if (!$show_all) {
    $where_clause .= " AND r.is_disabled = 0";
}

$sql = "SELECT r.*, u.name as reviewer_name 
        FROM reviews r 
        JOIN users u ON r.reviewer_id = u.id 
        WHERE $where_clause 
        ORDER BY r.created_at DESC";

$result = $conn->query($sql);

$reviews = [];
$totalRating = 0;
$count = 0;

while($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $reviews[] = $row;
    $totalRating += $row['rating'];
    $count++;
}

$avgRating = ($count > 0) ? round($totalRating / $count, 1) : 0;

echo json_encode([
    'status' => 'success', 
    'reviews' => $reviews, 
    'count' => $count, 
    'average_rating' => $avgRating
]);
?>
