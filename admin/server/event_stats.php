<?php
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get the year and month from the request
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;

// Prepare the response array
$response = [];

// Filter the total events count by year and month
$query = "SELECT COUNT(*) as total_events FROM events WHERE YEAR(start_date) = ?";
if ($month > 0) {
    $query .= " AND MONTH(start_date) = ?";
}

$stmt = $conn->prepare($query);
if ($month > 0) {
    $stmt->bind_param("ii", $year, $month);
} else {
    $stmt->bind_param("i", $year);
}
$stmt->execute();
$result = $stmt->get_result();
$total_events = $result->fetch_assoc()['total_events'];

// Filter the monthly events count for the specified year and month
$monthly_events = [];
$months = [];
for ($i = 1; $i <= 12; $i++) {
    $query = "SELECT COUNT(*) as count FROM events WHERE YEAR(start_date) = ? AND MONTH(start_date) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $year, $i);
    $stmt->execute();
    $result = $stmt->get_result();
    $monthly_events[$i] = $result->fetch_assoc()['count'];
    $months[] = date('F', mktime(0, 0, 0, $i, 1)); // Get month name
}

// Initialize passed events with zeros
$passed_events = array_fill(1, 12, 0);

// Adjust the query to filter passed events by year and month
$query = "
    SELECT MONTH(attendance_date) as month, COUNT(DISTINCT event_id) as total_passed_events 
    FROM attendance 
    WHERE attendance_date < NOW() AND YEAR(attendance_date) = ?";
if ($month > 0) {
    $query .= " AND MONTH(attendance_date) = ?";
}
$query .= " GROUP BY MONTH(attendance_date)";

$stmt = $conn->prepare($query);
if ($month > 0) {
    $stmt->bind_param("ii", $year, $month);
} else {
    $stmt->bind_param("i", $year);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $passed_events[$row['month']] = $row['total_passed_events'];
}

// Filter the total attendance data for the specified year and month
$query = "
    SELECT status, COUNT(*) as count 
    FROM attendance 
    WHERE YEAR(attendance_date) = ?";
if ($month > 0) {
    $query .= " AND MONTH(attendance_date) = ?";
}
$query .= " GROUP BY status";

$stmt = $conn->prepare($query);
if ($month > 0) {
    $stmt->bind_param("ii", $year, $month);
} else {
    $stmt->bind_param("i", $year);
}
$stmt->execute();
$result = $stmt->get_result();

$attendance_data = ['Present' => 0, 'Absent' => 0];
while ($row = $result->fetch_assoc()) {
    if (isset($attendance_data[$row['status']])) {
        $attendance_data[$row['status']] = $row['count'];
    }
}

// Calculate participation rate
$total_present = $attendance_data['Present'];
$total_absent = $attendance_data['Absent'];
$total_attendance = $total_present + $total_absent;
$participation_rate = $total_attendance > 0 ? ($total_present / $total_attendance) * 100 : 0;

// Get total unique volunteers for the specified year and month
$query = "SELECT COUNT(DISTINCT user_id) AS total_unique_volunteers FROM attendance WHERE YEAR(attendance_date) = ?";
if ($month > 0) {
    $query .= " AND MONTH(attendance_date) = ?";
}

$stmt = $conn->prepare($query);
if ($month > 0) {
    $stmt->bind_param("ii", $year, $month);
} else {
    $stmt->bind_param("i", $year);
}
$stmt->execute();
$result = $stmt->get_result();
$total_unique_volunteers = $result->fetch_assoc()['total_unique_volunteers'];

// Query to get total distinct event days
$query = "
    SELECT COUNT(DISTINCT CONCAT(event_id, '-', attendance_date)) AS total_event_days
    FROM attendance
    WHERE YEAR(attendance_date) = ?";
if ($month > 0) {
    $query .= " AND MONTH(attendance_date) = ?";
}

$stmt = $conn->prepare($query);
if ($month > 0) {
    $stmt->bind_param("ii", $year, $month);
} else {
    $stmt->bind_param("i", $year);
}
$stmt->execute();
$result = $stmt->get_result();
$total_event_days = $result->fetch_assoc()['total_event_days'];

// Get total events count (already taken attendance)
$query = "SELECT COUNT(DISTINCT event_id) as total_events_attendance FROM attendance WHERE YEAR(attendance_date) = ?";
if ($month > 0) {
    $query .= " AND MONTH(attendance_date) = ?";
}

$stmt = $conn->prepare($query);
if ($month > 0) {
    $stmt->bind_param("ii", $year, $month);
} else {
    $stmt->bind_param("i", $year);
}
$stmt->execute();
$result = $stmt->get_result();
$total_events_attendance = $result->fetch_assoc()['total_events_attendance'];

// Prepare the response
$response['success'] = true;
$response['total_events'] = $total_events;
$response['total_events_attendance'] = $total_events_attendance;
$response['monthly_events'] = array_values($monthly_events); // Reindex array for JSON response
$response['months'] = $months;
$response['attendance_data'] = $attendance_data;
$response['participation_rate'] = number_format($participation_rate, 2); // Format as percentage
$response['total_unique_volunteers'] = $total_unique_volunteers;
$response['total_event_days'] = $total_event_days;
$response['total_passed_events'] = array_values($passed_events);

// Return the results in JSON
header('Content-Type: application/json');
echo json_encode($response);

// Close the database connection
$conn->close();
