<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

    if ($conn->connect_error) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        exit();
    }

    if (isset($_POST['review']) && isset($_POST['volunteer_id']) && isset($_POST['event_id'])) {
        $review = $conn->real_escape_string($_POST['review']);
        $volunteerId = (int)$_POST['volunteer_id'];
        $eventId = (int)$_POST['event_id'];
        $organizerId = $_SESSION['user_id'];
        $isEdit = isset($_POST['is_edit']) && $_POST['is_edit'] === '1';

        // Check if a review already exists
        $checkSql = "SELECT * FROM reviews WHERE volunteer_id = ? AND event_id = ? AND organizer_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("iii", $volunteerId, $eventId, $organizerId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $existingReview = $result->fetch_assoc();
            if ($isEdit && $existingReview['updated_at'] === null) {
                // Allow edit if it's the first edit
                $sql = "UPDATE reviews SET review_text = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $review, $existingReview['id']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Review already exists or cannot be edited further']);
                exit();
            }
        } else {
            // Insert new review
            $sql = "INSERT INTO reviews (volunteer_id, event_id, organizer_id, review_text, created_at, updated_at, replied_at, replied_updated_at) VALUES (?, ?, ?, ?, NOW(), NULL, NULL, NULL)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiis", $volunteerId, $eventId, $organizerId, $review);
        }

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Review submitted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error submitting review: ' . $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Required review data is missing.']);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
