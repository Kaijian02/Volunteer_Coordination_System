<?php
session_start();

header('Content-Type: application/json'); // Set header for JSON response

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$title = $_POST['title'];
$description = $_POST['description'];
$startDate = $_POST['startDate'];
$endDate = $_POST['endDate'] ?? null; // Use null if not set
$startTime = $_POST['start_time'];
$endTime = $_POST['end_time'];
$venue = $_POST['venue'];
$state = $_POST['state'];
$city = $_POST['city'];
$volunteers = $_POST['volunteers'];
$closeEvent = $_POST['closeEvent'];
$minAge = $_POST['minAge'];
$comments = $_POST['comments'] ?? null;
$donation = $_POST['donation'];
$goal = $_POST['goal'];
$skills = isset($_POST['skills']) ? $_POST['skills'] : '';
$skills = $conn->real_escape_string($skills);
$status = 'Public';

$userId = (int)$_SESSION['user_id'];

if (is_null($comments) || trim($comments) === '') {
    $comments = 'None';
} else {
    $comments = $conn->real_escape_string($comments);
}


// Handle one-day event
$isOneDay = $_POST['isOneDay'] ?? 'No';
if ($isOneDay === 'Yes') {
    $endDate = null; // Set end date to null for one-day events
}

if ($comments === '') {
    $comments = null;
}

if ($skills === '') {
    $skills = null;
}

// Insert event data without the poster to get the event ID
$stmt = $conn->prepare("INSERT INTO events (title, description, start_date, end_date, start_time, end_time, venue, state, city, volunteers_needed, close_event, skills, min_age, comments, donation, goal, status, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssssssssssssss", $title, $description, $startDate, $endDate, $startTime, $endTime, $venue, $state, $city, $volunteers, $closeEvent, $skills, $minAge, $comments, $donation, $goal, $status, $userId);

if ($stmt->execute()) {
    $eventId = $stmt->insert_id; // Get the ID of the newly created event

    // Update the event_created count for the user
    $updateCountStmt = $conn->prepare("UPDATE users SET event_created = event_created + 1 WHERE id = ?");
    $updateCountStmt->bind_param("i", $userId);
    $updateCountStmt->execute();
    $updateCountStmt->close();

    // Handle image upload
    // Define user-specific directory structure
    $userDir = '../uploads/' . $userId . '/';
    $posterDir = $userDir . 'poster/';
    $eventDir = $posterDir . $eventId . '/';

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

    // Full path to save the uploaded file
    $dest_path = $eventDir . $newFileName;

    // Define the file path relative to the uploads directory
    $relativePath = str_replace('../', '', $dest_path);

    // Move the file to the uploads directory
    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        // File is successfully uploaded
        $filePath = $conn->real_escape_string($relativePath);

        // Update event with the poster image path
        $updateStmt = $conn->prepare("UPDATE events SET event_poster = ? WHERE id = ?");
        $updateStmt->bind_param("si", $filePath, $eventId);
        if ($updateStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Event and poster image saved successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save poster image to the database.']);
        }
        $updateStmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Error moving the uploaded file.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
