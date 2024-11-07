<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

    if ($conn->connect_error) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
        exit();
    }

    // Check if the required fields exist in the POST array
    if (isset($_POST['state']) && isset($_POST['city'])) {
        $state = $conn->real_escape_string($_POST['state']);
        $city = $conn->real_escape_string($_POST['city']);
        $userId = (int)$_SESSION['user_id'];

        // Prepare the SQL statement with placeholders
        $sql = "UPDATE users SET state = ?, city = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            echo json_encode(['status' => 'error', 'message' => 'Error preparing SQL statement: ' . $conn->error]);
            exit();
        }

        // Bind parameters to the prepared statement
        $stmt->bind_param("ssi", $state, $city, $userId);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Location updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error updating record: ' . $conn->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: Required fields are missing in POST data.']);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
