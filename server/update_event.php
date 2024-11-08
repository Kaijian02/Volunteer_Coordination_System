<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

$event_id = $_POST['event_id'];
$userId = (int)$_SESSION['user_id'];

// Step 1: Retrieve the original event data
$query = "SELECT start_date, current_volunteers FROM events WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $event_id, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Event not found.']);
    exit();
}

$row = $result->fetch_assoc();
$originalStartDate = new DateTime($row['start_date']);
$currentVolunteers = (int)$row['current_volunteers'];
$currentDate = new DateTime();

// Step 2: Check if the event is less than 5 days away
$interval = $currentDate->diff($originalStartDate);
if ($interval->days < 3 && $interval->invert == 0) { // Only check if the event is in the future
    echo json_encode(['success' => false, 'message' => 'Cannot update the event as it is less than 3 days away.']);
    exit();
}

// Check if the event start date has already passed or is today
if ($currentDate >= $originalStartDate) {
    echo json_encode(['success' => false, 'message' => 'Cannot edit the event as the date has already passed.']);
    exit();
}


$title = $_POST['title'];
$description = $_POST['description'];
$startDate = $_POST['startDate'] ?? null;
$endDate = empty($_POST['endDate']) ? NULL : $_POST['endDate'];
$startTime = $_POST['start_time'];
$endTime = $_POST['end_time'] ?? null;
$venue = $_POST['venue'];
$state = $_POST['state'];
$city = $_POST['city'];
$volunteers = (int)$_POST['volunteers'];
$closeEvent = $_POST['closeEvent'];
$minAge = $_POST['minAge'];
$comments = $_POST['comments'] ?? null;
$donation = $_POST['donation'];
$goal = $_POST['goal'];
$skills = isset($_POST['skills']) ? $_POST['skills'] : '';
$skills = $conn->real_escape_string($skills);

$query = "UPDATE events SET title = ?, description = ?, start_date = ?, end_date = ?, start_time = ?, end_time = ?, venue = ?, state = ?, city = ?, volunteers_needed = ?, close_event = ?, skills = ?, min_age = ?, comments = ?, donation = ?, goal = ? WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssssssssssssssssi", $title, $description, $startDate, $endDate, $startTime, $endTime, $venue, $state, $city, $volunteers, $closeEvent, $skills, $minAge, $comments, $donation, $goal, $event_id);


if ($stmt->execute()) {
    // Check conditions for setting the status based on close_event and volunteers_needed
    if ($closeEvent === 'Yes') {
        if ($volunteers > $currentVolunteers) {
            $status = 'Public';
            $message = 'Event status updated to Public.';
        } else if ($volunteers <= $currentVolunteers) {
            $status = 'Closed';
            $message = 'Event status updated to Closed.';
        }
        $debugInfo['status'] = $status;

        // Update the status in the database
        $statusUpdateQuery = "UPDATE events SET status = ? WHERE id = ?";
        $statusUpdateStmt = $conn->prepare($statusUpdateQuery);
        $statusUpdateStmt->bind_param("si", $status, $event_id);
        $statusUpdateStmt->execute();
        $statusUpdateStmt->close();
    }


    // Handle image upload if provided
    if (isset($_FILES['eventPoster']) && $_FILES['eventPoster']['error'] === UPLOAD_ERR_OK) {
        // Define user-specific directory structure
        $userDir = '../uploads/' . $userId . '/';
        $posterDir = $userDir . 'poster/';
        $eventDir = $posterDir . $event_id . '/';

        // Create directories if they do not exist
        if (!is_dir($userDir)) {
            mkdir($userDir, 0777, true);
        }
        if (!is_dir($eventDir)) {
            mkdir($eventDir, 0777, true);
        }

        // Get file info and sanitize file name
        $fileTmpPath = $_FILES['eventPoster']['tmp_name'];
        $fileName = $_FILES['eventPoster']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

        // Define allowed file extensions
        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'jfif');

        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Full path to save the uploaded file
            $dest_path = $eventDir . $newFileName;

            // Move the file to the uploads directory
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // File is successfully uploaded
                $filePath = $conn->real_escape_string(str_replace('../', '', $dest_path)); // Relative path

                // Update event with the poster image path
                $updateStmt = $conn->prepare("UPDATE events SET event_poster = ? WHERE id = ?");
                $updateStmt->bind_param("si", $filePath, $event_id);
                if ($updateStmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Event and poster image updated successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to save poster image to the database.']);
                }
                $updateStmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'Error moving the uploaded file.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Upload failed. Allowed file types: ' . implode(',', $allowedfileExtensions) . '.']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Event updated successfully without a poster image.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
