<?php
// Initialize SQLite Database
$dbFile = 'zenvia.db';
$db = new SQLite3($dbFile);

// Enable Foreign Keys
$db->exec('PRAGMA foreign_keys = ON;');

// Users Table
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    phone TEXT NOT NULL,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user', -- enum not supported, using text
    status TEXT NOT NULL DEFAULT 'pending', -- pending, active, rejected
    address TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Insert Admin if not exists
$adminCheck = $db->query("SELECT * FROM users WHERE email='admin@zenvia.com'");
if (!$adminCheck->fetchArray()) {
    $pass = password_hash('password', PASSWORD_DEFAULT);
    $status = 'active';
    // Fixed: Added status to columns and values
    $db->exec("INSERT INTO users (name, email, phone, password, role, address, status) VALUES ('Admin', 'admin@zenvia.com', '0000000000', '$pass', 'admin', 'Zenvia HQ', '$status')");
    echo "Admin created.<br>";
}

// Service Details Table
$db->exec("CREATE TABLE IF NOT EXISTS service_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    category TEXT NOT NULL,
    rate DECIMAL(10,2) NOT NULL,
    bio TEXT DEFAULT NULL,
    experience TEXT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
)");

// Properties Table
$db->exec("CREATE TABLE IF NOT EXISTS properties (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    owner_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    location TEXT NOT NULL,
    type TEXT NOT NULL,
    listing_type TEXT NOT NULL DEFAULT 'sale', -- sale or rent
    status TEXT NOT NULL DEFAULT 'pending', -- pending, active, rejected
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE
) ");

// Property Images Table
$db->exec("CREATE TABLE IF NOT EXISTS property_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    property_id INTEGER NOT NULL,
    image_url TEXT NOT NULL,
    FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE
)");

// Bookings Table
$db->exec("CREATE TABLE IF NOT EXISTS bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    provider_id INTEGER DEFAULT NULL,
    property_id INTEGER DEFAULT NULL,
    booking_date DATE NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    advance_paid DECIMAL(10,2) NOT NULL,
    payment_id TEXT DEFAULT NULL,
    customer_address TEXT DEFAULT NULL,
    customer_phone TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES users (id) ON DELETE SET NULL,
    FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE SET NULL
)");

// Reviews Table
$db->exec("CREATE TABLE IF NOT EXISTS reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    reviewer_id INTEGER NOT NULL,
    provider_id INTEGER NOT NULL,
    rating INTEGER NOT NULL,
    comment TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES users (id) ON DELETE CASCADE
)");

echo "Database initialized successfully in " . __DIR__ . "/$dbFile";
?>
