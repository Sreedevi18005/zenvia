<?php
require 'db_connect.php';

header('Content-Type: application/json');

$sql = "SELECT p.*, pi.image_url 
        FROM properties p 
        LEFT JOIN (
            SELECT property_id, image_url 
            FROM property_images 
            GROUP BY property_id
        ) pi ON p.id = pi.property_id 
        WHERE (p.status = 'approved') AND (p.is_deleted = 0 OR p.is_deleted IS NULL)
        ORDER BY p.created_at DESC";

$result = $conn->query($sql);

$properties = [];
while($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $properties[] = $row;
}

echo json_encode(['status' => 'success', 'properties' => $properties]);
?>
