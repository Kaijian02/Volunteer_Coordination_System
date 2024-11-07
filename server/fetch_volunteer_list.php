<?php
//fetch_volunteer_list.php
session_start();
// Database connection
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the event_id from the query string
$event_id = $_GET['event_id'];
$organizer_id = $_SESSION['user_id'];

// SQL query to get the list of approved volunteers for the event
$query = "
    SELECT u.id AS user_id, u.name AS user_name, u.dob, u.email, ea.approval_date, e.end_date, e.start_date,
    r.review_text, r.created_at AS review_date, r.updated_at AS review_updated_at
    FROM event_applications ea 
    JOIN users u ON ea.user_id = u.id
    JOIN events e ON ea.event_id = e.id
    LEFT JOIN reviews r ON r.volunteer_id = u.id AND r.event_id = e.id AND r.organizer_id = ? 
    WHERE ea.event_id = ? AND (ea.status = 'Approved' OR ea.status = 'Participated')
     ORDER BY u.name ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $organizer_id, $event_id);
$stmt->execute();
$result = $stmt->get_result();

// Display the list of approved volunteers
if ($result->num_rows > 0) {
    echo '<h4>Approved Volunteers</h4>';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Name</th><th>Age</th><th>Email</th><th>Approval Date & Time</th><th>Review</th><th>Action</th></tr></thead>';
    echo '<tbody>';
    while ($row = $result->fetch_assoc()) {
        $dob = new DateTime($row['dob']);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
        $eventEndDate = new DateTime($row['end_date']);
        if (is_null($row['end_date'])) {
            $eventEndDate = new DateTime($row['start_date']);
        }
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['user_name']) . '</td>';
        echo '<td>' . $age . '</td>';
        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
        echo '<td style="width: 200px;">' . $row['approval_date'] . '</td>';
        echo '<td style="width: 200px;">';
        if ($now > $eventEndDate) {
            if ($row['review_text']) {
                // Review exists
                echo '<p>' . htmlspecialchars($row['review_text']) . '</p>';
                if ($row['review_updated_at']) {
                    echo '<p class="meta">Edited on: ' . $row['review_updated_at'] . '</p>';
                } else {
                    echo '<p class="meta">Left on: ' . $row['review_date'] . '</p>';
                }
                if ($row['review_updated_at'] && $row['review_updated_at'] != $row['review_date']) {
                    // Review has been edited
                    echo '<p class="meta">Review cannot be edited further.</p>';
                } else {
                    // Review can be edited once
                    echo '<button type="button" class="btn btn-secondary btn-sm edit-review-btn" data-user-id="' . $row['user_id'] . '">Edit Review</button>';
                }
            } else {
                // No review yet
                echo '<button type="button" class="btn btn-primary btn-sm leave-review-btn" data-user-id="' . $row['user_id'] . '">Leave a Review</button>';
            }
        } else {
            echo 'You can leave a review for the volunteer after the event.';
        }
        echo '</td>';

        echo '<td>';
        echo '<button class="btn btn-warning btn-sm cancel-btn" data-id="' . $row['user_id'] . '">Cancel</button> ';
        echo '<a href="view_profile.php?user_id=' . $row['user_id'] . '" class="btn btn-info btn-sm">View Profile</a>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<h4>Approved Volunteers</h4>';
    echo '<p>No approved volunteers found for this event.</p>';
}

// Close the connection
$conn->close();
