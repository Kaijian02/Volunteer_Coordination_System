<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $eventId = (int)$_POST['event_id'];
    $attendanceDate = $_POST['attendance_date'] ?? date('Y-m-d'); // Use today's date for attendance

    if (isset($_POST['attendance']) && is_array($_POST['attendance'])) {
        $attendanceUpdated = false; // Track if any attendance was updated
        $presentUserIds = []; // Array to keep track of user IDs who are present

        foreach ($_POST['attendance'] as $userId => $status) {
            $query = "
                INSERT INTO attendance (user_id, event_id, attendance_date, status)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status)
            ";

            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("iiss", $userId, $eventId, $attendanceDate, $status);
                $stmt->execute();

                if ($status === 'Present' && $stmt->affected_rows > 0) {
                    // Ensure credit does not exceed 100
                    $creditUpdateQuery = "UPDATE users SET credit = LEAST(credit + 2, 100) WHERE id = ? AND credit < 100";
                    $creditStmt = $conn->prepare($creditUpdateQuery);
                    if ($creditStmt) {
                        $creditStmt->bind_param("i", $userId);
                        $creditStmt->execute();
                        $creditStmt->close();
                    }
                    $presentUserIds[] = $userId; // Add to present users list
                } elseif ($status === 'Absent' && $stmt->affected_rows > 0) {
                    // Ensure credit does not go below 0
                    $creditDeductQuery = "UPDATE users SET credit = GREATEST(credit - 2, 0) WHERE id = ? AND credit > 0";
                    $creditStmt = $conn->prepare($creditDeductQuery);
                    if ($creditStmt) {
                        $creditStmt->bind_param("i", $userId);
                        $creditStmt->execute();
                        $creditStmt->close();
                    }
                }

                // Track if at least one update was successful
                if ($stmt->affected_rows > 0) {
                    $attendanceUpdated = true; // Track if at least one update was successful
                }
                $stmt->close();
            }
        }

        // Now update the status in event_applications to 'Participated' only for present users
        if (!empty($presentUserIds)) {
            $userIds = implode(',', $presentUserIds);
            $updateQuery = "
                UPDATE event_applications
                SET status = 'Participated'
                WHERE event_id = ? AND user_id IN ($userIds)
            ";

            $updateStmt = $conn->prepare($updateQuery);
            if ($updateStmt) {
                $updateStmt->bind_param("i", $eventId);
                $updateStmt->execute();

                // Check if the update was successful
                if ($updateStmt->affected_rows > 0) {
                    // Increment event_joined for users who have "Participated" status
                    $incrementQuery = "UPDATE users SET event_joined = event_joined + 1 WHERE id IN ($userIds)";
                    $conn->query($incrementQuery); // Directly execute this query

                    // Attendance updated and event application status changed
                    echo json_encode(['status' => 'success']);
                } else {
                    // Attendance updated, but no event applications were updated
                    if ($attendanceUpdated) {
                        echo json_encode(['status' => 'success', 'message' => 'Attendance updated, but no applications were updated']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'No attendance updated and no applications changed']);
                    }
                }

                $updateStmt->close(); // Close the update statement
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to prepare update query']);
            }
        } else {
            echo json_encode(['status' => 'success']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No attendance data provided']);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
