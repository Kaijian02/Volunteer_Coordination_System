<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $event_id = $_POST['event_id'];
    $status = $_POST['status'];
    $reason = $_POST['reason'] ?? '';
    $applyPenalty = isset($_POST['apply_penalty']) ? $_POST['apply_penalty'] === 'true' : false;

    // Example connection to the database
    $conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

    // Check if the connection was successful
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get user information based on user_id from the users table
    $userQuery = "SELECT email, name FROM users WHERE id = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userData = $userResult->fetch_assoc();

    if (!$userData) {
        die("User not found.");
    }

    $email = $userData['email'];
    $userName = $userData['name'];

    // Get event information based on event_id from the events table
    $eventQuery = "SELECT title AS event_name, start_date AS event_start_date, end_date AS event_end_date, venue AS event_location FROM events WHERE id = ?";
    $eventStmt = $conn->prepare($eventQuery);
    $eventStmt->bind_param("i", $event_id);
    $eventStmt->execute();
    $eventResult = $eventStmt->get_result();
    $eventData = $eventResult->fetch_assoc();

    if (!$eventData) {
        die("Event not found.");
    }

    $eventName = $eventData['event_name'];
    $eventStartDate = $eventData['event_start_date'];
    $eventEndDate = $eventData['event_end_date'];
    $eventLocation = $eventData['event_location'];


    if ($status === 'Approved') {
        $sql = "UPDATE event_applications 
                SET status = ?, 
                    approval_date = NOW(), 
                    rejected_date = NULL,
                    cancelled_date = NULL,
                    reason = NULL
                WHERE user_id = ? AND event_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $status, $user_id, $event_id);

        // Execute and send email for approval
        if ($stmt->execute()) {
            // Increment current_volunteers count for the event
            $updateVolunteerCountQuery = "UPDATE events SET current_volunteers = current_volunteers + 1 WHERE id = ?";
            $updateVolunteerStmt = $conn->prepare($updateVolunteerCountQuery);
            $updateVolunteerStmt->bind_param("i", $event_id);
            $updateVolunteerStmt->execute();

            // Check if the event should be closed
            $checkEventStatusQuery = "SELECT current_volunteers, volunteers_needed, close_event FROM events WHERE id = ?";
            $checkEventStatusStmt = $conn->prepare($checkEventStatusQuery);
            $checkEventStatusStmt->bind_param("i", $event_id);
            $checkEventStatusStmt->execute();
            $eventStatusResult = $checkEventStatusStmt->get_result();
            $eventStatusData = $eventStatusResult->fetch_assoc();

            if ($eventStatusData['close_event'] === 'Yes' && $eventStatusData['current_volunteers'] >= $eventStatusData['volunteers_needed']) {
                // Close the event if conditions are met
                $closeEventQuery = "UPDATE events SET status = 'Closed' WHERE id = ?";
                $closeEventStmt = $conn->prepare($closeEventQuery);
                $closeEventStmt->bind_param("i", $event_id);
                $closeEventStmt->execute();
            }

            // Send email
            sendNotificationEmail($email, $userName, $eventName, $eventStartDate, $eventEndDate, $eventLocation, $status);
            echo json_encode([
                'success' => true,
                'message' => 'Volunteer Recruited'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating status: ' . $stmt->error
            ]);
        }
    } elseif ($status === 'Rejected') {
        $sql = "UPDATE event_applications 
                SET status = ?, 
                    approval_date = NULL,
                    cancelled_date = NULL, 
                    rejected_date = NOW(),
                    reason = ? 
                WHERE user_id = ? AND event_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $status, $reason, $user_id, $event_id);

        // Execute and send email for rejection
        if ($stmt->execute()) {
            sendNotificationEmail($email, $userName, $eventName, $eventStartDate, $eventEndDate, $eventLocation, $status, $reason);
            echo json_encode([
                'success' => true,
                'message' => 'Volunteer Rejected'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating status: ' . $stmt->error
            ]);
        }
    } elseif ($status === 'Cancelled') {
        $sql = "UPDATE event_applications 
                SET status = ?, 
                    approval_date = NULL,
                    rejected_date = NULL, 
                    cancelled_date = NOW(),
                    reason = ?
                WHERE user_id = ? AND event_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $status, $reason, $user_id, $event_id);

        // Execute and send email for cancellation
        if ($stmt->execute()) {

            // Decrement current_volunteers count for the event
            $updateVolunteerCountQuery = "UPDATE events SET current_volunteers = current_volunteers - 1 WHERE id = ?";
            $updateVolunteerStmt = $conn->prepare($updateVolunteerCountQuery);
            $updateVolunteerStmt->bind_param("i", $event_id);
            $updateVolunteerStmt->execute();

            // Check if the event status needs to be changed to Public
            $checkEventStatusQuery = "SELECT current_volunteers, volunteers_needed FROM events WHERE id = ?";
            $checkEventStatusStmt = $conn->prepare($checkEventStatusQuery);
            $checkEventStatusStmt->bind_param("i", $event_id);
            $checkEventStatusStmt->execute();
            $eventStatusResult = $checkEventStatusStmt->get_result();
            $eventStatusData = $eventStatusResult->fetch_assoc();

            if ($eventStatusData['volunteers_needed'] > $eventStatusData['current_volunteers']) {
                $makeEventPublicQuery = "UPDATE events SET status = 'Public' WHERE id = ?";
                $makeEventPublicStmt = $conn->prepare($makeEventPublicQuery);
                $makeEventPublicStmt->bind_param("i", $event_id);
                $makeEventPublicStmt->execute();
            }

            sendNotificationEmail($email, $userName, $eventName, $eventStartDate, $eventEndDate, $eventLocation, $status, $reason);
            echo json_encode([
                'success' => true,
                'message' => 'Volunteer Cancelled'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating status: ' . $stmt->error
            ]);
        }
    } elseif ($status === 'Cancellation Approved') {
        $sql = "UPDATE event_applications 
        SET status = ?, 
            cancelled_date = NOW(),
            reason = ?
        WHERE user_id = ? AND event_id = ?";
        $reason = 'No reason needed';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $status, $reason, $user_id, $event_id);
        if ($stmt->execute()) {

            // Decrement current_volunteers count for the event
            $updateVolunteerCountQuery = "UPDATE events SET current_volunteers = current_volunteers - 1 WHERE id = ?";
            $updateVolunteerStmt = $conn->prepare($updateVolunteerCountQuery);
            $updateVolunteerStmt->bind_param("i", $event_id);
            $updateVolunteerStmt->execute();

            // Check if the event status needs to be changed to Public
            $checkEventStatusQuery = "SELECT current_volunteers, volunteers_needed FROM events WHERE id = ?";
            $checkEventStatusStmt = $conn->prepare($checkEventStatusQuery);
            $checkEventStatusStmt->bind_param("i", $event_id);
            $checkEventStatusStmt->execute();
            $eventStatusResult = $checkEventStatusStmt->get_result();
            $eventStatusData = $eventStatusResult->fetch_assoc();

            if ($eventStatusData['volunteers_needed'] > $eventStatusData['current_volunteers']) {
                $makeEventPublicQuery = "UPDATE events SET status = 'Public' WHERE id = ?";
                $makeEventPublicStmt = $conn->prepare($makeEventPublicQuery);
                $makeEventPublicStmt->bind_param("i", $event_id);
                $makeEventPublicStmt->execute();
            }

            sendNotificationEmail($email, $userName, $eventName, $eventStartDate, $eventEndDate, $eventLocation, $status, $reason, $applyPenalty);
            echo json_encode([
                'success' => true,
                'message' => 'Volunteer Cancellation Approved'
            ]);
        } else {
            // Handle any errors that occur during the update
            echo json_encode([
                'success' => false,
                'message' => 'Error updating status: ' . $stmt->error
            ]);
        }
    } else {
        die("Invalid status.");
    }

    // Execute the statement
    if ($stmt->execute()) {
        // Send a success message back to the client
        // echo "Volunteer status updated to: " . $status;
    } else {
        // Handle any errors that occur during the update
        echo json_encode([
            'success' => false,
            'message' => 'Error updating status: ' . $stmt->error
        ]);
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}


