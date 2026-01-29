<?php
require 'db_connect.php';

// Add new columns if they don't exist
$alterQueries = [
    "ALTER TABLE bookings ADD COLUMN payment_id TEXT DEFAULT NULL",
    "ALTER TABLE bookings ADD COLUMN customer_address TEXT DEFAULT NULL",
    "ALTER TABLE bookings ADD COLUMN customer_phone TEXT DEFAULT NULL"
];

foreach ($alterQueries as $q) {
    try {
        $conn->exec($q);
        echo "Executed: $q <br>";
    } catch (Exception $e) {
        // Ignore error if column exists (SQLite doesn't support IF NOT EXISTS for ADD COLUMN directly in all versions, or throws if exists)
        echo "Skipped (or error): " . $e->getMessage() . " <br>";
    }
}

echo "Schema updated successfully.";
?>
