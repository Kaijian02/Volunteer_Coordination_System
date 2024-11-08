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
$current_start_date = $data['start_date'];
$current_end_date = $data['end_date'];

// Check if the user has already applied for this event
$check_sql = "SELECT id FROM event_applications WHERE user_id = ? AND event_id = ?";
$check_stmt = $conn->prepare($check_sql);

if ($check_stmt) {
    $check_stmt->bind_param("ii", $user_id, $event_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already applied for this event.']);
        $check_stmt->close();
        $conn->close();
        exit();
    }
    $check_stmt->close();
}

// Check for date conflicts with other applied events
$conflict_sql = "
    SELECT e.id, e.start_date, e.end_date 
    FROM events e
    JOIN event_applications ea ON e.id = ea.event_id
    WHERE ea.user_id = ? 
    AND ea.status IN ('Applying', 'Approved', 'Participated') 
    AND (
        (e.start_date <= ? AND e.end_date >= ?) OR
        (e.start_date <= ? AND e.end_date >= ?) OR
        (e.start_date >= ? AND e.end_date <= ?)
    )";
$conflict_stmt = $conn->prepare($conflict_sql);

if ($conflict_stmt) {
    // Remove JSON encoding from dates and fix parameter count
    $clean_start_date = trim($current_start_date, '"');
    $clean_end_date = trim($current_end_date, '"');

    $conflict_stmt->bind_param(
        "sssssss",
        $user_id,
        $clean_start_date,
        $clean_start_date,
        $clean_end_date,
        $clean_end_date,
        $clean_start_date,
        $clean_end_date
    );

    $conflict_stmt->execute();
    $conflict_result = $conflict_stmt->get_result();

    if ($conflict_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Date conflict with another event application.']);
        $conflict_stmt->close();
        $conn->close();
        exit();
    }
    $conflict_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare conflict check statement.']);
    $conn->close();
    exit();
}

// Insert new event application
$sql = "INSERT INTO event_applications (user_id, event_id, status) VALUES (?, ?, 'Applying')";
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
