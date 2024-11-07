<?php
// Assuming you already have a session check here
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the posted data
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['reviewId']) && isset($data['replyText'])) {
    $reviewId = $data['reviewId'];
    $replyText = $data['replyText'];

    // Check if there is already a reply for this review
    $sql_check = "SELECT replied_text, replied_at FROM reviews WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $reviewId);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $row = $result_check->fetch_assoc();

        if ($row['replied_text'] === null || $row['replied_at'] === null) {
            // First-time reply: Update replied_text and replied_at
            $sql_update = "UPDATE reviews SET replied_text = ?, replied_at = NOW() WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $replyText, $reviewId);
        } else {
            // Edit reply: Update replied_text and replied_updated_at
            $sql_update = "UPDATE reviews SET replied_text = ?, replied_updated_at = NOW() WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $replyText, $reviewId);
        }

        if ($stmt_update->execute()) {
            echo json_encode(['success' => true, 'message' => 'Reply submitted/updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit/update reply']);
        }

        $stmt_update->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Review not found']);
    }

    $stmt_check->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
