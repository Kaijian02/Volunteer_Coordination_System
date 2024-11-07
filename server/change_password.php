<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if the required fields exist in the POST array
    if (isset($_POST['old-password'], $_POST['new-password'], $_POST['confirm-password'])) {
        $oldPassword = $conn->real_escape_string($_POST['old-password']);
        $newPassword = $conn->real_escape_string($_POST['new-password']);
        $confirmPassword = $conn->real_escape_string($_POST['confirm-password']);
        $userId = (int)$_SESSION['user_id'];

        // Fetch the current password from the database using a prepared statement
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $dbPassword = $row['password'];

            // Verify the old password
            if (password_verify($oldPassword, $dbPassword)) {
                // Check if new password matches confirm password
                if ($newPassword === $confirmPassword) {
                    // Check if the new password is different from the old password
                    if (!password_verify($newPassword, $dbPassword)) {
                        // Hash the new password and update the database
                        $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                        // Update the password in the database using a prepared statement
                        $sql = "UPDATE users SET password = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $newHashedPassword, $userId);

                        if ($stmt->execute()) {
                            // Show a success message

                            echo json_encode(['status' => 'success', 'message' => 'Password changed successfully. You will be logout in 3 seconds. Please login again.']);
                        } else {
                            echo json_encode(['status' => 'error', 'message' => 'Error updating password: ' . $conn->error]);
                        }
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'New password cannot be the same as the old password.']);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'New password and confirm password do not match.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Old password is incorrect.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User not found.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Required fields are missing in POST data.']);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
