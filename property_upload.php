<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check property count
$res = $conn->query("SELECT COUNT(*) as count FROM properties WHERE owner_id = $user_id");
$row = $res->fetchArray(SQLITE3_ASSOC);

if ($row['count'] >= 4) {
    echo json_encode(['status' => 'error', 'message' => 'Maximum limit reached (4 properties).']);
    exit();
}

    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $location = $_POST['location'];
    $type = $_POST['type'];
    $listing_type = isset($_POST['listing_type']) ? $_POST['listing_type'] : 'sale';
    $prop_id = isset($_POST['property_id']) && !empty($_POST['property_id']) ? $_POST['property_id'] : null;

    $uploaded = false;
    $property_id = null;

    // --- ENFORCE IMAGE LIMIT (3 to 5) ---
    $new_img_count = 0;
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $nm) {
            if (!empty($nm)) $new_img_count++;
        }
    }

    if ($prop_id) {
        // UPDATE: Check existing + new
        $cRes = $conn->query("SELECT COUNT(*) as count FROM property_images WHERE property_id = " . intval($prop_id));
        $cRow = $cRes->fetchArray(SQLITE3_ASSOC);
        $current_count = $cRow['count'];
        $total_imgs = $current_count + $new_img_count;

        // If simple text update (new=0) and already valid, allow. 
        // If they assume this rule is new, we force compliance on next edit.
        if ($total_imgs < 3 || $total_imgs > 5) {
            echo json_encode(['status' => 'error', 'message' => "Property must have 3 to 5 images. Current: $current_count, Adding: $new_img_count."]);
            exit();
        }
    } else {
        // CREATE: Must upload 3-5
        if ($new_img_count < 3 || $new_img_count > 5) {
            echo json_encode(['status' => 'error', 'message' => "You must upload between 3 and 5 images."]);
            exit();
        }
    }
    // ------------------------------------

    if ($prop_id) {
        // UPDATE
        $stmt = $conn->prepare("UPDATE properties SET title=:title, description=:desc, price=:price, location=:loc, type=:type, listing_type=:ltype, status='pending' WHERE id=:pid AND owner_id=:uid");
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':desc', $description, SQLITE3_TEXT);
        $stmt->bindValue(':price', $price, SQLITE3_FLOAT);
        $stmt->bindValue(':loc', $location, SQLITE3_TEXT);
        $stmt->bindValue(':type', $type, SQLITE3_TEXT);
        $stmt->bindValue(':ltype', $listing_type, SQLITE3_TEXT);
        $stmt->bindValue(':pid', $prop_id, SQLITE3_INTEGER);
        $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
             $property_id = $prop_id;
             $uploaded = true;
        }
    } else {
        // INSERT
        $stmt = $conn->prepare("INSERT INTO properties (owner_id, title, description, price, location, type, listing_type, status) VALUES (:uid, :title, :desc, :price, :loc, :type, :ltype, 'pending')");
        $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':desc', $description, SQLITE3_TEXT);
        $stmt->bindValue(':price', $price, SQLITE3_FLOAT);
        $stmt->bindValue(':loc', $location, SQLITE3_TEXT);
        $stmt->bindValue(':type', $type, SQLITE3_TEXT);
        $stmt->bindValue(':ltype', $listing_type, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $property_id = $conn->lastInsertRowID();
            $uploaded = true;
        }
    }

    if ($uploaded) {
        // Handle Images with MIME Check
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($tmp_name && $_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    
                    // MIME Check
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($tmp_name);
                    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    
                    if (in_array($mime, $allowed)) {
                        $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                        $fileName = uniqid('img_', true) . '.' . $ext;
                        $targetPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($tmp_name, $targetPath)) {
                            $idxStmt = $conn->prepare("INSERT INTO property_images (property_id, image_url) VALUES (:pid, :url)");
                            $idxStmt->bindValue(':pid', $property_id, SQLITE3_INTEGER);
                            $idxStmt->bindValue(':url', $targetPath, SQLITE3_TEXT);
                            $idxStmt->execute();
                        }
                    }
                }
            }
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Property submitted for approval']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error saving property']);
    }
}
?>
