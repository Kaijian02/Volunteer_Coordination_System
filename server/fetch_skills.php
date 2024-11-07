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

$userId = (int)$_SESSION['user_id'];
$sql = "SELECT skills FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $skills = $row['skills'];
    $skillsArray = explode(',', $skills);
    echo json_encode(['status' => 'success', 'skills' => $skillsArray]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
}

$conn->close();
