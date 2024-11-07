<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['event_id'])) {
    $eventId = (int)$_GET['event_id'];

    // Database connection
    $conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare the SQL statement to fetch event details along with organizer name
    $sql = "SELECT events.*, users.name AS organizer_name 
            FROM events
            JOIN users ON events.user_id = user_id 
            WHERE events.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $eventId);

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $event = $result->fetch_assoc();
    } else {
        echo "Event not found.";
        $stmt->close();
        $conn->close();
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Event ID is not specified.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($event['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../css/find_event.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
    </style>
</head>

<body>
    <div id="notificationContainer"></div>

    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="container d-flex align-items-center position-relative">
                    <p id="goBack" class="position-absolute go-back-link" style="left: 0;">
                        <i class="fas fa-arrow-left"></i>
                        Go back
                    </p>
                    <h3 class="mx-auto mr-3">Event Details</h3>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <section>
        <div class="event-detail">
            <div class="event-poster">
                <img src="../<?php echo ($event['event_poster']); ?>" alt="Event Poster">
            </div>
            </br>
            <div class="event-content">
                <div class="icon" style="display: flex;">
                    <h4 class="event-title"><?php echo ($event['title']); ?></h4>
                </div>
                <p class="event-organizer">Organizer: <?php echo ($event['organizer_name']); ?></p>
                <p class="event-dates">Date: <?php echo ($event['start_date']); ?>
                    <?php if (!empty($event['end_date'])): ?>
                        - <?php echo ($event['end_date']); ?> </p>
            <?php endif; ?>

            <p class="event-venue">Venue: <?php echo ($event['venue']); ?></p>
            <p class="event-description"><?php echo ($event['description']); ?></p>
            </br>
            <p class="event-meta">Location: <?php echo ($event['city']); ?></p>
            <p class="event-meta">Date Posted: <?php echo ($event['date_created']); ?></p>



            </div>
            <?php if ($event['donation'] === 'Yes'): ?>
                <div class="event-donation">
                    <p id="raised-amount-<?php echo $event['id']; ?>">Raised: RM <?php echo number_format($event['raised'], 2); ?></p>
                    <div class="progress-bar-container">
                        <div id="progress-bar-<?php echo $event['id']; ?>" class="progress-bar" style="width: <?php echo min(100, ($event['raised'] / $event['goal']) * 100); ?>%;"></div>
                    </div>
                    <p id="goal-amount-<?php echo $event['id']; ?>" style="margin-top: 1rem;">Goal: RM <?php echo number_format($event['goal'], 2); ?></p>
                    <p id="goal-message-<?php echo $event['id']; ?>" class="text-success mt-3"></p>

                    <?php if ($event['raised'] >= $event['goal']): ?>
                        <p class="text-success mt-3">Goal reached! Thank you for your support.</p>
                    <?php else: ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Modal -->
    <div class="modal fade" id="donateModal-<?php echo $event['id']; ?>" tabindex="-1" aria-labelledby="donateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="donateModalLabel">Donate to "<?php echo htmlspecialchars($event['title']); ?>"</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="donationForm-<?php echo $event['id']; ?>" method="POST" action="server/process_donation.php">
                        <!-- Preset Buttons for Quick Amount Selection -->
                        <div class="mb-3">
                            <label for="presetAmounts" class="form-label">Select Donation Amount:</label>
                            <div class="preset-amounts">
                                <button type="button" class="btn btn-outline-primary preset-amount" data-amount="50">RM 50</button>
                                <button type="button" class="btn btn-outline-primary preset-amount" data-amount="200">RM 200</button>
                                <button type="button" class="btn btn-outline-primary preset-amount" data-amount="400">RM 400</button>
                                <button type="button" class="btn btn-outline-primary preset-amount" data-amount="600">RM 600</button>
                                <button type="button" class="btn btn-outline-primary preset-amount" data-amount="800">RM 800</button>
                                <button type="button" class="btn btn-outline-primary preset-amount" data-amount="1000">RM 1000</button>
                            </div>
                        </div>

                        <!-- Input field for manually entering donation amount -->
                        <div class="mb-3">
                            <label for="donationAmount" class="form-label">Or Enter Donation Amount (RM)</label>
                            <input type="number" class="form-control" id="donationAmount-<?php echo $event['id']; ?>" name="donation_amount" required min="1">
                        </div>

                        <!-- Hidden fields for event details -->
                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger">Submit Donation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <section>
        <div class="event-detail">
            <div class="container py-4">
                <div class="event-content">
                    <div class="skills-container">
                        <div class="skills-title">Skills:</div>
                        <div id="skillsList" class="skills-list">
                        </div>
                        </br>
                        <?php if (!empty($event['comments'])): ?>
                            <div class="skills-title">
                                <p class="event-skills">Additional Requirements:</p>
                            </div>
                            <?php echo htmlspecialchars($event['comments']); ?>
                        <?php else: ?>
                            <div class="skills-title">
                                <p>Additional Requirements:</p>
                            </div>
                            None
                        <?php endif; ?>
                    </div>
                    <div class="event-map">
                    </div>
                </div>
            </div>
    </section>
    <script>
        document.getElementById('goBack').addEventListener('click', function() {
            window.history.back(); // Go back to the previous page
        });

        document.querySelectorAll('.donate-button').forEach(button => {
            button.addEventListener('click', function() {
                const modalId = this.getAttribute('data-target');
                const modal = document.querySelector(modalId);
            });
        });

        document.querySelectorAll('.preset-amount').forEach(button => {
            button.addEventListener('click', function() {
                // Get the selected amount from the data-amount attribute
                const selectedAmount = this.getAttribute('data-amount');
                // Find the corresponding donation input field (based on event ID)
                const donationInput = this.closest('.modal-body').querySelector('[id^="donationAmount"]');
                // Set the value of the donation amount input field
                donationInput.value = selectedAmount;
            });
        });

        document.querySelectorAll('[id^="donationForm-"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                const formData = new FormData(this); // This will include all fields in the form

                fetch('server/process_donation.php', {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showNotification(data.message, 'success'); // Show success notification
                            updateProgressBar(<?php echo $event['id']; ?>);


                            // Properly close the modal and remove the backdrop
                            const modal = this.closest('.modal');
                            if (modal) {
                                const modalInstance = bootstrap.Modal.getInstance(modal);
                                modalInstance.hide();

                                // Remove the modal backdrop and restore scrolling
                                setTimeout(() => {
                                    const backdrop = document.querySelector('.modal-backdrop');
                                    if (backdrop) {
                                        backdrop.remove();
                                    }
                                    document.body.classList.remove('modal-open');
                                    document.body.style.removeProperty('padding-right');
                                    document.body.style.removeProperty('overflow');

                                    // Re-enable scrolling
                                    document.body.style.overflow = 'auto';
                                    document.documentElement.style.overflow = 'auto';
                                }, 300); // Wait for modal hide transition to complete
                            }
                        } else {
                            showNotification(data.message, 'error'); // Show error notification
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.', 'error'); // Show error notification
                    });
            });
        });

        // Function to ensure scrolling is restored when modal is closed manually
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('hidden.bs.modal', function(event) {
                document.body.style.overflow = 'auto';
                document.documentElement.style.overflow = 'auto';
                document.body.style.removeProperty('padding-right');
            });
        });


        document.getElementById('applyEventButton').addEventListener('click', function() {
            const userId = <?php echo json_encode($_SESSION['user_id']); ?>;
            const eventId = <?php echo json_encode($eventId); ?>;
            const organizerId = <?php echo json_encode($event['user_id']); ?>;

            if (userId === organizerId) {
                alert('You cannot apply for your own event.');
                return; // Prevent further action
            }
            // Show a confirmation dialog
            if (confirm('Do you want to apply for this event?')) {
                // Replace these with the actual user ID and event ID

                // Create the payload for the request
                const requestData = {
                    user_id: userId,
                    event_id: eventId
                };

                // Send a POST request to the server
                fetch('server/apply_event.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(requestData) // Convert the data to JSON string
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json(); // Assuming the server returns JSON
                    })
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error during fetch operation:', error);
                        alert('An error occurred while applying for the event.');
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
                noSkills.textContent = "None required.";
                skillsList.appendChild(noSkills);
            }
        }

        // Call the function with the PHP-generated skills data
        displaySkills('<?php echo addslashes($event['skills']); ?>');

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

        function updateProgressBar(eventId) {
            fetch(`server/fetch_donation_data.php?event_id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const raised = data.total || 0; // Use the total from the response
                        const goal = data.goal || 0; // Use the goal from the response

                        // Update the displayed raised amount
                        document.querySelector(`#raised-amount-${eventId}`).innerText = `Raised: RM ${raised.toFixed(2)}`;
                        document.querySelector(`#goal-amount-${eventId}`).innerText = `Goal: RM ${goal.toFixed(2)}`;

                        // Update the progress bar
                        const progressBar = document.querySelector(`#progress-bar-${eventId}`);
                        const progressPercentage = Math.min(100, (raised / goal) * 100);
                        progressBar.style.width = `${progressPercentage}%`;

                        // Check if the goal is reached
                        if (raised >= goal) {
                            document.querySelector(`#goal-message-${eventId}`).innerText = "Goal reached! Thank you for your support.";
                            // Hide the donate button
                            const donateButton = document.querySelector(`#donate-button-${eventId}`);
                            if (donateButton) {
                                donateButton.style.display = 'none'; // Hide button
                            } else {
                                console.error(`Button not found for event ID: ${eventId}`);
                            }
                        } else {
                            document.querySelector(`#goal-message-${eventId}`).innerText = ""; // Clear message
                            const donateButton = document.querySelector(`#donate-button-${eventId}`);
                            if (donateButton) {
                                donateButton.style.display = 'block'; // Show button
                            } else {
                                console.error(`Button not found for event ID: ${eventId}`);
                            }
                        }
                    } else {
                        console.error('Error fetching donation data:', data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    </script>

</body>

</html>