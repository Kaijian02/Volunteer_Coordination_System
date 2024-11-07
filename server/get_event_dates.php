<?php
session_start();
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['event_id'])) {
    $eventId = (int)$_GET['event_id'];

    $query = "SELECT start_date, end_date FROM events WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $stmt->bind_result($startDate, $endDate);
    $stmt->fetch();
    $stmt->close();

    echo json_encode(['status' => 'success', 'startDate' => $startDate, 'endDate' => $endDate]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Event ID is missing.']);
}
?>
