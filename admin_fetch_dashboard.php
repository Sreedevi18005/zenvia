<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Return empty if not admin/logged in
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// 1. Analytics Stats
$total_properties = $conn->querySingle("SELECT COUNT(*) FROM properties WHERE (is_deleted IS NULL OR is_deleted=0)");
$total_users = $conn->querySingle("SELECT COUNT(*) FROM users WHERE role='user'");
$total_sales = $conn->querySingle("SELECT SUM(price) FROM properties WHERE listing_type='sale' AND (is_deleted IS NULL OR is_deleted=0) AND status='approved'") ?: 0;
$total_rent = $conn->querySingle("SELECT SUM(price) FROM properties WHERE listing_type='rent' AND (is_deleted IS NULL OR is_deleted=0) AND status='approved'") ?: 0;

$stats = [
    'total_properties' => $total_properties,
    'total_users' => $total_users,
    'total_rent' => $total_rent,
    'total_sales' => $total_sales
];

// 2. Fetch Users (Customers)
$users = ['pending' => [], 'active' => [], 'deactivated' => []];
$uQuery = $conn->query("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC");
while ($row = $uQuery->fetchArray(SQLITE3_ASSOC)) {
    unset($row['password']);
    $status = $row['status'] ?? 'pending';
    if ($status === 'pending') $users['pending'][] = $row;
    elseif ($status === 'active') $users['active'][] = $row;
    else $users['deactivated'][] = $row; // includes rejected/deactivated
}

// 3. Fetch Providers
$providers = ['pending' => [], 'active' => [], 'deactivated' => []];
$pQuery = $conn->query("
    SELECT u.*, s.category, s.experience, s.bio, s.rate 
    FROM users u 
    LEFT JOIN service_details s ON u.id = s.user_id 
    WHERE u.role='provider' 
    ORDER BY u.created_at DESC
");
while ($row = $pQuery->fetchArray(SQLITE3_ASSOC)) {
    unset($row['password']);
    $status = $row['status'] ?? 'pending';
    if ($status === 'pending') $providers['pending'][] = $row;
    elseif ($status === 'active') $providers['active'][] = $row;
    else $providers['deactivated'][] = $row;
}

// 4. Fetch Properties
$properties = ['pending' => [], 'active' => [], 'deactivated' => []];
$propQuery = $conn->query("
    SELECT p.*, u.name as owner_name, u.email as owner_email
    FROM properties p 
    JOIN users u ON p.owner_id = u.id
    WHERE (p.is_deleted = 0 OR p.is_deleted IS NULL)
    ORDER BY p.created_at DESC
");
while ($row = $propQuery->fetchArray(SQLITE3_ASSOC)) {
    $status = $row['status'] ?? 'pending';
    if ($status === 'pending') $properties['pending'][] = $row;
    elseif ($status === 'approved') $properties['active'][] = $row;
    else $properties['deactivated'][] = $row; // rejected
}

// 5. Fetch Reviews
$reviews = [];
$resReviews = $conn->query("
    SELECT r.*, u.name as reviewer_name, p.name as provider_name
    FROM reviews r
    JOIN users u ON r.reviewer_id = u.id
    JOIN users p ON r.provider_id = p.id
    ORDER BY r.created_at DESC
");
while ($row = $resReviews->fetchArray(SQLITE3_ASSOC)) {
    $reviews[] = $row;
}

echo json_encode([
    'stats' => $stats,
    'users' => $users,
    'providers' => $providers,
    'properties' => $properties,
    'reviews' => $reviews
]);
?>
