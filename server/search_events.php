<?php
session_start();
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = $_POST['query'] ?? '';
$location = $_POST['location'] ?? ''; // Get the location from POST data
$filterBySkill = $_POST['filterBySkill'] ?? '0';
$userId = $_SESSION['user_id'] ?? 0; // Assuming the user is logged in and has a valid user ID

$sql = "SELECT * FROM event WHERE title LIKE ?";
$searchParam = "%" . $query . "%";
$params = ["s", $searchParam]; // Start with the search parameter

// If location is specified, add it to the SQL query
if (!empty($location)) {
    $sql .= " AND venue LIKE ?";
    $locationParam = "%" . $location . "%";
    $params[0] .= "s"; // Add another 's' for the location string
    $params[] = $locationParam;
}

$userSkills = ''; // Initialize an empty variable for user skills

// If the filter is enabled, match the events with user skills
if ($filterBySkill === '1' && $userId > 0) {
    $userSkillsSql = "SELECT skills FROM users WHERE id = ?";
    $stmtUserSkills = $conn->prepare($userSkillsSql);
    $stmtUserSkills->bind_param("i", $userId);
    $stmtUserSkills->execute();
    $resultUserSkills = $stmtUserSkills->get_result();
    $userSkills = $resultUserSkills->fetch_assoc()['skills'] ?? '';

    // Debugging: Log fetched user skills
    error_log("User Skills: " . $userSkills);

    // If user has skills, include them in the event matching
    if ($userSkills) {
        $skillsArray = explode(',', $userSkills);
        $skillsPlaceholder = implode(',', array_fill(0, count($skillsArray), '?'));
        $sql .= " AND (" . implode(" OR ", array_map(function ($skill) {
            return "skills LIKE ?";
        }, $skillsArray)) . ")";

        // Add the skills to the params array
        foreach ($skillsArray as $skill) {
            $params[0] .= "s"; // Add one more string type for each skill
            $params[] = "%" . trim($skill) . "%"; // Add the actual skill wrapped in wildcards
        }

        // Debugging: Log updated SQL and parameters after filtering by skills
        error_log("Updated SQL with Skills: " . $sql);
        error_log("Updated Params with Skills: " . print_r($params, true));
    }
}

$stmt = $conn->prepare($sql);

// Debugging: Check if the statement was prepared successfully
if (!$stmt) {
    error_log("Statement preparation failed: " . $conn->error);
    echo "Error preparing the statement.";
    exit();
}

// Use a reference array for bind_param to avoid errors
$stmt->bind_param(...$params);

// Debugging: Log the final bound parameters
error_log("Final Bound Params: " . print_r($params, true));

$stmt->execute();
$result = $stmt->get_result();

$matchingEvents = []; // To store events that match the criteria

while ($row = $result->fetch_assoc()) {
    // Debugging: Log each event's skills
    error_log("Event Title: " . $row['title']);
    error_log("Event Skills: " . $row['skills']); // Log the skills of each event for debugging purposes

    // Check if any user skill matches the event's skill
    if ($filterBySkill === '1' && $userSkills) {
        $eventSkills = explode(',', $row['skills']); // Split event skills into an array
        $eventMatches = array_intersect($skillsArray, array_map('trim', $eventSkills)); // Find matching skills

        // Debugging: Log matching skills
        error_log("Matching Skills for Event '" . $row['title'] . "': " . implode(", ", $eventMatches));

        if (!empty($eventMatches)) {
            $matchingEvents[] = $row; // Only add if there are matching skills
        }
    } else {
        $matchingEvents[] = $row; // Add all events if no filter is applied
    }
}

// Check if any results were fetched
if (empty($matchingEvents)) {
    error_log("No matching events found.");
    echo json_encode([
        "status" => "error",
        "message" => "No matching events found.",
        "userSkills" => $userSkills,
        "events" => []
    ]);
} else {
    // Return user skills and events in the JSON response
    echo json_encode([
        "status" => "success",
        "message" => "Matching events found.",
        "userSkills" => $userSkills,
        "events" => $matchingEvents
    ]);
}

$stmt->close();
$conn->close();
?>
