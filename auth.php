<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action == 'signup') {
    $name = $conn->escapeString($_POST['name']);
    $email = $conn->escapeString($_POST['email']);
    $phone = $conn->escapeString($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $conn->escapeString($_POST['role']);
    $address = $conn->escapeString($_POST['address'] ?? '');

    // Check if email exists
    $checkQuery = $conn->prepare("SELECT * FROM users WHERE email=:email");
    $checkQuery->bindValue(':email', $email, SQLITE3_TEXT);
    $result = $checkQuery->execute();
    
    if ($result->fetchArray()) {
        echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
        exit();
    }

    // Status Logic
    $status = 'active'; // Default active (for 'user')
    if ($role === 'provider') {
        $status = 'pending'; // Providers need approval
    }
    if ($email === 'admin@zenvia.com') {
        $status = 'active';
        $role = 'admin'; // Enforce admin role for this email
    }

    // Handle Profile Image Upload
    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['profile_image']['tmp_name'];
        // MIME Check
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp_name);
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (in_array($mime, $allowed)) {
            $uploadDir = 'uploads/profiles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('profile_', true) . '.' . $ext;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($tmp_name, $targetPath)) {
                $profile_image = $targetPath;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, address, status, profile_image) VALUES (:name, :email, :phone, :password, :role, :address, :status, :img)");
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    $stmt->bindValue(':password', $password, SQLITE3_TEXT);
    $stmt->bindValue(':role', $role, SQLITE3_TEXT);
    $stmt->bindValue(':address', $address, SQLITE3_TEXT);
    $stmt->bindValue(':status', $status, SQLITE3_TEXT);
    $stmt->bindValue(':img', $profile_image, SQLITE3_TEXT);

    try {
        $stmt->execute();
        $user_id = $conn->lastInsertRowID();
        
        if ($role == 'provider') {
            $bio = $conn->escapeString($_POST['bio'] ?? '');
            $category = $conn->escapeString($_POST['category'] ?? 'General');
            $rate = $conn->escapeString($_POST['rate'] ?? 0);
            $experience = $conn->escapeString($_POST['experience'] ?? '');
            
            $pStmt = $conn->prepare("INSERT INTO service_details (user_id, category, rate, bio, experience) VALUES (:uid, :cat, :rate, :bio, :exp)");
            $pStmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
            $pStmt->bindValue(':cat', $category, SQLITE3_TEXT);
            $pStmt->bindValue(':rate', $rate, SQLITE3_TEXT); 
            $pStmt->bindValue(':bio', $bio, SQLITE3_TEXT);
            $pStmt->bindValue(':exp', $experience, SQLITE3_TEXT);
            $pStmt->execute();
        }

        echo json_encode(['status' => 'success', 'message' => 'Registration successful. ' . ($status == 'pending' ? 'Please wait for Admin approval.' : 'You can login now.')]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
} 

elseif ($action == 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=:email");
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if ($user) {
        if (password_verify($password, $user['password'])) {
            // CHECK STATUS
            if ($user['status'] !== 'active') {
                echo json_encode(['status' => 'error', 'message' => 'Account pending approval or rejected.']);
                exit();
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['profile_image'] = $user['profile_image'];

            // Redirect Logic
            $redirectLink = '../frontend/index.html'; // Default to Home
            
            if ($user['role'] == 'admin') {
                $redirectLink = '../frontend/admin-dashboard.html';
            } elseif ($user['role'] == 'provider') {
                $redirectLink = '../frontend/provider-dashboard.html';
            }
            // Users -> Index.html

            // Remove password from response
            unset($user['password']);

            echo json_encode(['status' => 'success', 'message' => 'Login successful', 'redirect' => $redirectLink, 'user' => $user]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
}
?>
