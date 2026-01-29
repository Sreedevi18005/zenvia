<?php
require 'db_connect.php';

echo "<h2>Updating Database Schema...</h2>";

try {
    // 1. Add profile_image to users
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN profile_image TEXT DEFAULT NULL");
        echo "Added 'profile_image' to users table.<br>";
    } catch (Exception $e) {
        echo "Column 'profile_image' likely already exists in users.<br>";
    }

    // 2. Add status to properties
    try {
        $conn->exec("ALTER TABLE properties ADD COLUMN status TEXT DEFAULT 'pending'");
        echo "Added 'status' to properties table.<br>";
    } catch (Exception $e) {
        echo "Column 'status' likely already exists in properties.<br>";
    }

    // 3. Add is_deleted to properties
    try {
        $conn->exec("ALTER TABLE properties ADD COLUMN is_deleted INTEGER DEFAULT 0");
        echo "Added 'is_deleted' to properties table.<br>";
    } catch (Exception $e) {
        echo "Column 'is_deleted' likely already exists in properties.<br>";
    }

    echo "<h3>Schema update completed.</h3>";

} catch (Exception $e) {
    echo "Critical Error: " . $e->getMessage();
}
?>
