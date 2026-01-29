<?php
date_default_timezone_set('Asia/Kolkata');
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID is required']);
    exit();
}

try {
    if ($action == 'approve_user') {
        $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'User approved successfully']);
    } 
    elseif ($action == 'decline_user') {
        // Decline: Can be hard delete or rejected status. Let's use rejected.
        $stmt = $conn->prepare("UPDATE users SET status = 'rejected' WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'User request declined']);
    }
    elseif ($action == 'delete_user') {
        // Soft delete for admin visibility
        $stmt = $conn->prepare("UPDATE users SET status = 'deactivated' WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'User deactivated']);
    }
    elseif ($action == 'approve_property') {
        $stmt = $conn->prepare("UPDATE properties SET status = 'approved' WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Property approved']);
    }
    elseif ($action == 'decline_property') {
        // Soft delete (or set status rejected)
        $stmt = $conn->prepare("UPDATE properties SET status = 'rejected' WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Property rejected']);
    }
    elseif ($action == 'delete_property') {
        // Soft delete
        $stmt = $conn->prepare("UPDATE properties SET is_deleted=1 WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Property deleted successfully']);
    }
    elseif ($action == 'disable_review') {
        $stmt = $conn->prepare("UPDATE reviews SET is_disabled = 1 WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Review disabled successfully']);
    }
    elseif ($action == 'enable_review') {
        $stmt = $conn->prepare("UPDATE reviews SET is_disabled = 0 WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Review enabled successfully']);
    }
    else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
