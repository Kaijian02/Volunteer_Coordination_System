<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        // Database connection
        $conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Check if token is valid and not expired
        $sql = "SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id);
        $stmt->fetch();

        if ($stmt->num_rows > 0) {
            // Update password and invalidate token
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed_password, $id);
            if ($stmt->execute()) {
                $message = "Password reset successfully.";
            } else {
                $message = "Error updating password.";
            }
        } else {
            $message = "Invalid or expired token.";
        }

        $stmt->close();
        $conn->close();
    }
} elseif (isset($_GET['token'])) {
    $token = $_GET['token'];
} else {
    die("Invalid request.");
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Reset Password - Volunteer Coordination System</title>
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
            <h2>Reset Password</h2>
            <p>Enter your new password.</p>
            <form method="post" action="" onsubmit="return validatePassword()">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="form__group field">
                    <input type="password" class="form__field" placeholder="New Password" name="new_password" id='new_password' required />
                    <label for="new_password" class="form__label">New Password</label>
                    <small id="passwordHelp" class="text-muted">Password must be at least 6 characters long and contain at least one letter and one symbol.</small>
                </div>
                <div class="form__group field">
                    <input type="password" class="form__field" placeholder="Confirm Password" name="confirm_password" id='confirm_password' required />
                    <label for="confirm_password" class="form__label">Confirm Password</label>
                </div>
                <p id="error-message" style="color: red; font-weight: bold; margin-top:5px;"></p>
                <?php
                if (!empty($message)) {
                    echo "<p style='color: red; font-weight: bold; margin-top:5px;'>$message</p>";
                }
                ?>
                <input type="submit" class="login-button" value="Reset Password">
            </form>
        </div>
    </div>

    <script>
        // Regex pattern: At least 6 characters, one letter and one symbol
        const passwordPattern = /^(?=.*[A-Za-z])(?=.*[\W]).{6,}$/;

        function validatePassword() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const errorMessage = document.getElementById('error-message');

            if (!passwordPattern.test(newPassword)) {
                errorMessage.textContent = "Password must be at least 6 characters long and contain at least one letter and one symbol.";
                return false;
            }

            if (newPassword !== confirmPassword) {
                errorMessage.textContent = "Passwords do not match.";
                return false;
            }

            return true;
        }
    </script>
</body>

</html>