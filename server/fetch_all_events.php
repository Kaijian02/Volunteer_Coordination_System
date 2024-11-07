<?php
header('Content-Type: application/json');

// Connect to the database
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

// Fetch all events from the database using a prepared statement
$sql = "SELECT * FROM event ORDER BY startDate DESC";
$stmt = $conn->prepare($sql);

// Execute the prepared statement
$stmt->execute();
$result = $stmt->get_result();

$events = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    echo json_encode(['status' => 'success', 'events' => $events]);
} else {
    echo json_encode(['status' => 'success', 'events' => []]);
}

$conn->close();
?>