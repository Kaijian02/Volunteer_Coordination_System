<?php
header('Content-Type: application/json');
session_start();

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

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

$user_id = $_SESSION['user_id'];
$event_id = $data['event_id'];

// Check if the user has already applied for this event
$check_sql = "SELECT id FROM event_applications WHERE user_id = ? AND event_id = ?";
$check_stmt = $conn->prepare($check_sql);

if ($check_stmt) {
    $check_stmt->bind_param("ii", $user_id, $event_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        // User has already applied for the event
        echo json_encode(['success' => false, 'message' => 'You have already applied for this event. Go to History page to see your application details.']);
        $check_stmt->close();
        $conn->close();
        exit();
    }
    $check_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare check statement']);
    $conn->close();
    exit();
}

// Check if the user has completed their profile
$profile_check_sql = "SELECT self_introduction, profile_image FROM users WHERE id = ?";
$profile_check_stmt = $conn->prepare($profile_check_sql);

if ($profile_check_stmt) {
    $profile_check_stmt->bind_param("i", $user_id);
    $profile_check_stmt->execute();
    $profile_check_stmt->bind_result($self_introduction, $profile_image);
    $profile_check_stmt->fetch();

    // Check if self_introduction or profile_image is empty
    if (empty($self_introduction) || empty($profile_image)) {
        echo json_encode(['success' => false, 'message' => 'Please complete your profile by adding a self-introduction and profile image before applying.']);
        $profile_check_stmt->close();
        $conn->close();
        exit();
    }

    $profile_check_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare profile check statement']);
    $conn->close();
    exit();
}

$sql = "INSERT INTO event_applications (user_id, event_id, status) VALUES (?, ?, 'applying')";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ii", $user_id, $event_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit application']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
}

$conn->close();
