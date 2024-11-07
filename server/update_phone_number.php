<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Print the POST array
    // echo '<pre>';
    // print_r($_POST);
    // echo '</pre>';

    $conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if the 'self-introduction' field exists in the POST array
    if (isset($_POST['phone-number'])) {
        $phoneNumber = $_POST['phone-number'];
        $userId = (int)$_POST['user_id'];

        // Prepare the SQL statement with placeholders
        $sql = "UPDATE users SET phone_number = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            echo json_encode(['status' => 'error', 'message' => 'Error preparing SQL statement: ' . $conn->error]);
            exit();
        }

        $stmt->bind_param("si", $phoneNumber, $userId);

        if ($stmt->execute()) {
            // echo "Self-introduction updated successfully";
            // echo json_encode(['status' => 'success', 'message' => 'Self-introduction updated']);
            echo '<script language="javascript">
            alert("Phone number updated");
            window.location.href = "../profile.php";
          </script>';
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error updating record: ' . $conn->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: \'phone-number\' key is missing in POST data.']);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
