<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if (isset($_POST['skills'])) {
        $skills = $conn->real_escape_string($_POST['skills']);
        $userId = (int)$_SESSION['user_id'];

        // Prepare the SQL statement with a placeholder
        $sql = "UPDATE users SET skills = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            echo json_encode(['status' => 'error', 'message' => 'Error preparing SQL statement: ' . $conn->error]);
            exit();
        }


        $stmt->bind_param("si", $skills, $userId);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Skills updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error updating skills: ' . $conn->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Skills data is missing.']);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