// Function to send notification email based on status
function sendNotificationEmail($email, $userName, $eventName, $eventStartDate, $eventEndDate, $eventLocation, $status, $reason = '', $applyPenalty = false)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jerrylaw02@gmail.com'; // Your email
        $mail->Password   = 'zijexuiygafhswks'; // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('jerrylaw02@gmail.com', 'Volunteer Coordination System');
        $mail->addAddress($email);

        // Email content based on the status
        $mail->isHTML(true);

        // Set the email subject and body based on the status
        switch ($status) {
            case 'Approved':
                $mail->Subject = 'You have been recruited for the event!';
                $mail->Body    = "
                <html>
                <head>
                    <title>Recruitment Notification</title>
                </head>
                <body>
                    <p>Dear $userName,</p>
                    <p>You have been successfully recruited for the event <strong>$eventName</strong>.</p>
                    <p><strong>Event Details:</strong></p>
                    <ul>
                        <li>Date: $eventStartDate - $eventEndDate</li>
                        <li>Location: $eventLocation</li>
                    </ul>
                    <p>We look forward to seeing you at the event!</p>
                </body>
                </html>
                ";
                break;

            case 'Rejected':
                $mail->Subject = 'Your application has been rejected';
                $mail->Body    = "
                <html>
                <head>
                    <title>Application Rejected</title>
                </head>
                <body>
                    <p>Dear $userName,</p>
                    <p>We regret to inform you that your application for the event <strong>$eventName</strong> has been rejected.</p>
                    <p><strong>Reason:</strong> $reason</p>
                </body>
                </html>
                ";
                break;

            case 'Cancelled':
                $mail->Subject = 'Your participation has been cancelled';
                $mail->Body    = "
                <html>
                <head>
                    <title>Participation Cancelled</title>
                </head>
                <body>
                    <p>Dear $userName,</p>
                    <p>We regret to inform you that your participation in the event <strong>$eventName</strong> has been cancelled.</p>
                    <p><strong>Reason:</strong> $reason</p>
                </body>
                </html>
                ";
                break;

            case 'Cancellation Approved':
                $mail->Subject = 'Your cancellation request has been approved';
                $mail->Body    = "
                <html>
                <head>
                    <title>Cancellation Request Approved</title>
                </head>
                <body>
                    <p>Dear $userName,</p>
                    <p>Your request to cancel your participation in the event <strong>$eventName</strong> has been approved by the organizer.</p>
                    <p>We hope to see you in future events. Thank you for your understanding.</p>
                </body>
                </html>
                ";
                break;
        }

        // Add penalty notice if applicable
        if ($applyPenalty) {
            $penaltyMessage = "
                <p>Note: Credit score being -10 has been applied to your account due to this action.</p>
            ";
            $mail->Body .= $penaltyMessage;
        }

        // Send email
        $mail->send();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
