<?php
$eventId = $_GET['event_id'];
$selectedDate = isset($_GET['attendance_date']) ? $_GET['attendance_date'] : date('Y-m-d'); // Get the selected date or default to today

$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get event start and end dates
$eventQuery = "SELECT start_date, end_date FROM events WHERE id = ?";
$eventStmt = $conn->prepare($eventQuery);
$eventStmt->bind_param("i", $eventId);
$eventStmt->execute();
$eventResult = $eventStmt->get_result();
$eventDates = $eventResult->fetch_assoc();
$eventStmt->close();

// Generate an array of dates
$startDate = new DateTime($eventDates['start_date']);

// If the event is one day, set the end date as the start date, else use the actual end date
if (is_null($eventDates['end_date'])) {
    // If the event is one day, there is no range, it's just the start date
    $dateRange = [$startDate->format('Y-m-d')];
} else {
    // Multi-day event, include the end date in the range
    $endDate = new DateTime($eventDates['end_date']);
    $endDate = $endDate->modify('+1 day'); // Include the end date
    $dateRange = [];
    while ($startDate < $endDate) {
        $dateRange[] = $startDate->format('Y-m-d');
        $startDate->modify('+1 day');
    }
}

// Validate selected date
if (!in_array($selectedDate, $dateRange)) {
    echo '<script>alert("Selected date is outside the event date range.");</script>';
    $selectedDate = $dateRange[0]; // Reset to the first available date
}

// Fetch approved volunteers
$query = "
    SELECT u.id AS user_id, u.name AS user_name, u.email, u.dob
    FROM event_applications ea
    JOIN users u ON ea.user_id = u.id
    WHERE ea.event_id = ? AND (ea.status = 'Approved' OR ea.status = 'Participated')
     ORDER BY u.name ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $eventId);
$stmt->execute();
$result = $stmt->get_result();

// Fetch attendance status for the selected date
$attendanceQuery = "
    SELECT user_id, status 
    FROM attendance 
    WHERE event_id = ? AND attendance_date = ?
";

$attendanceStmt = $conn->prepare($attendanceQuery);
$attendanceStmt->bind_param("is", $eventId, $selectedDate);
$attendanceStmt->execute();
$attendanceResult = $attendanceStmt->get_result();

$attendanceStatus = [];
while ($attendanceRow = $attendanceResult->fetch_assoc()) {
    $attendanceStatus[$attendanceRow['user_id']] = $attendanceRow['status'];
}
$attendanceStmt->close();

if ($result->num_rows > 0) {
    echo '<h4>Take Attendance</h4>';
    echo '<form id="attendance-form">';
    echo '<input type="hidden" name="event_id" value="' . htmlspecialchars($eventId) . '">'; // Hidden input to pass event_id

    // Dropdown for date selection
    echo '<label for="attendance_date">Select Attendance Date:</label>';
    echo '<select name="attendance_date" id="attendance_date" required ' . (count($dateRange) === 1 ? 'readonly' : '') . '>'; // Add readonly if it's a one-day event
    foreach ($dateRange as $date) {
        $selected = ($date === $selectedDate) ? 'selected' : '';
        echo '<option value="' . $date . '" ' . $selected . '>' . $date . '</option>';
    }
    echo '</select>';
    echo '<button type="button" id="generate-pdf-btn" class="btn btn-primary" style="margin: 10px;">Attendance PDF</button>';
    echo '<button type="button" id="generate-empty-pdf-btn" class="btn btn-primary" style="margin: 10px;">Volunteer List PDF</button>';
    echo '<button type="button" id="generate-ecert-btn" class="btn btn-success" style="margin: 10px;">Issue E-Certificates to All Volunteers</button>';

    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Name</th><th>Email</th><th>Age</th><th>Present</th><th>Absent</th><th>Actions</th></tr></thead>';
    echo '<tbody>';

    while ($row = $result->fetch_assoc()) {
        $dob = new DateTime($row['dob']);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['user_name']) . '</td>';
        echo '<td>' . $row['email'] . '</td>';
        echo '<td>' . $age . '</td>';
        echo '<td>';
        echo '<input type="radio" name="attendance[' . $row['user_id'] . ']" value="Present" ' . (isset($attendanceStatus[$row['user_id']]) && $attendanceStatus[$row['user_id']] === 'Present' ? 'checked' : '') . '>';
        echo '</td>';
        echo '<td>';
        echo '<input type="radio" name="attendance[' . $row['user_id'] . ']" value="Absent" ' . (isset($attendanceStatus[$row['user_id']]) && $attendanceStatus[$row['user_id']] === 'Absent' ? 'checked' : '') . '>';
        echo '</td>';
        echo '<td>';
        echo '<a href="view_profile.php?user_id=' . $row['user_id'] . '" class="btn btn-info btn-sm">View Profile</a>'; // View profile button
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '<button type="submit">Submit Attendance</button>';
    echo '</form>';
} else {
    echo '<p>No volunteers found for this event.</p>';
}

$conn->close();
