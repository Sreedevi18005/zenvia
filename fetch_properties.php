<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM properties WHERE owner_id = $user_id AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY created_at DESC";
$result = $conn->query($sql);

$properties = [];
while($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $propId = $row['id'];
    $imgSql = "SELECT image_url FROM property_images WHERE property_id = $propId";
    $imgResult = $conn->query($imgSql);
    $images = [];
    while($imgRow = $imgResult->fetchArray(SQLITE3_ASSOC)) {
        $images[] = $imgRow['image_url'];
    }
    $row['images'] = $images;
    $properties[] = $row;
}

echo json_encode(['status' => 'success', 'properties' => $properties, 'count' => count($properties)]);
?>
