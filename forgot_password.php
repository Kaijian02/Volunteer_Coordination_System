<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if email exists
    $sql = "SELECT id, is_verified, token FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $is_verified, $existing_token);
    $stmt->fetch();

    if ($stmt->num_rows > 0) {
        if ($is_verified == 1) {
            // Generate a unique token for password reset
            $token = bin2hex(random_bytes(50));
            $sql = "UPDATE users SET reset_token = ?, reset_token_expiry = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $token, $id);
            $stmt->execute();

            // Send password reset email
            sendPasswordResetEmail($email, $token);
            $message = "Password reset link has been sent to your email.";
        } else {
            // Resend verification email
            sendVerificationEmail($email, $existing_token);
            $message = "Your account is not verified. A new verification email has been sent.";
        }
    } else {
        $message = "Email does not exist.";
    }

    $stmt->close();
    $conn->close();
}

function sendPasswordResetEmail($email, $token)
{
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
        $mail->addAddress($email);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset';
        $mail->Body    = '
        <html>
        <head>
            <title>Password Reset</title>
        </head>
        <body>
            <p>Click the link below to reset your password:</p>
            <a href="http://localhost/VolunteerCoordinationSystem/reset_password.php?token=' . $token . '">Reset Password</a>
        </body>
        </html>
        ';

        $mail->send();
    } catch (Exception $e) {
        global $message;
        $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

function sendVerificationEmail($email, $token)
{
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
        $mail->setFrom('jerrylaw02@gmail.com', 'No-Reply');
        $mail->addAddress($email);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification';
        $mail->Body    = '
        <html>
        <head>
            <title>Email Verification</title>
        </head>
        <body>
            <p>Click the link below to verify your email address:</p>
            <a href="http://localhost/VolunteerCoordinationSystem/verification.php?token=' . $token . '">Verify Email</a>
        </body>
        </html>
        ';

        $mail->send();
    } catch (Exception $e) {
        global $message;
        $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Forgot Password - Volunteer Coordination System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <div class="container d-flex justify-content-center align-items-center" style="height: 100vh;">
        <div class="col-sm-6" style="background-color: #ececec; padding:40px; border-radius: 20px;">
            <h2>Forgot Password</h2>
            <p>Enter your email to reset your password.</p>
            <form method="post" action="">
                <div class="form__group field">
                    <input type="email" class="form__field" placeholder="Email" name="email" id='email' required />
                    <label for="Email" class="form__label">Email</label>
                </div>
                <?php
                if (!empty($message)) {
                    echo "<p style='color: red; font-weight: bold; margin-top:5px;'>$message</p>";
                }
                ?>
                <input type="submit" class="login-button" value="Submit">
            </form>
            <p style="text-align: center; margin-top:30px;">Back to <a href="login.php"><u>Login</u></a></p>
        </div>
    </div>
</body>

</html>