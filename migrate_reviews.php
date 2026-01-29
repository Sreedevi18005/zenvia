<?php
require 'db_connect.php';

try {
    // Check if is_disabled exists
    $res = $conn->query("PRAGMA table_info(reviews)");
    $has_disabled = false;
    $has_booking = false;
    
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        if ($row['name'] === 'is_disabled') $has_disabled = true;
        if ($row['name'] === 'booking_id') $has_booking = true;
    }

    if (!$has_disabled) {
        $conn->exec("ALTER TABLE reviews ADD COLUMN is_disabled INTEGER DEFAULT 0");
        echo "Column 'is_disabled' added.<br>";
    }
    
    if (!$has_booking) {
        $conn->exec("ALTER TABLE reviews ADD COLUMN booking_id INTEGER DEFAULT NULL");
        // We might want a unique index on booking_id later, but for now just the column
        echo "Column 'booking_id' added.<br>";
    }

    echo "Reviews table migration successful.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
