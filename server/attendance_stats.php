<?php
// Database connection
session_start();

// Check if the user is logged in
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

$event_id = $_GET['event_id'];

// Check if a specific attendance date is provided
$attendance_date = isset($_GET['attendance_date']) ? $_GET['attendance_date'] : null;

$query = "SELECT start_date, end_date FROM events WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Event not found.']);
    exit();
}

$event = $result->fetch_assoc();
$start_date = new DateTime($event['start_date']);
$end_date = isset($event['end_date']) ? new DateTime($event['end_date']) : null;

// Determine the end date for comparison
$eventEndDate = $end_date ? $end_date : $start_date;

// Current date
$current_date = new DateTime();

// Check if the event has passed
if ($current_date < $eventEndDate) {
    echo json_encode(['success' => false, 'message' => 'You can see the statistical data after the event.']);
    exit();
}

// Query to count attendance (total event or specific day)
if ($attendance_date) {
    // If a specific day is selected, filter by attendance_date
    $query = "SELECT status, COUNT(*) as count FROM attendance WHERE event_id = ? AND attendance_date = ? GROUP BY status";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $event_id, $attendance_date);
} else {
    // Default to total attendance for the entire event
    $query = "SELECT status, COUNT(*) as count FROM attendance WHERE event_id = ? GROUP BY status";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $event_id);
}

$stmt->execute();
$result = $stmt->get_result();

$attendance_data = ['Present' => 0, 'Absent' => 0];

// Fetch attendance records
while ($row = $result->fetch_assoc()) {
    $attendance_data[$row['status']] = $row['count'];
}

// If no attendance records are found
if (empty($attendance_data['Present']) && empty($attendance_data['Absent'])) {
    echo json_encode(['success' => false, 'message' => 'No attendance records found for this event.', 'attendance' => $attendance_data]);
    exit();
}

// Query to get distinct event days (for the dropdown)
$query = "SELECT DISTINCT attendance_date FROM attendance WHERE event_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

$event_days = [];
while ($row = $result->fetch_assoc()) {
    $event_days[] = $row['attendance_date'];
}

// Query for total volunteers for the entire event (regardless of attendance)
$query = "SELECT COUNT(DISTINCT user_id) AS total_unique_volunteers 
          FROM attendance 
          WHERE event_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$totalUniqueVolunteers = $result->fetch_assoc()['total_unique_volunteers'];

// Query to count retained volunteers who attended previous events (only those who were Present for the current event and were also Present in past events)
$query = "SELECT COUNT(DISTINCT a.user_id) AS retained_volunteers 
          FROM attendance a
          WHERE a.event_id = ? AND a.status = 'Present'
          AND a.user_id IN (
              SELECT user_id FROM attendance
              WHERE event_id < ? AND status = 'Present'
          )";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $event_id, $event_id);
$stmt->execute();
$result = $stmt->get_result();
$retainedVolunteers = $result->fetch_assoc()['retained_volunteers'];

// Calculate retention rate
$retentionRate = 0;
if ($totalUniqueVolunteers > 0) {
    $retentionRate = ($retainedVolunteers / $totalUniqueVolunteers) * 100;
}

// Return the results in JSON format
echo json_encode([
    'success' => true,
    'attendance' => [
        'total_unique_volunteers' => $totalUniqueVolunteers,
        'retained_volunteers' => $retainedVolunteers,
        'retention_rate' => number_format($retentionRate, 2), // Format as percentage
        'attendance_data' => $attendance_data, // Attendance data (Present, Absent)
        'event_days' => $event_days // Event days for dropdown
    ]
]);

$stmt->close();
$conn->close();
