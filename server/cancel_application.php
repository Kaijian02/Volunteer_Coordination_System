<?php
header('Content-Type: application/json');
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get the application ID and reason from the request
$application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$reason = isset($_GET['reason']) ? trim($_GET['reason']) : null;

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Check if the reason was provided (for cancellation within 7 days)
if ($reason) {
    // File upload handling
    $upload_dir = "../uploads/{$user_id}/evidence/";

    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_path = null;
    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] == 0) {
        $file = $_FILES['evidence'];
        $file_name = basename($file['name']);
        $target_file = $upload_dir . $file_name;

        // Allow only certain file formats (e.g., JPG, PNG, PDF)
        $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $file_path = $target_file;  // Save file path for database
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload evidence file.']);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, JPG, and PNG files are allowed.']);
            exit();
        }
    }

    // Update the event application with the reason and mark it as pending approval
    $sql = "UPDATE event_applications 
            SET status = 'Pending Cancellation', reason = ?, pending_cancelled_date = NOW(), evidence = ?
            WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssii", $reason, $file_path, $application_id, $user_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Cancellation request has been submitted for approval.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit cancellation request.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
    }
} else {
    // Direct cancellation for cases without a reason
    $reason = 'No reason needed';
    $sql = "DELETE FROM event_applications WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ii", $application_id, $user_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Application successfully canceled.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to cancel application.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
    }
}

$conn->close();
