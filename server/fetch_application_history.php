<?php
// application_history.php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Function to get secure file path
function getSecureFilePath($filePath)
{
    // Base directory where your uploads are stored
    $baseDir = realpath(__DIR__ . '/../'); // Adjust this to point to your project's root directory

    // Remove any leading '../' from the file path
    $filePath = preg_replace('/^(\.\.\/)+/', '', $filePath);

    // Construct the full server path
    $fullPath = realpath($baseDir . '/' . $filePath);

    // Check if the file exists and is within the allowed directory
    if ($fullPath && strpos($fullPath, $baseDir) === 0) {
        // File exists and is within the allowed directory
        // Return a web-accessible URL
        return '/VolunteerCoordinationSystem/' . $filePath; // This matches your current URL structure
    } else {
        // File doesn't exist or is outside the allowed directory
        return null;
    }
}



$user_id = $_SESSION['user_id'];
$status = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT ea.id, e.title AS event_name, ea.status, ea.applied_date, ea.cancelled_date, ea.approval_date, e.start_date, e.end_date, ea.pending_cancelled_date, ea.reason, ea.rejected_date, ea.evidence
        FROM event_applications ea 
        JOIN events e ON ea.event_id = e.id 
        WHERE ea.user_id = ?";

if (!empty($status)) {
    // Check if the status is 'Cancelled' or 'Cancellation Approved'
    if ($status === 'Cancelled') {
        $sql .= " AND ea.status IN ('Cancelled', 'Cancellation Approved')";
    } else {
        $sql .= " AND ea.status = ?";
    }
}

$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($status) && $status === 'Cancelled') {
        // Only bind user_id when filtering for cancelled statuses
        $stmt->bind_param("i", $user_id);
    } elseif (!empty($status)) {
        $stmt->bind_param("is", $user_id, $status); // Bind both user_id and status
    } else {
        $stmt->bind_param("i", $user_id); // Bind only user_id
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $applications = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate the difference between the current date and the event's start date
        $current_date = new DateTime();
        $start_date = new DateTime($row['start_date']);
        $interval = $current_date->diff($start_date)->days;
        $row['evidence'] = getSecureFilePath($row['evidence']);

        // Determine if the user can cancel based on the hybrid approach
        if ($row['status'] === 'Approved') {
            if ($interval < 3) {
                // Cannot cancel within 3 days
                $row['can_cancel'] = false;
                $row['cancel_message'] = "The event is starting soon, you cannot cancel.";
            } elseif ($interval >= 3 && $interval < 7) {
                // Requires reason and organizer approval within 7 days but before 3 days
                $row['can_cancel'] = true;
                $row['requires_reason'] = true;
                $row['cancel_message'] = "Cancellation requires organizer approval.";
            } else {
                // Can cancel directly before 7 days
                $row['can_cancel'] = true;
                $row['requires_reason'] = false;
                $row['cancel_message'] = "";
            }
        } else {
            // Handle other statuses (Applying, etc.)
            $row['can_cancel'] = true;
            $row['cancel_message'] = "";
        }

        // Add the row to the applications array
        $applications[] = $row;
    }

    echo json_encode(['success' => true, 'applications' => $applications]);

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
}

$conn->close();
