<?php
// seed_data.php - Populates the database with dummy data
require 'db_connect.php';

echo "Starting Seeding Process...<br>";

$status = 'active'; // All seeded users are active

// 1. Create Customers
$customers = [
    ['Arun Kumar', 'arun@example.com', '9876543210', 'Bangalore'],
    ['Priya Sharma', 'priya@example.com', '9876543211', 'Mumbai'],
    ['Rahul Dravid', 'rahul@example.com', '9876543212', 'Bangalore'],
    ['Sneha Gupta', 'sneha@example.com', '9876543213', 'Delhi'],
    ['Vikram Singh', 'vikram@example.com', '9876543214', 'Chennai']
];

foreach ($customers as $c) {
    try {
        $pass = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, address, status) VALUES (:name, :email, :phone, :pass, 'user', :addr, :dstat)");
        $stmt->bindValue(':name', $c[0], SQLITE3_TEXT);
        $stmt->bindValue(':email', $c[1], SQLITE3_TEXT);
        $stmt->bindValue(':phone', $c[2], SQLITE3_TEXT);
        $stmt->bindValue(':pass', $pass, SQLITE3_TEXT);
        $stmt->bindValue(':addr', $c[3], SQLITE3_TEXT);
        $stmt->bindValue(':dstat', $status, SQLITE3_TEXT);
        $stmt->execute();
        echo "Created Customer: {$c[0]}<br>";
    } catch (Exception $e) {
        echo "Skipped Customer {$c[0]} (Already exists)<br>";
    }
}

// 2. Create Service Providers
$providers = [
    ['Suresh Electricals', 'suresh@provider.com', 'Electrician', 250, '10 Years', 'Expert in house wiring and repairs.'],
    ['Ramesh Plumbing', 'ramesh@provider.com', 'Plumber', 300, '8 Years', 'Leakage expert and pipe fitting.'],
    ['Quick Clean', 'clean@provider.com', 'Cleaning', 150, '5 Years', 'Deep cleaning services for homes.'],
    ['Anita Interiors', 'anita@provider.com', 'Painter', 400, '4 Years', 'Wall painting and texture designs.'],
    ['Tech Fix', 'tech@provider.com', 'Appliance Repair', 350, '6 Years', 'AC, Fridge, and Washing Machine repair.']
];

foreach ($providers as $p) {
    try {
        $pass = password_hash('provider123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, status) VALUES (:name, :email, '9999999999', :pass, 'provider', :dstat)");
        $stmt->bindValue(':name', $p[0], SQLITE3_TEXT);
        $stmt->bindValue(':email', $p[1], SQLITE3_TEXT);
        $stmt->bindValue(':pass', $pass, SQLITE3_TEXT);
        $stmt->bindValue(':dstat', $status, SQLITE3_TEXT);
        $stmt->execute();
        $uid = $conn->lastInsertRowID();

        $sStmt = $conn->prepare("INSERT INTO service_details (user_id, category, rate, experience, bio) VALUES (:uid, :cat, :rate, :exp, :bio)");
        $sStmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
        $sStmt->bindValue(':cat', $p[2], SQLITE3_TEXT);
        $sStmt->bindValue(':rate', $p[3], SQLITE3_FLOAT); 
        $sStmt->bindValue(':exp', $p[4], SQLITE3_TEXT);
        $sStmt->bindValue(':bio', $p[5], SQLITE3_TEXT);
        $sStmt->execute();
        echo "Created Provider: {$p[0]}<br>";
    } catch (Exception $e) {
        echo "Skipped Provider {$p[0]} (Already exists)<br>";
    }
}

// 3. Create Properties
// Get two different users
$owners = [];
$res = $conn->query("SELECT id FROM users WHERE role='user' LIMIT 2");
while($r = $res->fetchArray()) { $owners[] = $r['id']; }

if(empty($owners)) { echo "Error: No users found to assign properties to.<br>"; exit; }

$properties = [
    // Owner 1 (4 props)
    ['Luxury 3BHK Villa', 'Spacious villa with garden and pool.', 15000000, 'Whitefield, Bangalore', 'Villa', 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', $owners[0]],
    ['Modern 2BHK Apartment', 'City center apartment, fully furnished.', 8500000, 'Indiranagar, Bangalore', 'Apartment', 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', $owners[0]],
    ['Commercial Space', 'Office space in tech park.', 25000000, 'Cyber City, Gurgaon', 'Commercial', 'https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', $owners[0]],
    ['Urban Studio', 'Compact studio for singles.', 4500000, 'Powai, Mumbai', 'Apartment', 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', $owners[0]],
    
    // Owner 2 (2 props)
    ['Farmhouse Land', '1 Acre agricultural land.', 5000000, 'Coorg, Karnataka', 'Land', 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', $owners[1] ?? $owners[0]],
    ['Sea View Penthouse', 'Top floor with ocean view.', 35000000, 'Marine Drive, Mumbai', 'Apartment', 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', $owners[1] ?? $owners[0]]
];

foreach ($properties as $prop) {
    if (count($prop) < 7) continue; 
    
// Create Admin
try {
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, status) VALUES ('System Admin', 'admin@zenvia.com', '0000000000', :pass, 'admin', 'active')");
    $stmt->bindValue(':pass', $pass, SQLITE3_TEXT);
    $stmt->execute();
    echo "Created Admin: admin@zenvia.com<br>";
} catch (Exception $e) { echo "Skipped Admin (Exists)<br>"; }


foreach ($properties as $prop) {
    if (count($prop) < 7) continue; 
    
    try {
        $stmt = $conn->prepare("INSERT INTO properties (owner_id, title, description, price, location, type) VALUES (:oid, :title, :desc, :price, :loc, :type)");
        $stmt->bindValue(':title', $prop[0], SQLITE3_TEXT);
        $stmt->bindValue(':desc', $prop[1], SQLITE3_TEXT);
        $stmt->bindValue(':price', $prop[2], SQLITE3_FLOAT);
        $stmt->bindValue(':loc', $prop[3], SQLITE3_TEXT);
        $stmt->bindValue(':type', $prop[4], SQLITE3_TEXT);
        $stmt->bindValue(':oid', $prop[6], SQLITE3_INTEGER);
        $stmt->execute();
        $pid = $conn->lastInsertRowID();

        $iStmt = $conn->prepare("INSERT INTO property_images (property_id, image_url) VALUES (:pid, :url)");
        $iStmt->bindValue(':pid', $pid, SQLITE3_INTEGER);
        $iStmt->bindValue(':url', $prop[5], SQLITE3_TEXT);
        $iStmt->execute();
        echo "Created Property: {$prop[0]}<br>";
    } catch (Exception $e) {
        echo "Skipped Property {$prop[0]} (Error or exists)<br>";
    }
}

}
echo "<h3>Seeding Completed Successfully!</h3>";
?>
