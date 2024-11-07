<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start(); // Assuming user ID is stored in session
$user_id = $_SESSION['user_id'];
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'ongoing';

$current_date = date('Y-m-d');

// Fetch events created by the user based on filter
if ($filter === 'ongoing') {
    // Ongoing events: start_date <= today and (end_date >= today or end_date is null and start_date <= today)
    $sql = "SELECT e.id, e.title, e.start_date, e.end_date, e.venue, e.start_time, e.end_time, e.volunteers_needed,
           (SELECT COUNT(*) FROM event_applications WHERE event_id = e.id) AS volunteers_requested
           FROM events e
           WHERE e.user_id = ?
        --    AND e.status = 'Public' 
           AND (
            (e.start_date <= ? AND e.end_date >= ?)
            OR (e.end_date IS NULL AND e.start_date = ?)
)";
} elseif ($filter === 'upcoming') {
    // Upcoming events: start_date > today
    $sql = "SELECT e.id, e.title, e.start_date, e.end_date, e.venue, e.start_time, e.end_time, e.volunteers_needed, e.status,
    (SELECT COUNT(*) FROM event_applications WHERE event_id = e.id) AS volunteers_requested
    FROM events e
    WHERE e.user_id = ? 
    AND e.start_date > ?
    AND e.status <> 'Cancelled'";
} elseif ($filter === 'cancelled') {
    // Cancelled events: only select events with status 'Cancelled'
    $sql = "SELECT e.id, e.title, e.start_date, e.end_date, e.venue, e.start_time, e.end_time, e.volunteers_needed, e.status,
           (SELECT COUNT(*) FROM event_applications WHERE event_id = e.id) AS volunteers_requested
           FROM events e
           WHERE e.user_id = ? 
           AND e.status = 'Cancelled'";
} else { // passed
    // Passed events: end_date < today or (end_date is null and start_date < today)
    $sql = "SELECT e.id, e.title, e.start_date, e.end_date, e.venue, e.start_time, e.end_time, e.volunteers_needed, e.status,
    (SELECT COUNT(*) FROM event_applications WHERE event_id = e.id) AS volunteers_requested
    FROM events e
    WHERE e.user_id = ? 
    AND (e.end_date < ? OR (e.end_date IS NULL AND e.start_date < ?))
    AND e.status <> 'Cancelled'";
}

$stmt = $conn->prepare($sql);

// Bind parameters based on filter type
if ($filter === 'ongoing') {
    $stmt->bind_param("isss", $user_id, $current_date, $current_date, $current_date);
} elseif ($filter === 'upcoming') {
    $stmt->bind_param("is", $user_id, $current_date);
} elseif ($filter === 'cancelled') {
    $stmt->bind_param("i", $user_id); // No date needed for cancelled events
} else { // passed
    $stmt->bind_param("iss", $user_id, $current_date, $current_date);
}

$stmt->execute();
$result = $stmt->get_result();

$events = array();
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'events' => $events]);
