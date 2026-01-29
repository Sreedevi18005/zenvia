<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

// Helper for count
function getCount($conn, $table) {
    return $conn->querySingle("SELECT COUNT(*) FROM $table");
}

$stats = [];
$stats['users'] = getCount($conn, 'users');
$stats['properties'] = getCount($conn, 'properties');
$stats['services'] = getCount($conn, 'service_details');
$stats['bookings'] = getCount($conn, 'bookings');

// Fetch Users
$usersRes = $conn->query("SELECT id, name, email, role, phone FROM users ORDER BY id DESC LIMIT 20");
$users = [];
while($row = $usersRes->fetchArray(SQLITE3_ASSOC)) $users[] = $row;

// Fetch Properties
$propsRes = $conn->query("SELECT id, title, price, location FROM properties ORDER BY id DESC LIMIT 20");
$properties = [];
while($row = $propsRes->fetchArray(SQLITE3_ASSOC)) $properties[] = $row;

// Fetch Bookings
$booksRes = $conn->query("SELECT b.id, u.name as user, b.total_amount, b.status, b.booking_date 
                          FROM bookings b 
                          JOIN users u ON b.user_id = u.id 
                          ORDER BY b.id DESC LIMIT 20");
$bookings = [];
while($row = $booksRes->fetchArray(SQLITE3_ASSOC)) $bookings[] = $row;

echo json_encode([
    'status' => 'success',
    'stats' => $stats,
    'users' => $users,
    'properties' => $properties,
    'bookings' => $bookings
]);
?>
