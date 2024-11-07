<?php
require '../vendor/autoload.php';

use setasign\Fpdi\Fpdi;

// Create database connection
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    $attendanceDate = isset($_GET['attendance_date']) ? $_GET['attendance_date'] : '';
    die("Connection failed: " . $conn->connect_error);
}

// Get the event ID and attendance date from the URL
$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$attendanceDate = isset($_GET['attendance_date']) ? $_GET['attendance_date'] : '';



// Fetch the attendance data for the selected date
$query = "
    SELECT users.name AS user_name, users.email, users.dob, attendance.status, 
           events.title, events.start_time, events.end_time, events.venue,
           organizer.name AS organizer_name
    FROM users
    JOIN attendance ON users.id = attendance.user_id
    JOIN events ON attendance.event_id = events.id
    JOIN users AS organizer ON events.user_id = organizer.id
    WHERE attendance.event_id = ? AND attendance.attendance_date = ?
    ORDER BY users.name ASC
";


$stmt = $conn->prepare($query);
$stmt->bind_param("is", $eventId, $attendanceDate);
$stmt->execute();
$result = $stmt->get_result();

// Check if attendance records are found
if ($result->num_rows == 0) {
    echo json_encode(['status' => 'error', 'message' => 'No attendance records found for this event on ' . htmlspecialchars($attendanceDate) . '.']);
    exit;
}

// Fetch event details (assuming all rows have the same event data)
$eventDetails = $result->fetch_assoc();
$organizer = $eventDetails['organizer_name'];
$eventTitle = $eventDetails['title'];
$startTime = $eventDetails['start_time'];
$endTime = $eventDetails['end_time'];
$venue = $eventDetails['venue'];
$result->data_seek(0);
// Create a new PDF instance
$pdf = new FPDF();
$pdf->AddPage();

// Set the title of the document
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(190, 10, 'Event Title: ' . $eventTitle, 0, 1, 'C');
$pdf->Ln(5);
$pdf->SetFont('Arial', '', 10); // Normal (not bold) and smaller size
$pdf->Cell(95, 10, 'Organizer: ' . $organizer, 0, 0, 'L'); // Half width for organizer
$pdf->Cell(95, 10, 'Event Date: ' . $attendanceDate, 0, 1, 'R'); // Half width for event date

// Second row: Event Date (left) and Time (right)
$pdf->Cell(95, 10, 'Venue: ' . $venue, 0, 0, 'L'); // Half width for venue
$pdf->Cell(95, 10, 'Time: ' . $startTime . ' - ' . $endTime, 0, 1, 'R'); // Half width for time
$pdf->Ln(5);

// Set the headers for the attendance table
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(7, 10, 'No', 1);
$pdf->Cell(35, 10, 'Name', 1);
$pdf->Cell(60, 10, 'Email', 1);
$pdf->Cell(10, 10, 'Age', 1);
$pdf->Cell(20, 10, 'Present', 1);
$pdf->Cell(20, 10, 'Absent', 1);
$pdf->Cell(45, 10, 'Comments', 1);
$pdf->Ln();
// Add data rows to the PDF
$pdf->SetFont('Arial', '', 10);
$counter = 1;
while ($row = $result->fetch_assoc()) {
    // Calculate age from dob
    $dob = new DateTime($row['dob']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;

    // Render data in each cell
    $pdf->Cell(7, 10, $counter, 1);
    $pdf->Cell(35, 10, htmlspecialchars($row['user_name']), 1);
    $pdf->Cell(60, 10, $row['email'], 1);
    $pdf->Cell(10, 10, $age, 1);

    // Present and Absent radio buttons
    $presentChecked = ($row['status'] === 'Present') ? '[X]' : '[ ]';
    $absentChecked = ($row['status'] === 'Absent') ? '[X]' : '[ ]';
    $pdf->Cell(20, 10, $presentChecked, 1);
    $pdf->Cell(20, 10, $absentChecked, 1);

    // Comment section (empty, but you can modify to include comments)
    $pdf->Cell(45, 10, '', 1);
    $pdf->Ln();
    $counter++;
}

// Close the database connection
$stmt->close();
$conn->close();

// Output the PDF
$pdf->Output('I', 'Attendance_List_' . $attendanceDate . '.pdf'); // Display the PDF in the browser
