<?php
include 'db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $complaint = $_POST['complaint'] ?? '';

    if (empty($name) || empty($phone) || empty($email) || empty($complaint)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Ensure table exists
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS complaints (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT,
            email TEXT,
            complaint TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $stmt = $conn->prepare("INSERT INTO complaints (name, phone, email, complaint) VALUES (:name, :phone, :email, :complaint)");
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':complaint', $complaint, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Complaint submitted successfully.']);
        } else {
            // Capture DB error if any
            $errorInfo = $conn->lastErrorMsg();
            echo json_encode(['status' => 'error', 'message' => 'Failed to submit complaint. DB Error: ' . $errorInfo]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}

