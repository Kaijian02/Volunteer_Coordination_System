<?php
require '../vendor/autoload.php';

use setasign\Fpdi\Fpdi;

$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$attendanceDate = isset($_GET['attendance_date']) ? $_GET['attendance_date'] : '';

$query = "
    SELECT users.name AS user_name, users.email, users.dob, events.title, 
           events.start_time, events.end_time, events.venue, organizer.name AS organizer_name
    FROM event_applications
    JOIN users ON users.id = event_applications.user_id
    JOIN events ON event_applications.event_id = events.id
    JOIN users AS organizer ON events.user_id = organizer.id
    WHERE event_applications.event_id = ?
        AND event_applications.status IN ('Approved', 'Participated')
    ORDER BY users.name ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $eventId);
$stmt->execute();
$result = $stmt->get_result();

// Fetch event details (assuming the event is the same for all rows)
$eventDetails = $result->fetch_assoc();
$eventTitle = $eventDetails['title'];
$organizer = $eventDetails['organizer_name'];
$startTime = $eventDetails['start_time'];
$endTime = $eventDetails['end_time'];
$venue = $eventDetails['venue'];

$result->data_seek(0);

// Create a new PDF instance
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(190, 10, 'Event Title: ' . $eventTitle, 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 10); // Normal (not bold) and smaller size
// First row: Organizer (left) and Venue (right)
$pdf->Cell(95, 10, 'Organizer: ' . $organizer, 0, 0, 'L'); // Half width for organizer
$pdf->Cell(95, 10, 'Event Date: ' . $attendanceDate, 0, 1, 'R'); // Half width for event date

// Second row: Event Date (left) and Time (right)
$pdf->Cell(95, 10, 'Venue: ' . $venue, 0, 0, 'L'); // Half width for venue
$pdf->Cell(95, 10, 'Time: ' . $startTime . ' - ' . $endTime, 0, 1, 'R'); // Half width for time

$pdf->Ln(5);
// Table headers
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(7, 10, 'No', 1);
$pdf->Cell(35, 10, 'Name', 1);
$pdf->Cell(60, 10, 'Email', 1);
$pdf->Cell(10, 10, 'Age', 1);
$pdf->Cell(20, 10, 'Present', 1);
$pdf->Cell(20, 10, 'Absent', 1);
$pdf->Cell(45, 10, 'Comments', 1);
$pdf->Ln();

$counter = 1;
// Fetch and add volunteer data to the PDF
while ($row = $result->fetch_assoc()) {
    $dob = new DateTime($row['dob']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;

    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(7, 10, $counter, 1);
    $pdf->Cell(35, 10, $row['user_name'], 1);
    $pdf->Cell(60, 10, $row['email'], 1);
    $pdf->Cell(10, 10, $age, 1);
    $pdf->Cell(20, 10, '', 1); // Empty for Present
    $pdf->Cell(20, 10, '', 1); // Empty for Absent
    $pdf->Cell(45, 10, '', 1);
    $pdf->Ln();
    $counter++;
}

$pdf->Output();
$conn->close();
