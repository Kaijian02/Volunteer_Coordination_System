<?php
session_start();
require '../vendor/autoload.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

$eventId = $_POST['event_id'] ?? null;

if (!$eventId) {
    echo json_encode(['status' => 'error', 'message' => 'Event ID is required']);
    exit();
}

// Check if the event exists and belongs to the current user
$stmt = $conn->prepare("SELECT user_id, start_date, end_date FROM events WHERE id = ?");
$stmt->bind_param("i", $eventId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Event not found']);
    exit();
}

$event = $result->fetch_assoc();

if ($event['user_id'] !== $_SESSION['user_id']) {
    echo json_encode(['status' => 'error', 'message' => 'You are not authorized to cancel this event']);
    exit();
}

// Check if the event is within 3 days of starting or already passed
$startDate = new DateTime($event['start_date']);
$currentDate = new DateTime();

if ($startDate < $currentDate) {
    echo json_encode(['status' => 'error', 'message' => 'Cannot cancel an event that has already passed']);
    exit();
}

$interval = $currentDate->diff($startDate)->days;

if ($interval < 3) {
    echo json_encode(['status' => 'error', 'message' => 'Cannot cancel an event within 3 days of its start date']);
    exit();
}


// Start transaction
$conn->begin_transaction();

try {
    // Update event status
    $stmt = $conn->prepare("UPDATE events SET status = 'Cancelled' WHERE id = ?");
    $stmt->bind_param("i", $eventId);
    $stmt->execute();

    // Update event applications status
    $stmt = $conn->prepare("UPDATE event_applications SET status = 'Cancelled', cancelled_date = NOW() WHERE event_id = ? AND status IN ('Applying', 'Approved')");
    $stmt->bind_param("i", $eventId);
    $stmt->execute();

    // Update the event_created count for the user
    $stmt = $conn->prepare("UPDATE users SET event_created = event_created - 1 WHERE id = ?");
    $stmt->bind_param("i", $event['user_id']);
    $stmt->execute();


    // Fetch affected users for email notification
    $stmt = $conn->prepare("SELECT u.email FROM users u JOIN event_applications ea ON u.id = ea.user_id WHERE ea.event_id = ? AND ea.status = 'Cancelled'");
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $emailResult = $stmt->get_result();

    $conn->commit();

    // Send email notifications
    while ($row = $emailResult->fetch_assoc()) {
        sendCancellationEmail($row['email'], $eventId);
    }

    echo json_encode(['status' => 'success', 'message' => 'Event cancelled successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while cancelling the event']);
}

$conn->close();

function sendCancellationEmail($email, $eventId)
{
    $mail = new PHPMailer(true);
    // Get the event details to include in the email
    global $conn; // Use the existing database connection
    $stmt = $conn->prepare("SELECT title, start_date, end_date, venue FROM events WHERE id = ?");
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $eventResult = $stmt->get_result();
    if ($eventResult->num_rows === 0) {
        // No event found, stop sending email
        return;
    }
    $event = $eventResult->fetch_assoc();

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jerrylaw02@gmail.com'; // Replace with your email
        $mail->Password   = 'zijexuiygafhswks';     // Replace with your password (use environment variables for security)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('jerrylaw02@gmail.com', 'Volunteer Coordination System');
        $mail->addAddress($email);  // Add the recipient's email

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Event Cancellation Notice';

        // Construct email body
        $mail->Body    = '
        <html>
        <body>
            <p>Dear Volunteer,</p>
            <p>We regret to inform you that the event titled <strong>' . $event['title'] . '</strong>, 
            scheduled from <strong>' . $event['start_date'] . '</strong> to <strong>' . $event['end_date'] . '</strong> at <strong>' . $event['venue'] . '</strong>, 
            has been cancelled by the organizer.</p>
            <p>We apologize for the inconvenience caused. If you have any questions or concerns, please feel free to contact us.</p>
            <p>Best regards,<br>Volunteer Coordination System</p>
        </body>
        </html>
        ';

        // Optional: Add plain text alternative for non-HTML email clients
        $mail->AltBody = 'Dear Volunteer, The event titled ' . $event['title'] . ' has been cancelled.';

        $mail->send();  // Send the email

    } catch (Exception $e) {
        // Log error (optional, for debugging purposes)
        error_log("Error sending cancellation email to {$email}: {$mail->ErrorInfo}");
    }
}
