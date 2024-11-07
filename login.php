<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if email exists and is verified
    $sql = "SELECT id, password, is_verified, token, failed_attempts, last_failed_attempt, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password, $is_verified, $token, $failed_attempts, $last_failed_attempt, $role);
    $stmt->fetch();

    if ($stmt->num_rows > 0) {

        $failed_attempts = $failed_attempts ?? 0;
        $last_failed_attempt = $last_failed_attempt ?? null;

        // Calculate cooldown time if any
        $cooldown_period = 30 * 60; // 30 minutes
        $extra_time = ($failed_attempts - 5) * 15 * 60; // Add 15 mins after 5 wrong attempts
        $cooldown_time = $cooldown_period + max(0, $extra_time);
        $current_time = time();

        // Calculate unlock time
        $unlock_time = strtotime($last_failed_attempt) + $cooldown_time;

        if ($failed_attempts >= 5 && $unlock_time > $current_time) {
            // Format unlock time for user-friendly display
            $unlock_time_formatted = date("g:i A, M j", $unlock_time);
            $message = "Too many failed attempts. You can try logging in again at $unlock_time_formatted.";
        } else {

            // Verify password
            if (password_verify($password, $hashed_password)) {
                // Check if email is verified
                if ($is_verified == 1) {
                    // Reset the attempts
                    $reset_sql = "UPDATE users SET failed_attempts = 0, last_failed_attempt = NULL WHERE id = ?";
                    $reset_stmt = $conn->prepare($reset_sql);
                    $reset_stmt->bind_param("i", $id);
                    $reset_stmt->execute();

                    // Start session and set session variables
                    session_start();
                    $_SESSION['user_id'] = $id;
                    $_SESSION['email'] = $email;

                    if ($role === 'admin') {
                        header("Location: admin/admin_home.php");
                    } else {
                        header("Location: home.php");
                    }
                    exit();
                } else {
                    // Resend verification email
                    sendVerificationEmail($email, $token);
                    $message = "Your account is not verified. A new verification email has been sent.";
                }
            } else {
                // Increment failed attempts and set last failed attempt timestamp
                $failed_attempts++;
                $update_sql = "UPDATE users SET failed_attempts = ?, last_failed_attempt = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ii", $failed_attempts, $id);
                $update_stmt->execute();
                $message = "Invalid email or password.";
            }
        }
    } else {
        $message = "Invalid email or password.";
    }

    $stmt->close();
    $conn->close();
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
    <title>Volunteer Coordination System</title>
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
            <h2>Login</h2>
            <form method="post" action="">
                <p>Login to access the Volunteer Coordination System.</p>
                <div class="form__group field">
                    <input type="email" class="form__field" placeholder="Email" name="email" id='email' required />
                    <label for="Email" class="form__label">Email</label>
                </div>
                <div class="form__group field">
                    <input type="password" class="form__field" placeholder="Password" name="password" id='password' required />
                    <label for="password" class="form__label">Password</label>
                    <span class="toggle-password" onclick="togglePasswordVisibility()">
                        <span class="material-symbols-outlined" id="eye-icon">visibility_off</span>
                    </span>
                </div>
                <br />
                <a href="forgot_password.php" style="text-align:right;">Forgot Password</a>
                <?php
                if (!empty($message)) {
                    echo "<p style='color: red; font-weight: bold; margin-top:5px;'>$message</p>";
                }
                ?>
                <button type="submit" class="login-button">Login</button>
                <p style="text-align: center; margin-top:30px;">Don't have an account? <a href="register.php"><u>Register</u></a></p>
            </form>
        </div>
    </div>
</body>

</html>

<script>
    function togglePasswordVisibility() {
        var passwordField = document.getElementById('password');
        var eyeIcon = document.getElementById('eye-icon');
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            eyeIcon.textContent = 'visibility';
        } else {
            passwordField.type = 'password';
            eyeIcon.textContent = 'visibility_off';
        }
    }
</script>