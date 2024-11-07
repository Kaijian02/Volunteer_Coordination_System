<?php
// Include database connection
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

$event_id = $_GET['event_id'];

$query = "
    SELECT u.id AS user_id, u.name AS user_name, u.dob, u.email
    FROM event_applications ea
    JOIN users u ON ea.user_id = u.id
    WHERE ea.event_id = ? AND ea.status = 'Applying'
     ORDER BY u.name ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<h4>Recruitment List</h4>';
    echo '<div class="scrollable-table-container">';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Name</th><th>Age</th><th>Email</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    while ($row = $result->fetch_assoc()) {
        $dob = new DateTime($row['dob']);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
        echo '<tr>';
        echo '<td>' . $row['user_name'] . '</td>';
        echo '<td>' . $age . '</td>';
        echo '<td>' . $row['email'] . '</td>';
        echo '<td>';
        echo '<button class="btn btn-success btn-sm accept-btn me-2" data-id="' . $row['user_id'] . '">Accept</button>';
        echo '<button class="btn btn-danger btn-sm reject-btn me-2" data-id="' . $row['user_id'] . '">Reject</button>';
        echo '<a href="view_profile.php?user_id=' . $row['user_id'] . '" class="btn btn-info btn-sm">View Profile</a>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
} else {
    echo '<h4>Recruitment List</h4>';
    echo '<p>No applications found for this event.</p>';
}
