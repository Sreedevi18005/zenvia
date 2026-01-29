<?php
require 'db_connect.php';

echo "<h2>Cleaning up dummy properties...</h2>";

try {
    // Delete all data from property related tables
    $conn->exec("DELETE FROM property_images");
    $conn->exec("DELETE FROM properties");
    
    // Reset Auto Increment (optional, but good for cleanup)
    $conn->exec("DELETE FROM sqlite_sequence WHERE name='properties'");
    $conn->exec("DELETE FROM sqlite_sequence WHERE name='property_images'");

    echo "Successfully removed all properties and images.<br>";
} catch (Exception $e) {
    echo "Error cleaning up: " . $e->getMessage();
}
?>
