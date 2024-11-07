<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$eventTerm = $_POST['event_term'] ?? '';
$locationTerm = $_POST['location_term'] ?? '';
$skillTerm = $_POST['skill_term'] ?? '';
$filter_by_skill = $_POST['filter_by_skill'] ?? '0';
$filter_by_donation = $_POST['filter_by_donation'] ?? '0';
$stateTerm = $_POST['state_term'] ?? '';
$cityTerm = $_POST['city_term'] ?? '';

$query = "SELECT * FROM events WHERE status = 'Public'";
$query .= " AND start_date > CURDATE()";
$params = []; // Parameters array
$paramTypes = '';

// Add conditions based on provided terms
if (!empty($eventTerm)) {
    $query .= " AND title LIKE ?";
    $params[] = "%" . $eventTerm . "%";
    $paramTypes .= "s";
}

// if (!empty($locationTerm)) {
//     $query .= " AND state LIKE ?";
//     // $params[] = "%" . $locationTerm . "%";
//     $params[] = $locationTerm;
//     $paramTypes .= "s";
// }
if (!empty($stateTerm)) {
    $query .= " AND state LIKE ?";
    $params[] = $stateTerm;
    $paramTypes .= "s";
}

if (!empty($cityTerm)) {
    $query .= " AND city LIKE ?";
    $params[] = $cityTerm;
    $paramTypes .= "s";
}

if (!empty($skillTerm)) {
    $query .= " AND FIND_IN_SET(?, skills) > 0";
    $params[] = $skillTerm;
    $paramTypes .= "s";
}

// Filter by user skills
if ($filter_by_skill === '1') {
    // Fetch user skills from the database
    $stmt_user = $conn->prepare("SELECT skills FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user_skills = '';

    if ($row = $result_user->fetch_assoc()) {
        $user_skills = $row['skills'];
    }

    if (empty($user_skills)) {
        // Show a message if the user hasn't set up their skills
        echo '<p>You haven\'t set up your skills yet.</p>';
        exit(); // Stop further execution
    }

    if (!empty($user_skills)) {
        $user_skills_array = explode(',', $user_skills);
        $query .= " AND (";
        foreach ($user_skills_array as $index => $skill) {
            if ($index > 0) {
                $query .= " OR ";
            }
            $query .= "skills LIKE ?";
            $params[] = "%" . trim($skill) . "%"; // Add the actual skill wrapped in wildcards
            $paramTypes .= "s"; // Add one more string type for each skill
        }
        $query .= ")";
    }
}

// Filter by donation needs
if ($filter_by_donation === '1') {
    $query .= " AND donation = 'Yes'";
}

// Prepare and execute the query
$stmt = $conn->prepare($query);
if ($paramTypes) {
    $stmt->bind_param($paramTypes, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Fetch organizer name based on user_id
        $organizer_id = $row['user_id'];
        $stmt_organizer = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $stmt_organizer->bind_param("i", $organizer_id);
        $stmt_organizer->execute();
        $result_organizer = $stmt_organizer->get_result();
        $organizer_name = '';

        if ($organizer_row = $result_organizer->fetch_assoc()) {
            $organizer_name = $organizer_row['name'];
        }

        // Display skills or a message if no skills are required
        $skills = $row['skills'];
        $skills_display = !empty($skills) ? htmlspecialchars($skills) : 'No skill required';

        // Echo event item HTML
        echo '<a href="event_detail.php?event_id=' . $row['id']  . '" class="event-link">';
        echo '<div class="event-item">';
        echo '<div class="event-content">';
        echo '<h4 class="event-title">' . htmlspecialchars($row['title']) . '</h4>';
        echo '<p class="event-organizer">Organizer: ' . htmlspecialchars($organizer_name) . '</p>';
        echo '<p class="event-dates">Date: ' . htmlspecialchars($row['start_date']) . ' - ' . htmlspecialchars($row['end_date']) . '</p>';
        echo '<p class="event-venue">Venue: ' . htmlspecialchars($row['venue']) . '</p>';
        echo '<p class="event-description">' . htmlspecialchars($row['description']) . '</p>';
        echo '</br>';
        echo '<p class="event-skills">Skills: ' . $skills_display . '</p>';
        if ($row['donation'] === 'Yes') {
            $raisedAmount = htmlspecialchars($row['raised']);
            $goalAmount = htmlspecialchars($row['goal']);
            echo '<p class="event-donation">Raised: ' . $raisedAmount . ' / Goal: ' . $goalAmount . '</p>';
        } else {
            echo '<p class="event-donation">Donation Needed: No</p>';
        }
        echo '</br>';
        echo '<p class="event-meta">Location: ' . htmlspecialchars($row['city']) . '</p>';
        echo '<p class="event-meta">Date Posted: ' . htmlspecialchars($row['date_created']) . '</p>';
        echo '</div>';
        echo '<div class="event-poster">';
        echo '<img src="' . htmlspecialchars($row['event_poster']) . '" alt="Event Poster">';
        echo '</div>';
        echo '</div>';
        echo '</a>';
    }
} else {
    echo '<p>No events found.</p>';
}

$stmt->close();
$conn->close();
