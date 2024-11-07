<?php
// Include database connection
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

// Function to get secure file path
function getSecureFilePath($filePath)
{
    $baseDir = realpath(__DIR__ . '/../'); // Adjust to point to your project's root directory
    $filePath = preg_replace('/^(\.\.\/)+/', '', $filePath); // Clean file path
    $fullPath = realpath($baseDir . '/' . $filePath);

    if ($fullPath && strpos($fullPath, $baseDir) === 0) {
        return '/VolunteerCoordinationSystem/' . $filePath; // Web-accessible URL
    } else {
        return null;
    }
}


$query = "
    SELECT u.id AS user_id, u.name AS user_name, u.dob, u.email, 
           ea.pending_cancelled_date, ea.reason, ea.evidence
    FROM event_applications ea
    JOIN users u ON ea.user_id = u.id
    WHERE ea.event_id = ? AND ea.status = 'Pending Cancellation'
    ORDER BY u.name ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<h4>Pending Cancelled List</h4>';
    echo '<p>Volunteers who have been recruited but requested to cancel.</p>';
    echo '<div class="scrollable-table-container">';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Name</th><th>Age</th><th>Email</th><th>Date</th><th>Reason</th><th>Evidence</th><th>Actions(Penalty)</th></tr></thead>';
    echo '<tbody>';

    while ($row = $result->fetch_assoc()) {
        $dob = new DateTime($row['dob']);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
        // Ensure the evidence path is secure
        $evidenceUrl = getSecureFilePath($row['evidence']);

        echo '<tr>';
        echo '<td>' . $row['user_name'] . '</td>';
        echo '<td>' . $age . '</td>';
        echo '<td>' . $row['email'] . '</td>';
        echo '<td>' . $row['pending_cancelled_date'] . '</td>';
        echo '<td>' . htmlspecialchars($row['reason']) . '</td>';
        echo '<td>';
        if ($evidenceUrl) {
            echo '<button class="btn btn-info btn-sm view-evidence-btn" data-url="' . $evidenceUrl . '">View Evidence</button>';
        } else {
            echo 'No evidence uploaded';
        }
        echo '</td>';
        echo '<td>';
        echo '<div class="d-flex">';
        echo '<button class="btn btn-success btn-sm accept-cancel-btn me-2" data-id="' . $row['user_id'] . '">Confirm (w/o)</button>';
        echo '<button class="btn btn-danger btn-sm accept-cancel-penalty-btn me-2" data-id="' . $row['user_id'] . '">Confirm (w/)</button>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
} else {
    echo '<h4>Pending Cancelled List</h4>';
    echo '<p>No pending cancellation requests found for this event.</p>';
}

$stmt->close();
$conn->close();
