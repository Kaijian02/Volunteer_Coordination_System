<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
} else {
    $id = $_SESSION['user_id'];
    $email = $_SESSION['email'];
}

$conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id'");
$row = mysqli_fetch_assoc($user);

// Fetch comments (reviews)
$reviews = [];
$sql_reviews = "
    SELECT r.id, r.review_text, r.created_at, r.updated_at, r.replied_at, r.replied_text, r.replied_updated_at,
           u.name AS reviewer_name, e.title
    FROM reviews r
    JOIN users u ON r.organizer_id = u.id
    JOIN events e ON r.event_id = e.id
    WHERE r.volunteer_id = '$id'
";
$result_reviews = mysqli_query($conn, $sql_reviews);

while ($review_row = mysqli_fetch_assoc($result_reviews)) {
    $reviews[] = $review_row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="./css/notification.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        #upload-label {
            display: inline-block;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            margin-bottom: 0px;
        }

        #upload-label:hover {
            background-color: #0056b3;
        }

        #cropped-result {
            text-align: center;
            margin-top: 20px;
            width: 235px;
            height: 235px;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 10px solid #ddd;
            border-radius: 10px;
        }

        #cropped-result img {
            max-width: 100%;
            max-height: 100%;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0, 0, 0, 0.5);
        }

        .modal-backdrop {
            opacity: 0.5 !important;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 900px;
            margin-top: 10px;
        }

        .modal-content-password {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 450px;
            margin-top: 10px;
        }

        .skills-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        @media (max-width: 992px) {
            .skills-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .skills-grid {
                grid-template-columns: 1fr;
            }
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .img-container {
            height: 500px;
            /* Set the height to 500px */
            max-width: 100%;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .img-container img {
            max-height: 100%;
            max-width: 100%;
        }

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

        input[type="text"],
        select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }

        .form-group-n {
            display: flex;
        }

        .form-group-n label {
            padding-top: 8px;
            padding-left: 13px;
            padding-right: 13px;
            flex: 1;
        }

        .form-group-n input {
            flex: 2;
            /* Make input take up more space */
            padding-left: 5px;
            /* Padding inside the input field */
        }

        .form-group-n p {
            flex: 2;
            padding-top: 8px;
            padding-left: 0px;

        }

        .home-button {
            margin: 30px auto 0 auto;
            padding: 10px;
            background-color: #11998e;
            border: none;
            color: white;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }

        .home-button:hover {
            background-color: #38ef7d;
        }

        /* Style the edit button */
        #edit-self-introduction {
            border: none;
            background: white;
            margin-left: 8px;
            cursor: pointer;
        }

        /* Initially hide the save button */
        #save-self-introduction,
        #save-phone-number {
            display: none;
            border: none;
            background: white;
            margin-left: 8px;
            cursor: pointer;
        }

        /* Additional styling for textarea */
        #self-introduction[disabled] {
            background-color: #f9f9f9;
            cursor: not-allowed;
        }

        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            /* Semi-transparent background */
            display: none;
            /* Hidden by default */
            justify-content: center;
            /* Center horizontally */
            align-items: center;
            flex-direction: column;
            /* Center vertically */
            z-index: 9999;
            /* Ensure it appears above other content */
        }

        /* Spinner */
        .spinner {
            border: 8px solid rgba(0, 0, 0, 0.1);
            /* Light gray background */
            border-radius: 50%;
            border-top: 8px solid #3498db;
            /* White spinner */
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            /* Animation for spinner */
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
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

        .reply-form textarea {
            border-radius: 4px;

        }

        .btn-outline-primary {
            border-color: #007bff;
            color: #007bff;
        }

        .btn-outline-primary:hover {
            background-color: #007bff;
            color: #fff;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        #certificate-list li {
            list-style: none;
            margin-bottom: 10px;
        }

        #certificate-list a {
            color: #007bff;
            text-decoration: none;
        }

        #certificate-list a:hover {
            text-decoration: underline;
        }

        .no-certificates {
            color: #dc3545;
        }

        .form-control-file {
            border: 2px dashed #007bff;
            padding: 10px;
            border-radius: 5px;
            background-color: #f1f1f1;
        }

        #certificate-upload:hover {
            border-color: #28a745;
        }

        h3 {
            font-family: 'Arial', sans-serif;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }

        .skills-container {
            margin-top: 20px;
        }

        .skills-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .skill-badge {
            background-color: #e2e8f0;
            color: #4a5568;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            display: inline-block;
        }

        .no-skills {
            color: #718096;
            font-style: italic;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <div id="notificationContainer"></div>
    <div id="loading-overlay">
        <div class="spinner"></div>
        <p>Loading, please wait...</p>
    </div>
    <section>
        <div class="container py-4">
            <div class="row">
                <div class="col-12">
                    <h3>Personal Information</p>
                </div>
                <div class="col-12" style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: inline-flex; gap: 20px;">
                        <div class="form-group" style="display: inline-flex;">
                            <label for="date-joined">Date Joined:</label>
                            <p style="margin-left: 5px;"><?php echo $row['date_joined']; ?></p>
                        </div>
                        <div class="form-group" style="display: inline-flex;">
                            <label for="event-joined">Event Joined:</label>
                            <p style="margin-left: 5px;"><?php echo $row['event_joined']; ?></p>
                        </div>
                        <div class="form-group" style="display: inline-flex;">
                            <label for="event-created">Event Created:</label>
                            <p style="margin-left: 5px;"><?php echo $row['event_created']; ?></p>
                        </div>
                        <div class="form-group" style="display: inline-flex;">
                            <label for="event-created">User Credit:</label>
                            <p title="Note: Credit score might be deducted (based on organizer decision) if you cancel an approved application. You will earn 2 points for each attendance.">
                                <?php echo $row['credit'] . "/100"; ?>
                            </p>
                        </div>
                    </div>
                    <div>
                        <a href="view_profile.php?user_id=<?php echo $id; ?>">View My Profile</a>
                    </div>
                </div>
                <div class="col-lg-6 mb-4 mb-lg-0 d-flex flex-column justify-content-center align-items-center">
                    <div id="cropped-result">
                        <?php if (!empty($row['profile_image'])) : ?>
                            <img src="<?php echo $row['profile_image']; ?>" alt="Profile Image">
                        <?php else : ?>
                            <img src="img/upload-photo.jpg" alt="Default Image">
                        <?php endif; ?>
                    </div>
                    <!-- Button to change profile -->
                    <button id="change-profile-btn" class="btn btn-primary mt-3">Change Profile</button>
                    <input type="file" id="upload-photo" accept="image/*" style="display: none;">
                    <label id="upload-label" for="upload-photo" class="btn btn-primary mt-3" style="display: none;">Upload Photo</label>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="text" id="email" placeholder="<?php echo $_SESSION['email']; ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" placeholder="<?php echo $row['name']; ?>" disabled>
                    </div>
                    <form id="location-form" method="post" action="server/update_location.php">
                        <div class="form-group">
                            <div class="form-group-n">
                                <label for="state" style="padding-top: 8px; padding-left: 13px; padding-right: 13px;">Your Location</label>
                                <button id="edit-location" type="button" style="border: none; background: white;">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button id="save-location" type="button" style="display: none;">
                                    <i class="fas fa-save"></i>
                                </button>
                            </div>

                            <!-- State Dropdown -->
                            <select id="state" name="state" disabled>
                                <option value="<?php echo htmlspecialchars($row['state']); ?>">
                                    <?php echo htmlspecialchars($row['state']); ?>
                                </option>
                            </select>


                            <!-- City Dropdown -->
                            <select id="city" name="city" disabled>
                                <option value="<?php echo htmlspecialchars($row['city']); ?>">
                                    <?php echo htmlspecialchars($row['city']); ?>
                                </option>
                            </select>

                            <input type="hidden" name="user_id" value="<?php echo $id; ?>">
                        </div>
                    </form>

                    <div class="form-group">
                        <div class="form-group-n">

                            <!-- Button to trigger the modal -->
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                Change Password
                            </button>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Img Modal -->
        <div id="myModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div class="img-container">
                    <img id="image-preview" src="" alt="Image Preview">
                </div>
                <button id="crop-button">Crop & Save</button>
            </div>
        </div>

        <!-- Bootstrap Change Password Modal -->
        <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changePasswordLabel">Change Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="change-password-form" method="post" action="server/change_password.php">
                            <div class="form-group mb-3">
                                <label for="old-password">Old Password</label>
                                <input type="password" name="old-password" id="old-password" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="new-password">New Password</label>
                                <input type="password" name="new-password" id="new-password" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="confirm-password">Confirm Password</label>
                                <input type="password" name="confirm-password" id="confirm-password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-danger">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <div class="container py-4">
            <div class="row">
                <div class="col-12">
                    <h3>About</p>
                </div>
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <form id="self-introduction-form" method="post" action="server/update_self_introduction.php">
                        <div class="form-group">
                            <div class="form-group-n">
                                <label for="self-introduction">Self-introduction</label>
                                <button id="edit-self-introduction" type="button" style="border: none; background: white;">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button id="save-self-introduction" type="submit">
                                    <i class="fas fa-save"></i>
                                </button>
                            </div>
                            <textarea id="self-introduction" name="self-introduction" class="form-control" style="resize: none;" rows="5" cols="40" placeholder="Enter your self-introduction here" disabled><?php echo $row['self_introduction']; ?></textarea>
                            <input type="hidden" name="user_id" value="<?php echo $id; ?>">
                        </div>
                    </form>
                </div>
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="form-group">
                        <div class="form-group-n">
                            <label for="skills">Skills</label>
                            <button id="edit-skills" type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#skillsModal">

                                <i class="fas fa-edit"></i>
                            </button>

                        </div>
                        <div class="skills-container">
                            <div id="selected-skills" class="skills-list"></div>
                        </div>
                    </div>
                </div>

                <!-- Certificates Upload Section -->
                <div class="form-group">
                    <label for="certificate-upload">Upload Relevant Certificates (PDF only)</label>
                    <form id="certificate-upload-form" enctype="multipart/form-data">
                        <input type="file" id="certificate-upload" name="certificate" accept="application/pdf" class="form-control-file">
                        <input type="hidden" name="user_id" value="<?php echo $id; ?>">
                        <button type="submit" class="btn btn-primary mt-2">Upload</button>
                    </form>
                </div>

                <!-- Display Uploaded Certificates -->
                <div class="form-group mt-3">
                    <h5>Your Certificates</h5>
                    <ul id="certificate-list">
                        <?php
                        // Fetch and display uploaded certificates
                        $cert_query = "SELECT * FROM user_certificates WHERE user_id = ?";
                        $stmt = $conn->prepare($cert_query);
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            while ($cert = $result->fetch_assoc()) {
                                echo '<div class="form-group-n">';
                                echo '<li><a href="uploads/' . $id . '/certificates/' . htmlspecialchars($cert['file_name']) . '" target="_blank">' . htmlspecialchars($cert['file_name']) . '</a></li>';
                                echo ' <button class="delete-certificate" data-cert-id="' . $cert['id'] . '" style="border: none; background: white; padding-bottom:5px;">  <i class="fas fa-remove"></i></button>';
                                echo '</div>';
                            }
                        } else {
                            echo '<li>No certificates uploaded yet.</li>';
                        }
                        ?>
                    </ul>
                </div>

            </div>
        </div>


        <!-- Reviews Section -->
        <div class="comment-detail">
            <div class="container py-4">
                <div class="comments-container">
                    <div class="comments-title mb-4">
                        <h3>Review from Event Organizers</h3>
                    </div>
                    <div id="commentsList" class="comments-list">
                        <?php if (empty($reviews)): ?>
                            <!-- No reviews message -->
                            <div class="no-reviews p-4">
                                <p class="text-muted">No reviews available for you yet.</p>
                            </div>
                        <?php else: ?>
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


                                    <!-- Reply Section -->
                                    <div class="comment-reply">
                                        <?php if ($review['replied_at']): ?>
                                            <div class="reply-item mt-3 pl-4 border-left">
                                                <h6 class="text-info">Your Reply:</h6>
                                                <p class="mb-0"><?php echo htmlspecialchars($review['replied_text']); ?></p>
                                                <small class="text-muted">
                                                    Replied on: <?php echo date("F j, Y", strtotime($review['replied_at'])); ?>
                                                    <?php if ($review['replied_updated_at']): ?>
                                                        (Last updated: <?php echo date("F j, Y", strtotime($review['replied_updated_at'])); ?>)
                                                    <?php endif; ?>
                                                </small>
                                                <button class="btn btn-sm btn-outline-primary mt-2 edit-reply-button" data-review-id="<?php echo $review['id']; ?>">Edit Reply</button>
                                                <div class="reply-form mt-2" id="reply-form-<?php echo $review['id']; ?>" style="display:none;">
                                                    <textarea placeholder="Edit your reply..." rows="3" class="form-control mb-2" id="reply-text-<?php echo $review['id']; ?>"><?php echo htmlspecialchars($review['replied_text']); ?></textarea>
                                                    <button class="btn btn-sm btn-primary submit-reply" data-review-id="<?php echo $review['id']; ?>">Submit Edit</button>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-primary mt-3 reply-button" data-review-id="<?php echo $review['id']; ?>">Reply</button>
                                            <div class="reply-form mt-2" id="reply-form-<?php echo $review['id']; ?>" style="display:none;">
                                                <textarea placeholder="Write your reply..." rows="3" class="form-control mb-2" id="reply-text-<?php echo $review['id']; ?>"></textarea>
                                                <button class="btn btn-sm btn-primary submit-reply" data-review-id="<?php echo $review['id']; ?>">Submit Reply</button>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="skillsModal" class="modal fade" tabindex="-1" aria-labelledby="skillsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="skillsModalLabel">Select Skills</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="skills-form">
                            <div class="skills-grid">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill1" name="skills" value="Tutoring and Mentoring Programs">
                                    <label class="form-check-label" for="skill1">Tutoring and Mentoring Programs</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill2" name="skills" value="Adult Education Classes">
                                    <label class="form-check-label" for="skill2">Adult Education Classes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill3" name="skills" value="School Supplies Drives">
                                    <label class="form-check-label" for="skill3">School Supplies Drives</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill4" name="skills" value="Health Screenings">
                                    <label class="form-check-label" for="skill4">Health Screenings</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill5" name="skills" value="Health Education Workshops">
                                    <label class="form-check-label" for="skill5">Health Education Workshops</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill6" name="skills" value="Fitness and Wellness Programs">
                                    <label class="form-check-label" for="skill6">Fitness and Wellness Programs</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill7" name="skills" value="Community Clean-ups">
                                    <label class="form-check-label" for="skill7">Community Clean-ups</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill8" name="skills" value="Tree Planting and Gardening">
                                    <label class="form-check-label" for="skill8">Tree Planting and Gardening</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill9" name="skills" value="Recycling Drives">
                                    <label class="form-check-label" for="skill9">Recycling Drives</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill10" name="skills" value="Food Drives and Pantries">
                                    <label class="form-check-label" for="skill10">Food Drives and Pantries</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill11" name="skills" value="Clothing Drives">
                                    <label class="form-check-label" for="skill11">Clothing Drives</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill12" name="skills" value="Housing Assistance">
                                    <label class="form-check-label" for="skill12">Housing Assistance</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill13" name="skills" value="Cultural Festivals and Events">
                                    <label class="form-check-label" for="skill13">Cultural Festivals and Events</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill14" name="skills" value="Recreational Programs">
                                    <label class="form-check-label" for="skill14">Recreational Programs</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill15" name="skills" value="Public Libraries and Community Centers">
                                    <label class="form-check-label" for="skill15">Public Libraries and Community Centers</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill16" name="skills" value="Legal Aid Clinics">
                                    <label class="form-check-label" for="skill16">Legal Aid Clinics</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill17" name="skills" value="Advocacy Campaigns">
                                    <label class="form-check-label" for="skill17">Advocacy Campaigns</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill18" name="skills" value="Support Groups">
                                    <label class="form-check-label" for="skill18">Support Groups</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill19" name="skills" value="Job Fairs and Career Counseling">
                                    <label class="form-check-label" for="skill19">Job Fairs and Career Counseling</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skill20" name="skills" value="Small Business Support">
                                    <label class="form-check-label" for="skill20">Small Business Support</label>
                                </div>
                                <!-- Add other skills -->
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Save</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </section>


    <?php include 'footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script>
        document.querySelectorAll('.reply-button').forEach(button => {
            button.addEventListener('click', function() {
                const reviewId = this.getAttribute('data-review-id');
                const replyForm = document.getElementById(`reply-form-${reviewId}`);
                replyForm.style.display = 'block'; // Show the reply form
                document.getElementById(`reply-text-${reviewId}`).value = ''; // Clear textarea for new replies
            });
        });

        document.querySelectorAll('.edit-reply-button').forEach(button => {
            button.addEventListener('click', function() {
                const reviewId = this.getAttribute('data-review-id');

                // Check if the reply item exists before trying to get its text
                const replyItem = document.querySelector(`#reply-form-${reviewId}`).parentElement.querySelector('.reply-item p');
                let existingReplyText = '';

                if (replyItem) {
                    existingReplyText = replyItem.textContent.trim();
                    // console.log('Existing reply text:', existingReplyText);
                }

                // Show the reply form and pre-fill it with the existing reply text
                const replyForm = document.getElementById(`reply-form-${reviewId}`);
                replyForm.style.display = 'block'; // Show the reply form
                document.getElementById(`reply-text-${reviewId}`).value = existingReplyText; // Pre-fill the reply form
            });
        });


        document.addEventListener('DOMContentLoaded', () => {
            fetch('server/fetch_skills.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Pre-check the skills in the modal based on the data from the server
                        document.querySelectorAll('input[name="skills"]').forEach((checkbox) => {
                            if (data.skills.includes(checkbox.value)) {
                                checkbox.checked = true;
                            }
                        });
                        // Update selected skills display
                        displaySkills(data.skills);
                    } else {
                        console.error(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));

            const uploadPhoto = document.getElementById('upload-photo');
            const imagePreview = document.getElementById('image-preview');
            const modal = document.getElementById('myModal'); // Img modal
            const closeModal = document.querySelector('.close'); //upload photo
            const cropButton = document.getElementById('crop-button');
            const croppedResult = document.getElementById('cropped-result');
            const selectedSkillsDiv = document.getElementById('selected-skills');
            let cropper;

            // Change password
            document.getElementById('change-password-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const oldPassword = document.getElementById('old-password').value;
                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;

                // Regex pattern: At least 6 characters, one letter and one symbol
                const passwordPattern = /^(?=.*[A-Za-z])(?=.*[\W]).{6,}$/;

                if (newPassword !== confirmPassword) {
                    showNotification('New Password and Confirm Password do not match!', 'error');
                    return;
                }
                if (newPassword === oldPassword) {
                    showNotification('New Password should not be the same as the Old Password!', 'error');
                    return;
                }
                if (!passwordPattern.test(newPassword)) {
                    showNotification('Password must be at least 6 characters long, contain at least one letter, and one special character.', 'error');
                    return;
                }
                const formData = new FormData(this);
                fetch('server/change_password.php', {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showNotification(data.message, 'success');
                            const passwordModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
                            passwordModal.hide();
                            setTimeout(() => {
                                window.location.href = 'server/logout.php'; // Redirect to logout
                            }, 3000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });



            // Upload photo
            uploadPhoto.addEventListener('change', (e) => {
                const files = e.target.files;
                if (files && files.length > 0) {
                    const file = files[0];
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        imagePreview.src = event.target.result;
                        modal.style.display = 'block';
                        if (cropper) {
                            cropper.destroy();
                        }
                        cropper = new Cropper(imagePreview, {
                            aspectRatio: 1,
                            viewMode: 1,
                            minCropBoxWidth: 235,
                            minCropBoxHeight: 235,
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });

            closeModal.addEventListener('click', () => {
                modal.style.display = 'none';
                if (cropper) {
                    cropper.destroy();
                }
                imagePreview.src = '';
            });

            window.addEventListener('click', (e) => {
                if (e.target == modal) {
                    modal.style.display = 'none';
                    if (cropper) {
                        cropper.destroy();
                    }
                    imagePreview.src = '';
                }
            });

            cropButton.addEventListener('click', () => {
                if (cropper) {
                    const canvas = cropper.getCroppedCanvas({
                        width: 235,
                        height: 235
                    });
                    canvas.toBlob((blob) => {
                        const file = new File([blob], 'croppedImage.jpg', {
                            type: 'image/jpeg'
                        });
                        const url = URL.createObjectURL(blob);
                        croppedResult.innerHTML = `<img src="${url}" alt="Cropped Image">`;
                        modal.style.display = 'none';

                        const formData = new FormData();
                        formData.append('croppedImage', file);

                        fetch('upload_img.php', {
                                method: 'POST',
                                body: formData,
                            })
                            .then(response => response.text())
                            .then(data => {
                                // console.log('Success:', data);
                                showNotification('Profile image updated successfully.', 'success');
                            })
                            .catch(error => {
                                console.error('Error:', error);
                            });
                    }, 'image/jpeg');
                }
            });


            document.getElementById('skills-form').addEventListener('submit', (e) => {
                e.preventDefault();
                const selectedSkills = Array.from(document.querySelectorAll('input[name="skills"]:checked')).map(checkbox => checkbox.value);
                const formData = new FormData();
                formData.append('skills', selectedSkills.join(','));

                fetch('server/save_skills.php', {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            displaySkills(selectedSkills);
                            showNotification(data.message, 'success')
                            // Close the modal and clean up
                            const modal = document.getElementById('skillsModal');
                            if (modal) {
                                const modalInstance = bootstrap.Modal.getInstance(modal);
                                modalInstance.hide();

                                // Remove the modal backdrop and restore scrolling
                                setTimeout(() => {
                                    const backdrops = document.querySelectorAll('.modal-backdrop');
                                    backdrops.forEach(backdrop => backdrop.remove());
                                    document.body.classList.remove('modal-open');
                                    document.body.style.removeProperty('padding-right');
                                    document.body.style.overflow = 'auto';
                                    document.documentElement.style.overflow = 'auto';
                                }, 300); // Wait for modal hide transition to complete
                            }

                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));

            });


            //Change Profile
            const changeProfileBtn = document.getElementById('change-profile-btn');
            const uploadPhotoInput = document.getElementById('upload-photo');
            const uploadLabel = document.getElementById('upload-label');


            // Show the file input and hide the button when the button is clicked
            changeProfileBtn.addEventListener('click', () => {
                changeProfileBtn.style.display = 'none'; // Hide the button
                // uploadPhotoInput.style.display = 'block'; // Show the file input
                uploadLabel.style.display = 'inline-block';
            });


            //Self-introduction
            const editButton = document.getElementById('edit-self-introduction');
            const saveButton = document.getElementById('save-self-introduction');
            const selfIntroductionTextarea = document.getElementById('self-introduction');
            const selfIntroductionForm = document.getElementById('self-introduction-form');

            // Enable the textarea when the edit button is clicked
            editButton.addEventListener('click', () => {
                selfIntroductionTextarea.disabled = false; // Enable textarea
                selfIntroductionTextarea.focus(); // Focus on the textarea
            });

            // Show the save button when the user starts typing
            selfIntroductionTextarea.addEventListener('input', () => {
                if (selfIntroductionTextarea.value.trim() !== '') {
                    saveButton.style.display = 'inline-block'; // Show the save button
                } else {
                    saveButton.style.display = 'none'; // Hide the save button if the textarea is empty
                }
            });

            // Save the input and disable the textarea when the save button is clicked
            saveButton.addEventListener('click', (e) => {
                e.preventDefault();
                selfIntroductionTextarea.disabled = false;
                saveButton.style.display = 'none';
                // Manually create FormData object and log it for debugging
                const formData = new FormData(selfIntroductionForm);
                // for (let [key, value] of formData.entries()) {
                //     console.log(`${key}: ${value}`);
                // }
                fetch(selfIntroductionForm.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.status === 'success') {
                            showNotification(result.message, "success");
                            selfIntroductionTextarea.disabled = true;
                            saveButton.style.display = 'none';
                        } else {
                            showNotification(result.message, "error");
                        }
                    })
                    .catch(error => {
                        console.error('Form submission error:', error);
                    });
            });

            // Function to show notification with a dynamic message
            function showNotification(message, type, id = null) {
                const notificationContainer = document.getElementById('notificationContainer');

                // Create a new notification box element
                const notificationBox = document.createElement('div');
                notificationBox.classList.add('notification-box', 'show'); // Initially show

                // Give each notification a unique ID, so we can remove it easily
                const notificationId = id ? id : new Date().getTime();
                notificationBox.setAttribute('id', 'notification-' + notificationId);

                // Apply type-specific classes
                if (type === 'success') {
                    notificationBox.style.backgroundColor = '#28a745'; // Green for success
                } else if (type === 'error') {
                    notificationBox.style.backgroundColor = '#dc3545'; // Red for error
                } else if (type === 'info') {
                    notificationBox.style.backgroundColor = '#17a2b8'; // Blue for info
                }

                notificationBox.innerText = message;

                // Append the new notification at the top
                notificationContainer.prepend(notificationBox);

                // Fade in
                setTimeout(() => {
                    notificationBox.classList.add('show'); // Ensure it fades in
                }, 10); // Small timeout to trigger the transition

                // Set it to auto-hide after a delay (e.g., 3 seconds)
                setTimeout(() => {
                    hideNotification(notificationId); // Hide after delay
                }, 3000); // Change delay as needed
            }

            // Function to hide a specific notification
            function hideNotification(notificationId) {
                const notificationBox = document.getElementById('notification-' + notificationId);
                if (notificationBox) { // Check if the notification box exists
                    notificationBox.classList.remove('show');
                    notificationBox.classList.add('hide');
                    setTimeout(() => {
                        if (notificationBox && notificationBox.parentNode) {
                            notificationBox.parentNode.removeChild(notificationBox); // Safely remove the notification
                        }
                    }, 500); // Adjust this duration based on the CSS transition time
                }
            }

            // Edit location
            // Store all cities data
            let allCities = {};
            let allStates = [];
            let stateCodeMap = {};
            // Show loading indicator
            // document.getElementById('loading-overlay').style.display = 'flex';

            // Fetch all states and cities on page load
            fetchStatesAndCities();
            // Handle edit button click
            document.getElementById('edit-location').addEventListener('click', function() {
                const stateDropdown = document.getElementById('state');
                const cityDropdown = document.getElementById('city');
                const saveButton = document.getElementById('save-location');

                if (stateDropdown.disabled) {
                    // Enter edit mode
                    stateDropdown.disabled = false;
                    cityDropdown.disabled = false;
                    saveButton.style.display = 'inline'; // Show save button
                    toggleSaveButton(); // Check the state of the button initially

                    // Reset the dropdowns to default values
                    stateDropdown.value = ''; // Set state dropdown to default
                    cityDropdown.innerHTML = '<option value="">Select a city</option>'; // Reset city dropdown options
                    saveButton.disabled = true;
                } else {
                    // Exit edit mode and revert to original values
                    const userState = '<?php echo $row["state"]; ?>';
                    const userCity = '<?php echo $row["city"]; ?>';

                    stateDropdown.value = userState || '';
                    displayCitiesForState(userState); // Display cities for the selected state
                    cityDropdown.value = userCity || '';
                    stateDropdown.disabled = true;
                    cityDropdown.disabled = true;
                    saveButton.style.display = 'none';

                    // Enable the save button if state and city are selected
                    toggleSaveButton();
                }
            });

            document.getElementById('save-location').addEventListener('click', function() {
                const form = document.getElementById('location-form');
                const formData = new FormData(form);
                fetch(form.action, {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // alert(data.message);
                            showNotification(data.message, "success");
                            document.getElementById('state').disabled = true;
                            document.getElementById('city').disabled = true;
                            document.getElementById('save-location').style.display = 'none';
                        } else {
                            alert(data.message); // Show error message
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the location. Please try again.');
                    });
            });

            // Fetch cities when a state is selected
            document.getElementById('state').addEventListener('change', function() {
                const stateName = this.value;
                if (stateName) {
                    displayCitiesForState(stateName); // Display cities from preloaded data
                } else {
                    document.getElementById('city').innerHTML = '<option value="">Select a city</option>'; // Reset city dropdown if no state is selected
                    toggleSaveButton(); // Check if save button should be enabled
                }
            });

            // Handle city selection
            document.getElementById('city').addEventListener('change', function() {
                toggleSaveButton(); // Enable/Disable save button
            });

            // Function to enable/disable save button
            function toggleSaveButton() {
                const state = document.getElementById('state').value;
                const city = document.getElementById('city').value;
                const saveButton = document.getElementById('save-location');

                if (state && city) {
                    saveButton.disabled = false; // Enable button if both state and city are selected
                } else {
                    saveButton.disabled = true; // Disable button if any of the fields is not selected
                }
            }

            // Fetch all states and cities at once
            function fetchStatesAndCities() {
                const username = 'lawkaijian';
                const urlStates = `https://secure.geonames.org/childrenJSON?geonameId=1733045&username=${username}`; // Malaysia's GeoName ID

                fetch(urlStates)
                    .then(response => response.json())
                    .then(data => {
                        const states = data.geonames || [];
                        const stateDropdown = document.getElementById('state');
                        stateDropdown.innerHTML = '<option value="">Select a state</option>'; // Clear existing options

                        states.forEach(state => {
                            const option = document.createElement('option');
                            option.value = state.adminName1;
                            option.textContent = state.adminName1;
                            stateDropdown.appendChild(option);

                            // Store state code in the map
                            stateCodeMap[state.adminName1] = state.adminCode1;

                            // Fetch cities for each state and store them
                            fetchCitiesForState(state.adminName1);
                        });

                        // Set the user's current state as selected
                        const userState = '<?php echo htmlspecialchars($row["state"]); ?>';
                        if (userState) {
                            stateDropdown.value = userState;
                            displayCitiesForState(userState); // Display cities for the selected state
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching states:', error);
                        document.getElementById('loading-overlay').textContent = 'Failed to load states. Please try again.';
                    });
            }

            // Fetch cities for a given state and store them in allCities object
            function fetchCitiesForState(stateName) {
                const username = 'lawkaijian'; // Replace with your GeoNames username
                const stateCode = stateCodeMap[stateName]; // Get state code from the map
                const url = `https://secure.geonames.org/searchJSON?country=MY&adminCode1=${stateCode}&featureClass=P&maxRows=1000&username=${username}`;
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        const cities = data.geonames || [];
                        allCities[stateName] = cities; // Store cities for the state
                        // Hide the loading indicator after data is loaded
                        if (Object.keys(allCities).length === allStates.length) {
                            document.getElementById('loading-overlay').style.display = 'none';
                        }
                        const userCity = '<?php echo $row["city"]; ?>';
                        if (stateName === '<?php echo $row["state"]; ?>' && userCity) {
                            // Hide the loading indicator after data is loaded
                            document.getElementById('loading-overlay').style.display = 'none';
                            displayCitiesForState(stateName);
                        }
                    })
                    .catch(error => console.error('Error fetching cities:', error));
            }

            // Display cities for the selected state
            function displayCitiesForState(stateName) {
                const cityDropdown = document.getElementById('city');
                cityDropdown.innerHTML = '<option value="">Select a city</option>'; // Clear existing options

                const cities = allCities[stateName] || [];
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.name;
                    option.textContent = city.name;
                    cityDropdown.appendChild(option);
                });

                // Set the user's current city as selected
                const userCity = '<?php echo $row["city"]; ?>';
                if (userCity && stateName === '<?php echo $row["state"]; ?>') {
                    cityDropdown.value = userCity;
                }

                toggleSaveButton(); // Enable the save button after displaying cities
            }

            function displaySkills(skills) {
                const selectedSkillsDiv = document.getElementById('selected-skills');
                selectedSkillsDiv.innerHTML = '';

                if (skills.length > 0) {
                    skills.forEach(skill => {
                        const badge = document.createElement('span');
                        badge.className = 'skill-badge';
                        badge.textContent = skill;
                        selectedSkillsDiv.appendChild(badge);
                    });
                } else {
                    const noSkills = document.createElement('p');
                    noSkills.className = 'no-skills';
                    noSkills.textContent = "No skills added yet.";
                    selectedSkillsDiv.appendChild(noSkills);
                }
            }

            // Add event listeners to delete buttons
            document.querySelectorAll('.delete-certificate').forEach(button => {
                button.addEventListener('click', function() {
                    const certId = this.getAttribute('data-cert-id');
                    if (confirm('Are you sure you want to delete this certificate?')) {
                        fetch('server/delete_certificate.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: new URLSearchParams({
                                    cert_id: certId
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    // Remove the certificate from the UI
                                    this.parentElement.remove(); // Remove the <li> element
                                    showNotification(data.message, 'success');
                                } else {
                                    showNotification(data.message, 'error');
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    }
                });
            });

            document.getElementById('certificate-upload-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const fileInput = document.getElementById('certificate-upload');
                const file = fileInput.files[0]; // Get the selected file
                const userId = document.querySelector('input[name="user_id"]').value;
                if (!file) {
                    showNotification('Please select a certificate to upload.', 'error');
                    return;
                }
                const formData = new FormData();
                formData.append('certificate', file);
                formData.append('user_id', userId);
                fetch('server/upload_certificate.php', {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showNotification(data.message, 'success');
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred while uploading the certificate.', 'error');
                    });
            });


            document.querySelectorAll('.submit-reply').forEach(button => {
                button.addEventListener('click', function() {
                    const reviewId = this.getAttribute('data-review-id');
                    const replyText = document.getElementById(`reply-text-${reviewId}`).value;

                    if (replyText.trim() === '') {
                        showNotification('Please enter a reply message', 'error');
                        return;
                    }

                    const replyData = {
                        reviewId: reviewId,
                        replyText: replyText
                    };

                    fetch('server/submit_reply.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(replyData)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // showNotification('Reply submitted successfully', 'success');
                                location.reload(); // Reload to display the new reply or edited reply
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('There was an error submitting the reply. Please try again.');
                        });

                    // Clear the textarea and hide the reply form after submission
                    document.getElementById(`reply-text-${reviewId}`).value = '';
                    document.getElementById(`reply-form-${reviewId}`).style.display = 'none';
                });
            });

        });
    </script>
</body>

</html>