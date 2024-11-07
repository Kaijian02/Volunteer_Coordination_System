<?php
require '../vendor/autoload.php';

use setasign\Fpdi\Fpdi;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]));
}

// $eventId = $_GET['event_id'];
$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

// Fetch event details to get start and end dates
$eventDetailsQuery = "
    SELECT start_date, end_date
    FROM events
    WHERE id = ?
";
$eventDetailsStmt = $conn->prepare($eventDetailsQuery);
$eventDetailsStmt->bind_param("i", $eventId);
$eventDetailsStmt->execute();
$eventDetailsResult = $eventDetailsStmt->get_result();
$eventDetails = $eventDetailsResult->fetch_assoc();

$startDate = new DateTime($eventDetails['start_date']);
$endDate = $eventDetails['end_date'] ? new DateTime($eventDetails['end_date']) : null;
$eventDays = [];
$totalEventDays = 0;

// Determine event days
if ($endDate) {
    // Event spans multiple days
    $interval = new DateInterval('P1D');
    while ($startDate <= $endDate) {
        $eventDays[] = $startDate->format('Y-m-d'); // Store each event day
        $startDate->add($interval);
    }
    $totalEventDays = count($eventDays); // Total number of event days
} else {
    // One-day event
    $eventDays[] = $startDate->format('Y-m-d'); // Only the start date
    $totalEventDays = 1; // Total event days is 1
}

// Fetch total number of volunteers for this event
$volunteerCountQuery = "
    SELECT COUNT(*) AS total_volunteers
    FROM event_applications
    WHERE event_id = ? AND status IN ('Approved', 'Participated')
";
$volunteerStmt = $conn->prepare($volunteerCountQuery);
$volunteerStmt->bind_param("i", $eventId);
$volunteerStmt->execute();
$volunteerResult = $volunteerStmt->get_result();
$volunteerData = $volunteerResult->fetch_assoc();
$totalVolunteers = $volunteerData['total_volunteers'];

// Check if attendance is recorded for each event day
$completeAttendanceQuery = "
    SELECT COUNT(DISTINCT attendance_date) AS total_attended_days
    FROM attendance
    WHERE event_id = ?
    AND attendance_date IN (" . implode(',', array_fill(0, $totalEventDays, '?')) . ")
";
$attendanceParams = array_merge([$eventId], $eventDays);
$completeAttendanceStmt = $conn->prepare($completeAttendanceQuery);
$completeAttendanceStmt->bind_param(str_repeat('s', $totalEventDays + 1), ...$attendanceParams);
$completeAttendanceStmt->execute();
$attendanceResult = $completeAttendanceStmt->get_result();
$attendanceData = $attendanceResult->fetch_assoc();

$attendedDaysCount = $attendanceData['total_attended_days'];

$certificatesSent = 0;

// Only issue certificates if all event days have attendance recorded
if ($attendedDaysCount === $totalEventDays && $totalEventDays > 0) {

    // Fetch event details and eligible volunteers who attended all days
    $volunteerDetailsQuery = "
        SELECT u.name AS user_name, u.email, e.title AS event_title, 
               e.start_date, e.end_date, e.venue, org.name AS organizer_name,
               (SELECT COUNT(*) FROM attendance a WHERE a.event_id = e.id AND a.user_id = u.id AND a.status = 'Present') AS days_present,
               (SELECT COUNT(DISTINCT attendance_date) FROM attendance WHERE event_id = e.id) AS total_event_days
        FROM users u
        JOIN event_applications ea ON u.id = ea.user_id
        JOIN events e ON ea.event_id = e.id
        JOIN users org ON e.user_id = org.id
        WHERE ea.event_id = ? AND ea.status IN ('Approved', 'Participated')
    ";

    $stmt = $conn->prepare($volunteerDetailsQuery);
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Check if volunteer attended all days
        if ($row['days_present'] == $row['total_event_days']) {
            // Create a new PDF instance
            $pdf = new FPDF();
            $pdf->AddPage();

            // Add border
            $pdf->SetLineWidth(0.5);
            $pdf->Rect(10, 10, 190, 277);

            // Certificate Title
            $pdf->SetFont('Arial', 'B', 24);
            $pdf->Cell(0, 20, 'Certificate of Participation', 0, 1, 'C');

            // Subtitle
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, 'This is to certify that', 0, 1, 'C');

            // Participant's Name
            $pdf->SetFont('Times', 'B', 22);
            $pdf->Cell(0, 10, $row['user_name'], 0, 1, 'C');

            // Event Details
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, 'has successfully participated in', 0, 1, 'C');

            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, $row['event_title'], 0, 1, 'C');

            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, 'organized by ' . $row['organizer_name'], 0, 1, 'C');
            $pdf->Cell(0, 10, 'from ' . $eventDetails['start_date'] . ' to ' . ($endDate ? $eventDetails['end_date'] : $eventDetails['start_date']), 0, 1, 'C');
            $pdf->Cell(0, 10, 'at ' . $row['venue'], 0, 1, 'C');

            // Add date of issue
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 20, 'Issued on: ' . date('Y-m-d'), 0, 1, 'C');

            // Add signature image below the issue date and centered
            $signatureImagePath = '../img/digital_signature.png';
            $pdf->Image($signatureImagePath, ($pdf->GetPageWidth() - 60) / 2, 180, 60); // Adjust y-position as needed

            $pdf->Cell(0, 10, '', 0, 1); // Space between signature and "Voluntopia"

            // Add "Voluntopia" text below the signature
            $pdf->SetY(195); // Set Y position below the signature
            $pdf->SetFont('Arial', 'I', 14); // Italic font for the signature text
            $pdf->Cell(0, 10, 'Voluntopia', 0, 1, 'C'); // Center the "Voluntopia" text

            // Save PDF to a temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'cert');
            $pdf->Output('F', $tempFile);

            // Send email with certificate attached
            $mail = new PHPMailer(true);

            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'jerrylaw02@gmail.com';
                $mail->Password   = 'zijexuiygafhswks';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                //Recipients
                $mail->setFrom('jerrylaw02@gmail.com', 'Volunteer Coordination System');
                $mail->addAddress($row['email'], $row['user_name']);
                //Attachments
                $mail->addAttachment($tempFile, 'Certificate.pdf');
                //Content
                $mail->isHTML(true);
                $mail->Subject = 'Certificate of Participation - ' . $row['event_title'];
                $mail->Body    = '
                <html>
                <body>
                    <p>Dear ' . $row['user_name'] . ',</p>
                    <p>Thank you for your participation in ' . $row['event_title'] . '. Please find attached your Certificate of Participation.</p>
                    <p>We appreciate your dedication and hope to see you at future events!</p>
                    <p>Best regards,<br>Volunteer Coordination System</p>
                </body>
                </html>
                ';
                $mail->send();
                $certificatesSent++;

                // Delete temporary file
                unlink($tempFile);
            } catch (Exception $e) {
                // Log the error (you may want to implement proper logging)
                error_log("Error sending certificate to {$row['email']}: {$mail->ErrorInfo}");
            }
        }
    }
} else {
    // If not all attendance is complete, return a message
    echo json_encode([
        'success' => false,
        'message' => "Attendance is incomplete. Ensure all event days have attendance for every volunteer."
    ]);
    exit;
}

$conn->close();

// Return JSON response
if ($certificatesSent > 0) {
    echo json_encode([
        'success' => true,
        'message' => "E-Certificates generated and sent to $certificatesSent out of $totalVolunteers eligible volunteers."
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => "No E-Certificates were sent. Ensure all volunteers have attended all event days."
    ]);
}
