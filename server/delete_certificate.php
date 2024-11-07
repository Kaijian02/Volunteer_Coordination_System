<?php
session_start();
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if a certificate ID was sent
if (isset($_POST['cert_id'])) {
    $cert_id = $_POST['cert_id'];

    // Fetch the certificate details to find the file name
    $sql = "SELECT user_id, file_name FROM user_certificates WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cert_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $certificate = $result->fetch_assoc();
    
    if ($certificate) {
        $user_id = $certificate['user_id'];
        $file_name = $certificate['file_name'];

        // Define the file path
        $file_path = '../uploads/' . $user_id . '/certificates/' . $file_name;

        // Remove the file from the server
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Remove the certificate entry from the database
        $delete_sql = "DELETE FROM user_certificates WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $cert_id);
        if ($delete_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Certificate deleted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete certificate from the database.']);
        }
        $delete_stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Certificate not found.']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'No certificate ID provided.']);
}

$conn->close();
?>
