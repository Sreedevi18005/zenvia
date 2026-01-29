<?php
// DIAGNOSTIC SCRIPT
require 'db_connect.php';

echo "<h1>Zenvia Diagnostic Tool</h1>";

// 1. Check PHP Version
echo "<h3>1. PHP Version</h3>";
echo "PHP Version: " . phpversion() . "<br>";

// 2. Check Database File Existence
echo "<h3>2. Database File Check</h3>";
$dbPath = __DIR__ . '/zenvia.db';
if (file_exists($dbPath)) {
    echo "Filesystem: Database file FOUND at: $dbPath<br>";
    echo "Size: " . filesize($dbPath) . " bytes<br>";
    echo "Permissions: " . substr(sprintf('%o', fileperms($dbPath)), -4) . "<br>";
    if (is_writable($dbPath)) {
        echo "<span style='color:green'>SUCCESS: Database file is WRITABLE.</span><br>";
    } else {
        echo "<span style='color:red'>ERROR: Database file is NOT WRITABLE. Fix permissions.</span><br>";
    }
} else {
    echo "<span style='color:red'>CRITICAL ERROR: 'zenvia.db' file does not exist. Run init_db.php first.</span><br>";
}

// 3. Check SQLite Extension
echo "<h3>3. SQLite Extension</h3>";
if (extension_loaded('sqlite3')) {
    echo "<span style='color:green'>SUCCESS: SQLite3 extension is LOADED.</span><br>";
} else {
    echo "<span style='color:red'>CRITICAL ERROR: SQLite3 extension is NOT loaded. Check php.ini.</span><br>";
}

// 4. Check Connection and Users
echo "<h3>4. Database Content Check</h3>";
try {
    $conn = new SQLite3($dbPath);
    $result = $conn->query("SELECT count(*) as count FROM users");
    $row = $result->fetchArray();
    echo "Number of users in database: " . $row['count'] . "<br>";
    
    if ($row['count'] > 0) {
        $users = $conn->query("SELECT id, email, role FROM users");
        echo "<br><b>List of Users:</b><br>";
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Email</th><th>Role</th></tr>";
        while ($u = $users->fetchArray(SQLITE3_ASSOC)) {
            echo "<tr><td>{$u['id']}</td><td>{$u['email']}</td><td>{$u['role']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<span style='color:red'>WARNING: No users found. Please run init_db.php again.</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color:red'>CONNECTION ERROR: " . $e->getMessage() . "</span><br>";
}
?>
