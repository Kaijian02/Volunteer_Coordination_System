<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Database connection
    $conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare the SQL statement to fetch user details
    $sql = "SELECT * FROM users WHERE id = ? ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $profile = $result->fetch_assoc();
    } else {
        echo "User not found.";
        $stmt->close();
        $conn->close();
        exit();
    }

    // Fetch certificates for this user
    $certificates = [];
    $sql_certs = "SELECT file_name FROM user_certificates WHERE user_id = ?";
    $stmt_certs = $conn->prepare($sql_certs);
    $stmt_certs->bind_param("i", $user_id);
    $stmt_certs->execute();
    $result_certs = $stmt_certs->get_result();

    // Loop through all certificates and store them in the $certificates array
    while ($row = $result_certs->fetch_assoc()) {
        $certificates[] = $row;
    }
    $stmt_certs->close(); // Close after certificate query is done


    // Fetch reviews for this user (volunteer)
    $reviews = [];
    $sql_reviews = "SELECT r.id, r.review_text, r.created_at, r.updated_at, r.replied_at, r.replied_text, r.replied_updated_at,
                           u.name AS reviewer_name, e.title 
                    FROM reviews r
                    JOIN users u ON r.organizer_id = u.id
                    JOIN events e ON r.event_id = e.id
                    WHERE r.volunteer_id = ?"; // Only reviews where the user is the volunteer
    $stmt_reviews = $conn->prepare($sql_reviews);
    $stmt_reviews->bind_param("i", $user_id); // Binding the user's ID
    $stmt_reviews->execute();
    $result_reviews = $stmt_reviews->get_result();

    // Loop through all reviews and store them in the $reviews array
    while ($row = $result_reviews->fetch_assoc()) {
        $reviews[] = $row;
    }

    // Closing statements and connection
    $stmt_reviews->close();
    $stmt->close();

    // Step 1: Fetch attendance counts for this user
    $attendanceCountsQuery = "
        SELECT 
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS event_attended,
            SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS event_absent
        FROM attendance
        WHERE user_id = ?
    ";

    $attendanceCountsStmt = $conn->prepare($attendanceCountsQuery);
    $attendanceCountsStmt->bind_param("i", $user_id);
    $attendanceCountsStmt->execute();
    $attendanceCountsResult = $attendanceCountsStmt->get_result();

    // Initialize attended and absent counts
    $event_attended = 0;
    $event_absent = 0;

    if ($attendanceCountsResult->num_rows > 0) {
        $attendanceCounts = $attendanceCountsResult->fetch_assoc();
        $event_attended = $attendanceCounts['event_attended'];
        $event_absent = $attendanceCounts['event_absent'];
    }

    // Step 2: Count the total number of distinct events the user has joined
    $totalEventsQuery = "
        SELECT COUNT(DISTINCT event_id) AS total_events
        FROM attendance
        WHERE user_id = ?
    ";
    $totalEventsStmt = $conn->prepare($totalEventsQuery);
    $totalEventsStmt->bind_param("i", $user_id);
    $totalEventsStmt->execute();
    $totalEventsResult = $totalEventsStmt->get_result();

    // Initialize the total events count
    $total_events_joined = 0;

    if ($totalEventsResult->num_rows > 0) {
        $totalEventsCount = $totalEventsResult->fetch_assoc();
        $total_events_joined = $totalEventsCount['total_events'];
    }

    // Closing database connection
    $attendanceCountsStmt->close();
    $totalEventsStmt->close();
    $conn->close();
} else {
    echo "User ID is not specified.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $profile['name']; ?> - Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/find_event.css">
    <style>
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            padding-top: 8px;
            padding-left: 13px;
            padding-right: 13px;
        }

        .form-group p {
            flex: 2;
            padding-top: 8px;
            padding-left: 0px;

        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        #attendanceChart {
            width: 260px !important;
            height: 260px !important;
            display: block;
            margin: 0 auto;
        }

        .comment-item {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .comment-header h5 {
            font-size: 1.1rem;
        }

        .comment-body p {
            font-size: 0.95rem;
            color: #6c757d;
        }

        .comment-reply {
            padding-left: 1rem;
            border-left: 3px solid #007bff;
        }

        .reply-item h6 {
            font-size: 1rem;
        }

        .reply-item p {
            font-size: 0.95rem;
            color: #6c757d;
        }

        .reply-item small {
            color: #888;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <section style="padding-bottom: 30px; min-height:500px;">
        <div class="container py-4">
            <div class="row">
                <div class="col-12">
                    <div class="container d-flex align-items-center position-relative">
                        <p id="goBack" class="position-absolute go-back-link" style="left: 0;">
                            <i class="fas fa-arrow-left"></i> <!-- Font Awesome icon -->
                            Go back
                        </p>
                        <h3 class="mx-auto mr-3">User Profile</p>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group" style="display: inline-flex;">
                        <label for="date-joined">Date Joined:</label>
                        <p><?php echo $profile['date_joined']; ?></p>
                    </div>
                    <div class="form-group" style="display: inline-flex;">
                        <label for="event-created">Event Created:</label>
                        <p><?php echo $profile['event_created']; ?></p>
                    </div>
                    <div class="form-group" style="display: inline-flex;">
                        <label for="event-created">User Credit:</label>
                        <p title="Note: Credit score might be deducted (based on organizer decision) if you cancel an approved application. You will earn 2 points for each attendance.">
                            <?php echo $profile['credit'] . "/100"; ?>
                        </p>
                    </div>
                </div>

                <div class="user-detail">
                    <div class="user-image">
                        <img src="<?php echo $profile['profile_image']; ?>" alt="User Profile">
                    </div>
                    </br>
                    <div class="event-content">
                        <div class="icon" style="display: flex;">
                            <h4 class="event-title"></h4>

                        </div>
                        <?php
                        $dob = new DateTime($profile['dob']);
                        $now = new DateTime();
                        $age = $now->diff($dob)->y;
                        ?>
                        <p class="user-name"><?php echo $profile['name']; ?> </p>
                        <p class="user-age">Age: <?php echo $age ?></p>

                        <p class="user-email">Email: <?php echo $profile['email']; ?></p>
                        <p class="user-email">From: <?php echo $profile['city']; ?>, <?php echo $profile['state']; ?></p>
                        </br>
                        <p class="user-intro"><?php echo $profile['self_introduction']; ?></p>
                        </br>

                    </div>

                    <div class="event-donation text-center">
                        <p>Attendance rates as volunteer</p>
                        <p>Total Event Joined: <?php echo $total_events_joined; ?></p>
                        <p>Total Event Days: <?php echo $event_attended + $event_absent; ?></p>
                        <?php
                        if ($event_attended == 0 && $event_absent == 0) {
                            echo "<p>This user hasn't joined any events.</p>";
                        } else {

                            echo  "<canvas id='attendanceChart'></canvas>";
                        }
                        ?>
                    </div>
                </div>
            </div>


            <div class="skill-detail">
                <div class="container py-4">
                    <div class="skill-content">
                        <div class="skills-container">
                            <div class="skills-title">
                                <h4>Skills:</h4>
                            </div>
                            <div id="skillsList" class="skills-list"></div>
                        </div>
                    </div>
                    <div class="cert-content">
                        <div class="certs-container">
                            <div class="certs-title">
                                <h4>Certificates:</h4>
                            </div>
                            <div id="certsList" class="certs-list"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reviews Section -->
            <div class="container py-4">
                <div class="comments-container">
                    <div class="comments-title mb-4">
                        <h4>Feedback from Event Organizers</h4>
                    </div>
                    <div id="commentsList" class="comments-list">
                        <?php if (!empty($reviews)): ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="comment-item mb-4 p-4 border rounded shadow-sm">
                                    <div class="comment-header d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="mb-0 font-weight-bold text-dark">
                                            <?php echo htmlspecialchars($review['reviewer_name']); ?> on
                                            <span class="text-primary"><?php echo htmlspecialchars($review['title']); ?></span>
                                        </h5>
                                        <small class="text-muted"><?php echo date("F j, Y", strtotime($review['created_at'])); ?></small>
                                    </div>

                                    <div class="comment-body mb-3">
                                        <p class="mb-0 text-secondary"><?php echo htmlspecialchars($review['review_text']); ?></p>
                                    </div>

                                    <!-- Display reply if it exists -->
                                    <?php if ($review['replied_at']): ?>
                                        <div class="comment-reply mt-3 pl-4 border-left">
                                            <h6 class="text-info"><?php echo $profile['name']; ?> Reply:</h6>
                                            <p class="mb-0"><?php echo htmlspecialchars($review['replied_text']); ?></p>
                                            <small class="text-muted">
                                                Replied on: <?php echo date("F j, Y", strtotime($review['replied_at'])); ?>
                                                <?php if ($review['replied_updated_at']): ?>
                                                    (Last updated: <?php echo date("F j, Y", strtotime($review['replied_updated_at'])); ?>)
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No reviews available for this user.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>



        </div>
    </section>

    <?php include 'footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('attendanceChart');
            if (ctx) { // Ensure the element exists
                ctx = ctx.getContext('2d');
                var attendanceChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: ['Attended', 'Absent'],
                        datasets: [{
                            label: 'Attendance Rates',
                            data: [<?php echo $event_attended; ?>, <?php echo $event_absent; ?>],
                            backgroundColor: ['#4CAF50', '#FF5733'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        }
                    }
                });
            }
        });

        function displaySkills(skillsString) {
            const skillsList = document.getElementById('skillsList');
            const skills = skillsString ? skillsString.split(',').map(skill => skill.trim()) : [];

            if (skills.length > 0) {
                skills.forEach(skill => {
                    const badge = document.createElement('span');
                    badge.className = 'skill-badge';
                    badge.textContent = skill;
                    skillsList.appendChild(badge);
                });
            } else {
                const noSkills = document.createElement('p');
                noSkills.className = 'no-skills';
                noSkills.textContent = "The user hasn't set up their skills yet.";
                skillsList.appendChild(noSkills);
            }
        }

        function displayCertificates(certificates, userId) {
            const certsList = document.getElementById('certsList');

            if (certificates.length > 0) {
                certificates.forEach(cert => {
                    const certItem = document.createElement('li');
                    certItem.className = 'cert-item';

                    // Create the download/view link
                    const certLink = document.createElement('a');
                    certLink.href = 'uploads/' + userId + '/certificates/' + cert.file_name; // Construct path using userId and file_name
                    certLink.textContent = cert.file_name; // Display certificate file name
                    certLink.target = '_blank'; // Open PDF in a new tab
                    certLink.className = 'cert-link';

                    certItem.appendChild(certLink);
                    certsList.appendChild(certItem);
                });
            } else {
                const noCerts = document.createElement('p');
                noCerts.className = 'no-certs';
                noCerts.textContent = "The user hasn't added any certificates yet.";
                certsList.appendChild(noCerts);
            }
        }


        // Call the function with the PHP-generated skills data
        displaySkills('<?php echo addslashes($profile['skills']); ?>');
        displayCertificates(<?php echo json_encode($certificates); ?>, <?php echo ($profile['id']); ?>);

        document.getElementById('goBack').addEventListener('click', function() {
            window.history.back(); // Go back to the previous page
        });
    </script>

</body>

</html>