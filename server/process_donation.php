<?php
session_start();
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use setasign\Fpdi\Fpdi;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the form data
$user_id = $_SESSION['user_id'];
$event_id = $_POST['event_id'];
$donation_amount = $_POST['donation_amount'];

// Insert the donation record
$stmt = $conn->prepare("INSERT INTO donations (user_id, event_id, donation_amount) VALUES (?, ?, ?)");
$stmt->bind_param("iid", $user_id, $event_id, $donation_amount);

if ($stmt->execute()) {
    // Update the raised amount in the events table
    $stmt_update = $conn->prepare("UPDATE events SET raised = raised + ? WHERE id = ?");
    $stmt_update->bind_param("di", $donation_amount, $event_id);
    $stmt_update->execute();

    // Fetch user and event details
    $stmt_details = $conn->prepare("SELECT u.name, u.email, e.title FROM users u JOIN events e ON e.id = ? WHERE u.id = ?");
    $stmt_details->bind_param("ii", $event_id, $user_id);
    $stmt_details->execute();
    $result = $stmt_details->get_result();
    $details = $result->fetch_assoc();

    // Generate PDF receipt
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Donation Receipt', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Date: ' . date('Y-m-d'), 0, 1);
    $pdf->Cell(0, 10, 'Donor: ' . $details['name'], 0, 1);
    $pdf->Cell(0, 10, 'Event: ' . $details['title'], 0, 1);
    $pdf->Cell(0, 10, 'Amount: RM ' . number_format($donation_amount, 2), 0, 1);
    $pdf->Cell(0, 10, 'Thank you for your generous donation!', 0, 1);

    $pdfContent = $pdf->Output('S');

    // Send email with PDF attachment
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jerrylaw02@gmail.com';
        $mail->Password = 'zijexuiygafhswks';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('jerrylaw02@gmail.com', 'Volunteer Coordination System');
        $mail->addAddress($details['email'], $details['name']);

        // Attachment
        $mail->addStringAttachment($pdfContent, 'donation_receipt.pdf');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Thank You for Your Donation';
        $mail->Body = "Dear {$details['name']},<br><br>Thank you for your generous donation of RM " . number_format($donation_amount, 2) . " to the event '{$details['title']}'.<br><br>Please find attached your donation receipt.<br><br>Best regards,<br>Volunteer Coordination System";

        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'Donation successful. Receipt sent to your email.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'success', 'message' => 'Donation successful, but failed to send email: ' . $mail->ErrorInfo]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Donation failed']);
}

$stmt->close();
$conn->close();
