<?php
// Include database connection
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the event_id from the GET request
$event_id = $_GET['event_id'];

// First query: Get event details
$eventQuery = "
    SELECT 
        goal AS goal_amount,
        raised AS raised_amount
    FROM events
    WHERE id = ?
";

$eventStmt = $conn->prepare($eventQuery);
$eventStmt->bind_param('i', $event_id);
$eventStmt->execute();
$eventResult = $eventStmt->get_result();
$eventDetails = $eventResult->fetch_assoc();

// Second query: Get donations, summing donations by user
$donationQuery = "
    SELECT 
        u.id AS user_id, 
        u.name AS user_name, 
        SUM(d.donation_amount) AS total_donation, 
        COUNT(d.id) AS donation_count
    FROM donations d
    JOIN users u ON d.user_id = u.id
    WHERE d.event_id = ?
    GROUP BY u.id
    ORDER BY u.name ASC
";

$donationStmt = $conn->prepare($donationQuery);
$donationStmt->bind_param('i', $event_id);
$donationStmt->execute();
$donationResult = $donationStmt->get_result();

// Check if any donations were found
if ($donationResult->num_rows > 0) {
    echo '<h4>Donation List</h4>';
    echo '<div class="scrollable-table-container">';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Name</th><th>Total Donation Amount</th><th>Number of Donations</th></tr></thead>';
    echo '<tbody>';

    // Variable to store total donations received
    $totalDonated = 0;

    // Loop through each donation and display
    while ($row = $donationResult->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['user_name']) . '</td>';
        echo '<td>$' . htmlspecialchars(number_format($row['total_donation'], 2)) . '</td>';
        echo '<td>' . htmlspecialchars($row['donation_count']) . '</td>';
        echo '</tr>';

        // Accumulate total donations
        $totalDonated += $row['total_donation'];
    }

    echo '</tbody></table>';
    echo '</div>';

    // Display event goal and raised amount
    echo '<div style="margin-top: 20px;">';
    echo '<h5>Event Financial Summary</h5>';
    echo '<p><strong>Goal Amount:</strong> $' . htmlspecialchars(number_format($eventDetails['goal_amount'], 2)) . '</p>';
    echo '<p><strong>Raised Amount:</strong> $' . htmlspecialchars(number_format($eventDetails['raised_amount'], 2)) . '</p>';
    echo '<p><strong>Total Donations Received:</strong> $' . htmlspecialchars(number_format($totalDonated, 2)) . '</p>';
    echo '</div>';
} else {
    echo '<h4>Donation List</h4>';
    echo '<p>No donations found for this event.</p>';
}

// Debugging output
// echo "<p>Total Donations Found: " . $donationResult->num_rows . "</p>";

// Close the statements and connection
$eventStmt->close();
$donationStmt->close();
$conn->close();
