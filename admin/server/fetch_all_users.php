<?php
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$verified = isset($_GET['verified']) && $_GET['verified'] == '1';

// Query to fetch users based on the verified status
$query = $verified
    ? "SELECT id AS user_id, name AS user_name, email, date_joined FROM users WHERE verified_by_admin = 1 AND is_verified = 1 AND role = 'user' ORDER BY user_name ASC"
    : "SELECT id AS user_id, name AS user_name, email, date_joined FROM users WHERE verified_by_admin = 0 AND is_verified = 1 AND role = 'user' ORDER BY user_name ASC";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo '<h4>' . ($verified ? 'Verified Users' : 'Manage Users') . '</h4>';
    if (!$verified) {
        echo '<p class="text-muted">Check user profiles to validate their information, especially their certificates.</p>';
    } else {
        echo '<p class="text-muted">View users whose certificates have been verified and validated.</p>';
    }

    // Notify All Button
    if (!$verified) {
        echo '<button class="btn btn-warning btn-sm notify-all-btn">Notify All Users who havent completed their profiles</button>';
    }

    echo '<div class="scrollable-table-container">';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Name</th><th>Email</th><th>Date Joined</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['user_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
        echo '<td>' . htmlspecialchars($row['date_joined']) . '</td>';
        echo '<td>';
        echo '<a href="view_profile.php?user_id=' . $row['user_id'] . '" class="btn btn-info btn-sm me-2">View Profile</a>';

        // Only show the verify button for unverified users
        if (!$verified) {
            echo '<button class="btn btn-warning btn-sm notify-btn me-2" data-id="' . $row['user_id'] . '" data-email="' . $row['email'] . '">Notify</button>';
            echo '<button class="btn btn-success btn-sm verify-btn me-2" data-id="' . $row['user_id'] . '">Verify</button>';
        }

        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
} else {
    echo '<h4>' . ($verified ? 'Verified Users' : 'Manage Users') . '</h4>';
    echo '<p>No users found.</p>';
}

// Close the connection
$conn->close();
