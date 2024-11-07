<?php
// get_donation_data.php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
}

if (isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];

    // Fetch the raised amount and goal for the event
    $query = "SELECT raised, goal FROM events WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $raised = $row['raised'];
        $goal = $row['goal'];
        echo json_encode(['status' => 'success', 'total' => $raised, 'goal' => $goal]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Event not found.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No event ID provided.']);
}
?>
