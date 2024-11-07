<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php';
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['notify_all'])) {
        // Notify all unverified users to complete their profile (whose email is verified)
        $query = "SELECT email FROM users WHERE verified_by_admin = 0 AND is_verified = 1";
        $result = $conn->query($query);

        $failedEmails = [];

        while ($row = $result->fetch_assoc()) {
            $email = $row['email'];

            // Send profile completion email
            if (!sendNotificationEmail($email, 'Action Required: Complete Your Profile', getCompleteProfileMessage())) {
                $failedEmails[] = $email;  // Collect failed emails
            }
        }

        if (empty($failedEmails)) {
            echo json_encode(['success' => true, 'message' => 'Emails sent to all users.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send email to: ' . implode(', ', $failedEmails)]);
        }
    } elseif (isset($_POST['email'])) {
        // Notify individual user about profile legitimacy
        $email = $_POST['email'];

        // Send profile legitimacy notification email
        if (sendNotificationEmail($email, 'Action Required: Update Your Profile', getProfileLegitimacyMessage())) {
            echo json_encode(['success' => true, 'message' => 'Email sent successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send email.']);
        }
    }
}

// Function to send email notification
function sendNotificationEmail($email, $subject, $body)
{
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
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Function to generate the email body for incomplete profiles
function getCompleteProfileMessage()
{
    return '
        <html>
        <head>
            <title>Complete Your Profile</title>
        </head>
        <body>
            <p>Dear Valued Volunteer,</p>
            <p>We hope this message finds you well. Our records indicate that your profile is currently incomplete. To ensure that your account is fully updated, we kindly request you to upload a profile picture and provide a brief self-introduction.</p>
            <p>Additionally, uploading any relevant skills certificates will greatly enhance your profile and increase your chances of being selected for volunteer opportunities.</p>
            <p>You can complete your profile by logging in using the link below:</p>
            <p><a href="http://localhost/VolunteerCoordinationSystem/login.php">Login to Complete Profile</a></p>
            <p>Thank you for your cooperation and continued support.</p>
            <p>Best regards,<br>The Volunteer Coordination Team</p>
        </body>
        </html>';
}

// Function to generate the email body for profile legitimacy issues
function getProfileLegitimacyMessage()
{
    return '
        <html>
        <head>
            <title>Update Your Profile</title>
        </head>
        <body>
            <p>Dear Valued Volunteer,</p>
            <p>We have reviewed your profile and noticed that certain aspects may not meet the expected standards. Specifically, we observed issues with either your profile picture or the certificates you have uploaded. Ensuring that your profile information is accurate and professional is crucial to maintain the quality and integrity of our volunteer network.</p>
            <p>We kindly request that you review your profile and update it with a clear, appropriate profile picture, and ensure that any certificates uploaded are legitimate and relevant to the skills you have listed.</p>
            <p>You can make these updates by logging into your account using the link below:</p>
            <p><a href="http://localhost/VolunteerCoordinationSystem/login.php">Login to Update Your Profile</a></p>
            <p>Thank you for your prompt attention to this matter.</p>
            <p>Best regards,<br>The Volunteer Coordination Team</p>
        </body>
        </html>';
}
