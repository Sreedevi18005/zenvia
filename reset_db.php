<?php
// FORCE RESET SCRIPT
$dbFile = __DIR__ . '/zenvia.db';

if (file_exists($dbFile)) {
    // Attempt to delete
    if (unlink($dbFile)) {
        echo "<h3 style='color:green'>Old Database Deleted Successfully.</h3>";
    } else {
        echo "<h3 style='color:red'>Error: Could not delete 'zenvia.db'. Please delete it manually from the folder.</h3>";
        exit;
    }
} else {
    echo "<h3>No existing database found (Clean start).</h3>";
}

// Run initialization
require 'init_db.php';
?>
