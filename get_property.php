<?php
require 'db_connect.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID required']);
    exit();
}

// Fetch basic info
$stmt = $conn->prepare("SELECT p.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone 
                        FROM properties p 
                        JOIN users u ON p.owner_id = u.id 
                        WHERE p.id = :id");
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$res = $stmt->execute();
$prop = $res->fetchArray(SQLITE3_ASSOC);

if ($prop) {
    // Fetch Images
    $imgStmt = $conn->prepare("SELECT image_url FROM property_images WHERE property_id = :id");
    $imgStmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $imgRes = $imgStmt->execute();
    $images = [];
    while($r = $imgRes->fetchArray(SQLITE3_ASSOC)) {
        $images[] = $r['image_url'];
    }
    $prop['images'] = $images;
    
    echo json_encode(['status' => 'success', 'data' => $prop]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Property not found']);
}
?>
