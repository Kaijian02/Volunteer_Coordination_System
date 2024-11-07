<?php
// deduct_credit.php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get the user ID and credit deduction amount from the request
$userId = $_POST['user_id'];
$creditDeduction = $_POST['credit'];

// Update the user's credit in the database
$sql = "UPDATE users SET credit = credit - ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $creditDeduction, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Credit deducted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to deduct credit.']);
}

$stmt->close();
$conn->close();
