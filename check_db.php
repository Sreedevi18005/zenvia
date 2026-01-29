<?php
require 'backend/db_connect.php';
$result = $conn->query("PRAGMA table_info(bookings)");
$columns = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row;
}
echo json_encode($columns, JSON_PRETTY_PRINT);
?>
