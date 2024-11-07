<?php
session_start();
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Connection failed']));
}

$response = ['status' => 'error', 'message' => 'Error uploading the file'];

if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
    $user_id = $_POST['user_id'];
    $file_name = basename($_FILES['certificate']['name']);
    $file_tmp = $_FILES['certificate']['tmp_name'];

    // Create the upload path with user ID and certificates folder
    $upload_dir = '../uploads/' . $user_id . '/certificates/';

    // Ensure it's a PDF file
    $file_type = mime_content_type($file_tmp);
    if ($file_type === 'application/pdf') {
        // Create the directory structure if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        // Full file path
        $file_path = $upload_dir . $file_name;
        // Move the uploaded file to the correct directory
        if (move_uploaded_file($file_tmp, $file_path)) {
            // Insert certificate details into the database
            $sql = "INSERT INTO user_certificates (user_id, file_name) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $user_id, $file_name);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Certificate uploaded successfully'];
            } else {
                $response = ['status' => 'error', 'message' => 'Error saving certificate details'];
            }
            $stmt->close();
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to move the uploaded file'];
        }
    } else {
        $response = ['status' => 'error', 'message' => 'Only PDF files are allowed'];
    }
}

echo json_encode($response);

$conn->close();
