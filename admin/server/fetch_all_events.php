<?php
$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get filter value from GET request
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Query to fetch events
$query = "SELECT id, title, start_date, end_date FROM events";

// Modify query based on the filter value
$now = date('Y-m-d');

if ($filter == 'upcoming') {
    $query .= " WHERE start_date > '$now'";
} elseif ($filter == 'ongoing') {
    $query .= " WHERE start_date <= '$now' AND ((end_date IS NULL AND start_date = '$now') OR (end_date >= '$now'))";
} elseif ($filter == 'passed') {
    $query .= " WHERE (end_date < '$now' OR (end_date IS NULL AND start_date < '$now'))";
}

$query .= " ORDER BY start_date ASC";
$result = $conn->query($query);

// Events Table
echo '<table class="table table-striped">
        <thead>
            <tr>
                <th>Title</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['title']) . '</td>';
        echo '<td>' . htmlspecialchars($row['start_date']) . '</td>';
        echo '<td>' . ($row['end_date'] ? htmlspecialchars($row['end_date']) : 'One Day Event') . '</td>';
        echo '<td>';
        echo '<a href="event_detail.php?event_id=' . $row['id'] . '" class="btn btn-info btn-sm">View Details</a>';
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="4">No events found.</td></tr>';
}

echo '</tbody>
    </table>';

// Close connection
$conn->close();
