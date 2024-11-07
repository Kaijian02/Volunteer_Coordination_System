<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if the 'self-introduction' field exists in the POST array
    if (isset($_POST['self-introduction'])) {
        $selfIntroduction = $conn->real_escape_string($_POST['self-introduction']);
        $userId = (int)$_POST['user_id'];

        $sql = "UPDATE users SET self_introduction = '$selfIntroduction' WHERE id = $userId";

        if ($conn->query($sql) === TRUE) {
            echo json_encode(['status' => 'success', 'message' => 'Self-introduction updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error updating record: ' . $conn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: \'self-introduction\' key is missing in POST data.']);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
