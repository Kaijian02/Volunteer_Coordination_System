<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/notification.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Admin Dashboard</title>
    <style>
        #overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            display: none;
        }

        .overlay-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 20px;
            color: #fff;
        }
    </style>
</head>

<body>
    <div id="notificationContainer"></div>
    <div id="overlay">
        <div class="overlay-message"></div>
    </div>
    <section style="padding-top:20px;">
        <div class="container-fluid">
            <div class="row">
                <!-- Left Sidebar -->
                <div class="col-md-3 sidebar">
                    <div class="list-group text-center">
                        <h4 class="mb-4">Dashboard</h4>
                        <a href="#home" class="list-group-item list-group-item-action" data-content="home">
                            Home
                        </a>
                        <a href="#manage-new-users" class="list-group-item list-group-item-action" data-content="manageUsers">
                            Manage New Users
                        </a>
                        <a href="#manage-verified-events" class="list-group-item list-group-item-action" data-content="manageVerifiedUsers">
                            Verified Users
                        </a>
                        <a href="#manage-events" class="list-group-item list-group-item-action" data-content="manageEvents">
                            Manage Events
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" data-content="stats">
                            Statistical Graphs
                        </a>
                        <a href="#" class="list-group-item list-group-item-action text-danger" onclick="logout()">
                            Logout
                        </a>
                    </div>
                </div>

                <!-- Right Content Area -->
                <div class="col-md-9">
                    <div class="content-area" id="contentArea">
                        <h4>Welcome to the Event Dashboard</h4>
                        <p>Select an option from the left sidebar to get started.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">


    <script>
        function logout() {
            fetch('../server/logout.php')
                .then(response => {
                    if (response.ok) {
                        window.location.href = '../login.php';
                    } else {
                        console.error('Logout failed');
                    }
                })
                .catch(error => {
                    console.error('Error during logout:', error);
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar links
            const sidebarLinks = document.querySelectorAll('.list-group-item');

            // Content area
            const contentArea = document.getElementById('contentArea');

            // Event listener for each sidebar link
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Remove active class from all items
                    sidebarLinks.forEach(item => item.classList.remove('active'));
                    // Add active class to the clicked item
                    this.classList.add('active');

                    // Get the content identifier
                    const contentType = this.getAttribute('data-content');

                    // Load corresponding content dynamically
                    loadContent(contentType);
                });
            });

            function loadContent(contentType) {
                switch (contentType) {
                    case 'home':
                        contentArea.innerHTML = `
                        <h4>Welcome to the Event Dashboard</h4>
                        <p>Select an option from the left sidebar to get started.</p>
                    `;
                        break;
                    case 'manageUsers':
                        fetch('server/fetch_all_users.php')
                            .then(response => response.text())
                            .then(html => {
                                contentArea.innerHTML = html;
                                addActionListeners();
                            })
                            .catch(error => {
                                console.error('Error fetching users:', error);
                                contentArea.innerHTML = `
                                <h4>Manage Users</h4>
                                <p class ="text-muted">Check user profiles to validate their information, especially their certificates.</p>
                                <p>No new users to verify.</p>
                            `;

                            });
                        break;
                    case 'manageVerifiedUsers':
                        fetch('server/fetch_all_users.php?verified=1')
                            .then(response => response.text())
                            .then(html => {
                                contentArea.innerHTML = html;
                            })
                            .catch(error => {
                                console.error('Error fetching verified users:', error);
                                contentArea.innerHTML = `
                                <h4>Verified Users</h4>
                                <p class ="text-muted">View users whose certificates have been verified and validated.</p>
                                <p>No verified users.</p>
                            `;
                            });
                        break;
                    case 'manageEvents':
                        // Create the dropdown for filtering events
                        const dropdownHTML = `
                        <div class="form-group">
                            <label for="eventStatusFilter">Filter Events by Status:</label>
                            <select class="form-control" id="eventStatusFilter">
                                <option value="all">All Events</option>
                                <option value="upcoming">Upcoming Events</option>
                                <option value="ongoing">Ongoing Events</option>
                                <option value="passed">Past Events</option>
                            </select>
                        </div>
                    `;

                        // Fetch all events and display them
                        fetch('server/fetch_all_events.php')
                            .then(response => response.text())
                            .then(html => {
                                contentArea.innerHTML = `
                                    <h4>Manage Events</h4>
                                
                                    ${dropdownHTML}
                                    <div class="scrollable-table-container" id="eventsTableContainer">
                                        ${html}
                                    </div>
                                `;

                                // Attach the event listener to the dropdown
                                const eventStatusFilter = document.getElementById('eventStatusFilter');
                                if (eventStatusFilter) {
                                    eventStatusFilter.addEventListener('change', function() {
                                        const selectedFilter = this.value;
                                        fetch(`server/fetch_all_events.php?filter=${selectedFilter}`)
                                            .then(response => response.text())
                                            .then(html => {
                                                // Update the table without reloading the entire content
                                                document.getElementById('eventsTableContainer').innerHTML = html;
                                            })
                                            .catch(error => {
                                                console.error('Error fetching events:', error);
                                            });
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching events:', error);
                                contentArea.innerHTML = '<p>Error loading events data.</p>';
                            });
                        break;
                    case 'stats':
                        content = `
                        <h4>Statistical Graphs</h4>
                        <div id="errorMessage" style="display: none; color: red; text-align: center; padding: 20px; font-weight: bold;"></div>
                        <div class="row mb-4">
                                <div class="col-md-3">
                                    <select id="selectYear" class="form-control">
                                        <option value="">Select Year</option>
                                        <option value="2023">2023</option>
                                        <option value="2024">2024</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select id="selectMonth" class="form-control">
                                        <option value="">Select Month</option>
                                        <!-- Add options for months -->
                                        <option value="1">January</option>
                                        <option value="2">February</option>
                                        <option value="3">March</option>
                                        <option value="4">April</option>
                                        <option value="5">May</option>
                                        <option value="6">June</option>
                                        <option value="7">July</option>
                                        <option value="8">August</option>
                                        <option value="9">September</option>
                                        <option value="10">October</option>
                                        <option value="111">November</option>
                                        <option value="12">December</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <button id="filterButton" class="btn btn-primary">Filter</button>
                                </div>
                            </div>
                        <div id="statsContent" class="container mt-4" style="display: none;">
                            <div id="errorMessage" style="display: none; color: red; text-align: center; padding: 20px; font-weight: bold;"></div>

                            <div class="row mb-4">
                                <div class="col-12">
                                    <div id="totalEventsContent" class="card">
                                        <div class="card-body">
                                            <h5 class="card-title text-center"><strong>Total Events Created Monthly (Upcoming, Past, Ongoing)</strong></h5>
                                            <canvas id="totalEventsChart" width="400" height="400" style="margin: auto;"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div id="passedEventsContent" class="card">
                                        <div class="card-body">
                                            <h5 class="card-title text-center"><strong>Total Past Events Created</strong></h5>
                                            <canvas id="passedEventsChart" width="400" height="400" style="margin: auto;"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div id="attendanceContent" class="card">
                                        <div class="card-body">
                                            <h5 class="card-title text-center"><strong>Total Past Event Attendance Rates</strong></h5>
                                            <canvas id="attendanceChart" width="400" height="400" style="margin: auto;"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Section -->
                            <div id="attendanceSummary" class="mt-4 p-4 border rounded bg-light">
                                <h4 class="text-center mb-4 text-dark">Event Attendance Summary</h4>
                                <div class="row text-center">
                                    <div class="col-md-4 mb-3">
                                        <p class="text-muted">Total Events Created</p>
                                        <p class="h4 font-weight-bold" id="totalEventsCreated">-</p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="text-muted">Total Events Past</p>
                                        <p class="h4 font-weight-bold" id="totalEventsTakenAttendance">-</p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="text-muted">Total Events Day</p>
                                        <p class="h4 font-weight-bold" id="totalEventsDay">-</p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="text-muted">Total Present</p>
                                        <p class="h4 font-weight-bold" id="totalPresent">-</p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="text-muted">Total Absent</p>
                                        <p class="h4 font-weight-bold" id="totalAbsent">-</p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="text-muted">Total Volunteers</p>
                                        <p class="h4 font-weight-bold" id="totalVolunteers">-</p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <p class="text-muted">Attendance Rate</p>
                                        <p class="h4 font-weight-bold"><span id="attendanceRate">0</span>%</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                        contentArea.innerHTML = content;
                        var totalEventsChart, passedEventsChart, attendanceChart;
                        // Fetch event data
                        fetch('server/event_stats.php')
                            .then(response => response.json())
                            .then(data => {
                                if (data.success === false) {
                                    document.getElementById('errorMessage').style.display = 'block';
                                    document.getElementById('errorMessage').textContent = data.message;
                                    document.getElementById('statsContent').style.display = 'none';
                                } else {
                                    document.getElementById('errorMessage').style.display = 'none';
                                    document.getElementById('statsContent').style.display = 'block';

                                    // Setup Total Events Chart
                                    var totalCtx = document.getElementById('totalEventsChart').getContext('2d');
                                    totalEventsChart = new Chart(totalCtx, {
                                        type: 'bar',
                                        data: {
                                            labels: data.months, // Using months as labels
                                            datasets: [{
                                                label: 'Total Events Created',
                                                data: data.monthly_events,
                                                backgroundColor: '#4CAF50',
                                                borderColor: '#388E3C',
                                                borderWidth: 2,
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            plugins: {
                                                legend: {
                                                    position: 'top'
                                                },
                                                title: {
                                                    display: true,
                                                    text: 'Total Events Created'
                                                }
                                            },
                                            scales: {
                                                y: {
                                                    beginAtZero: true,
                                                    title: {
                                                        display: true,
                                                        text: 'Number of Events'
                                                    }
                                                },
                                                x: {
                                                    title: {
                                                        display: true,
                                                        text: 'Months'
                                                    }
                                                }
                                            }
                                        }
                                    });

                                    // Setup Passed Events Chart
                                    var passedCtx = document.getElementById('passedEventsChart').getContext('2d');
                                    passedEventsChart = new Chart(passedCtx, {
                                        type: 'bar',
                                        data: {
                                            labels: data.months,
                                            datasets: [{
                                                label: 'Total Past Events Created',
                                                data: data.total_passed_events,
                                                backgroundColor: '#FFCE56',
                                                borderColor: '#FFB300',
                                                borderWidth: 2,
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            plugins: {
                                                legend: {
                                                    position: 'top'
                                                },
                                                title: {
                                                    display: true,
                                                    text: 'Total Past Events Created'
                                                }
                                            },
                                            scales: {
                                                y: {
                                                    beginAtZero: true,
                                                    title: {
                                                        display: true,
                                                        text: 'Number of Events'
                                                    }
                                                },
                                                x: {
                                                    title: {
                                                        display: true,
                                                        text: 'Months'
                                                    }
                                                }
                                            }
                                        }
                                    });

                                    // Setup Attendance Chart
                                    var attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
                                    attendanceChart = new Chart(attendanceCtx, {
                                        type: 'doughnut',
                                        data: {
                                            labels: ['Present', 'Absent'],
                                            datasets: [{
                                                label: 'Participation Rates',
                                                data: [data.attendance_data.Present, data.attendance_data.Absent],
                                                backgroundColor: ['#36A2EB', '#FF6384']
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            plugins: {
                                                legend: {
                                                    position: 'top'
                                                },
                                                title: {
                                                    display: true,
                                                    text: 'Total Past Event Participation Rates'
                                                }
                                            }
                                        }
                                    });

                                    // Update Summary
                                    document.getElementById('totalEventsCreated').textContent = data.total_events;
                                    document.getElementById('totalEventsTakenAttendance').textContent = data.total_events_attendance;
                                    document.getElementById('totalEventsDay').textContent = data.total_event_days;
                                    document.getElementById('totalPresent').textContent = data.attendance_data.Present;
                                    document.getElementById('totalAbsent').textContent = data.attendance_data.Absent;
                                    document.getElementById('totalVolunteers').textContent = data.total_unique_volunteers;
                                    document.getElementById('attendanceRate').textContent = data.participation_rate;
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching event stats:', error);
                                document.getElementById('errorMessage').style.display = 'block';
                                document.getElementById('errorMessage').textContent = 'Failed to fetch event stats. Please try again later.';
                            });

                        // Filter button event listener
                        document.getElementById('filterButton').addEventListener('click', function() {
                            var selectedYear = document.getElementById('selectYear').value;
                            var selectedMonth = document.getElementById('selectMonth').value;
                            if (!selectedYear) {
                                showNotification("Please select a year", 'error');
                                return;
                            }
                            fetch(`server/event_stats.php?year=${selectedYear}&month=${selectedMonth}`)
                                .then(response => response.json())
                                .then(data => {
                                    console.log(data); // Log the filtered data for debugging
                                    if (data.success) {
                                        // Hide error message if visible
                                        document.getElementById('errorMessage').style.display = 'none';

                                        // Update the charts and the summary
                                        updateCharts(data);
                                    } else {
                                        document.getElementById('errorMessage').style.display = 'block';
                                        document.getElementById('errorMessage').textContent = data.message;
                                    }
                                })
                                .catch(error => {
                                    console.error('Error fetching filtered event stats:', error);
                                    document.getElementById('errorMessage').style.display = 'block';
                                    document.getElementById('errorMessage').textContent = 'Failed to fetch filtered event stats. Please try again later.';
                                });
                        });

                        // Function to update the charts with new data
                        function updateCharts(data) {
                            if (data.monthly_events && data.total_passed_events && data.attendance_data) {
                                // Update Total Events Created Chart
                                totalEventsChart.data.datasets[0].data = data.monthly_events || [];
                                totalEventsChart.update();

                                // Update Total Passed Events Created Chart
                                passedEventsChart.data.datasets[0].data = data.total_passed_events || [];
                                passedEventsChart.update();

                                // Update Total Passed Event Participation Rates Chart
                                attendanceChart.data.datasets[0].data = [
                                    data.attendance_data.Present || 0,
                                    data.attendance_data.Absent || 0
                                ];
                                attendanceChart.update();

                                // Calculate the total events created by summing the values in the Total Events Created chart dataset
                                const totalEventsCreated = data.monthly_events.reduce((total, num) => total + num, 0); // Sum the monthly events

                                // Update Summary
                                document.getElementById('totalEventsCreated').textContent = totalEventsCreated;
                                document.getElementById('totalEventsTakenAttendance').textContent = data.total_events_attendance || 0;
                                document.getElementById('totalEventsDay').textContent = data.total_event_days || 0;
                                document.getElementById('totalPresent').textContent = data.attendance_data.Present || 0;
                                document.getElementById('totalAbsent').textContent = data.attendance_data.Absent || 0;
                                document.getElementById('totalVolunteers').textContent = data.total_unique_volunteers || 0;
                                document.getElementById('attendanceRate').textContent = data.participation_rate || 0;
                            } else {
                                console.error('Error: Incomplete data provided for chart updates.');
                            }
                        }

                        break;
                }
            }



            function addActionListeners() {
                const processingNotificationId = new Date().getTime();
                // Verification button event listener
                document.querySelectorAll('.verify-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const userId = this.getAttribute('data-id');

                        // Confirm verification
                        if (confirm('Do you want to verify this user?')) {
                            fetch('server/verify_user.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: 'user_id=' + userId
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        showNotification('User verified successfully', 'success');
                                        loadContent('manageUsers');
                                    } else {
                                        alert('Error verifying user: ' + data.message);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('An error occurred while verifying the user.');
                                });
                        }
                    });
                });

                // Notify 1 user
                document.querySelectorAll('.notify-btn').forEach(function(button) {
                    button.addEventListener('click', function() {
                        var userEmail = this.getAttribute('data-email');
                        if (confirm('Do you want to notify the selected user about their profile legitimacy?')) {
                            showNotification("Processing...", "info", processingNotificationId);
                            showOverlay();
                            fetch('server/notify_users.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: 'email=' + encodeURIComponent(userEmail)
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        hideNotification(processingNotificationId);
                                        showNotification('Email notification sent successfully.', 'success');
                                        hideOverlay();
                                    } else {
                                        alert('Failed to send email: ' + data.message);
                                        hideNotification(processingNotificationId);
                                        hideOverlay();
                                    }
                                })
                                .catch(error => {
                                    console.error('Error sending email:', error);
                                    alert('An error occurred while sending the email.');
                                    hideNotification(processingNotificationId);
                                    hideOverlay();
                                });
                        }
                    });
                });

                // Notify all users
                document.querySelector('.notify-all-btn').addEventListener('click', function() {
                    if (confirm('Do you want to notify all users who haven’t completed their profile via email?')) {
                        showNotification("Processing...", "info", processingNotificationId);
                        showOverlay();
                        fetch('server/notify_users.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: 'notify_all=true' // Indicate that we want to notify all users
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    hideNotification(processingNotificationId);
                                    showNotification('Email Notifications sent to all users.', 'success');
                                    hideOverlay();
                                } else {
                                    alert('Failed to send notifications: ' + data.message);
                                    hideNotification(processingNotificationId);
                                    hideOverlay();
                                }
                            })
                            .catch(error => {
                                console.error('Error sending notifications:', error);
                                alert('An error occurred while sending notifications.');
                                hideNotification(processingNotificationId);
                                hideOverlay();
                            });
                    }
                });
            }
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

        function showOverlay() {
            document.getElementById('overlay').style.display = 'block';
        }

        function hideOverlay() {
            document.getElementById('overlay').style.display = 'none';
        }
    </script>
</body>

</html>