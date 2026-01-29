<?php
require 'db_connect.php';

header('Content-Type: application/json');

try {
    // Fetch users with role 'provider' and status 'active'
    $sql = "SELECT u.id, u.name, u.profile_image, sd.category, sd.rate, sd.experience, sd.bio, 
            (SELECT AVG(rating) FROM reviews WHERE provider_id = u.id) as avg_rating 
            FROM users u 
            LEFT JOIN service_details sd ON u.id = sd.user_id 
            WHERE u.role = 'provider' AND u.status = 'active'";
            
    $result = $conn->query($sql);
    
    $providers = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if (!$row['category']) $row['category'] = 'General';
        // Format rating
        $row['avg_rating'] = $row['avg_rating'] ? round($row['avg_rating'], 1) : 0;
        $providers[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'data' => $providers]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
