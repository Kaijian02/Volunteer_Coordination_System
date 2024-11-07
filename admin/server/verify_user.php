<?php
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']); // Ensure the user ID is an integer

    // Update query to set the verified status
    $query = "UPDATE users SET verified_by_admin = 1 WHERE id = ?";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param('i', $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Query preparation failed.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

// Close the connection
$conn->close();
