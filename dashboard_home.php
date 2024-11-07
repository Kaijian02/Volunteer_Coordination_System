<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit();
}

if (isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];

    $conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} else {
    echo "No event selected.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="./css/notification.css">
    <link rel="stylesheet" href="./css/sidebar.css">


    <style>
        body {
            background-color: #f8f9fa;
        }

        .content-area {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: 20px;
            min-height: 500px;
        }

        .content-area h4 {
            color: #007bff;
            margin-bottom: 20px;
        }

        .scrollable-table-container {
            max-height: 400px;
            overflow-y: auto;
        }

        .meta {
            grid-area: meta;
            font-size: 13px;
            color: #999;
            display: flex;
            justify-content: space-between;
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
            background-color: rgb(0, 0, 0);
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 900px;
            margin-top: 10px;
            box-sizing: border-box;
        }

        .skills-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        @media (max-width: 768px) {
            .skills-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
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

        #cropped-result {
            text-align: center;
            margin-top: 20px;
            width: 340px;
            height: 500px;
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

        .img-container {
            height: 500px;
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

        @media (max-width: 768px) {
            .modal-content {
                width: 100%;
                max-width: 600px;
            }

            #cropped-result {
                width: 100%;
                max-width: 300px;
            }
        }

        @media (max-width: 480px) {
            .modal-content {
                width: 100%;
                padding: 15px;
            }

            #cropped-result {
                width: 100%;
                max-width: 250px;
            }
        }

        form {
            max-height: 80vh;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <div id="notificationContainer"></div>
    <div id="overlay">
        <div class="overlay-message"></div>
    </div>
    <section>
        <div class="container-fluid">
            <div class="row">
                <!-- Left Sidebar -->
                <div class="col-md-3 sidebar">
                    <div class="list-group text-center">
                        <h4 class="mb-4">Dashboard</h4>
                        <a href="#" class="list-group-item list-group-item-action" data-content="stats">
                            Statistical Graph
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" data-content="editEvent">
                            Edit Event
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" data-content="volunteerList">
                            Volunteer List
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" data-content="attendance">
                            Take Attendance
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" data-content="recruitmentList">
                            Recruitment List
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" data-content="cancelledList">
                            Cancelled List
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" data-content="donationList">
                            Donation List
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

    <!-- Skills Modal -->
    <div id="skillsModal" class="modal">
        <div class="modal-content">
            <span class="close-skills">&times;</span>
            <h4>Select Skills</h4>
            <form id="skills-form">
                <div class="skills-grid">
                    <!-- <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="no-skill" name="no-skill" value="No Skill Required">
                                    <label class="form-check-label" for="no-skill">No Skill Required</label>
                                </div> -->
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
                </div>
                <button id="save-skills" class="btn btn-primary mt-3">Save</button>
            </form>
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

    <?php include 'footer.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script>
        let stateCodeMap = {};
        let allCities = {};
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

            // Function to dynamically load content based on clicked link
            function loadContent(contentType) {
                let content = '';

                switch (contentType) {
                    case 'stats':
                        content = `
                    <h4>Statistical Graphs</h4>
                    <div id="errorMessage" style="display: none; color: red; text-align: center; padding: 20px; font-weight: bold;"></div>
                    <div id="statsContent" style="display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 40px; width:800px; margin:auto;">
                        <div id="totalAttendanceContent" style="width:400px; height:500px;">
                            <p style="height:40px;text-align:center"><strong>Total Attendance for Entire Event</strong></p>
                            <canvas id="totalAttendanceChart" width="300" height="300" style="margin:auto;"></canvas>
                            <p style="margin-top:30px;"><strong>Total Present:</strong> <span id="totalPresent"></span></p>
                            <p><strong>Total Absent:</strong> <span id="totalAbsent"></span></p>
                            <p><strong>Total Event Days:</strong> <span id="totalEventDays"></span></p>
                        </div>

                        <div style="width:400px; padding-left: 20px;">
                            <div style="display:flex;height:40px;justify-content:center; margin-bottom:1rem;">
                                <p class="text-center"><strong>Attendance for</strong></p>
                                <select id="eventDay" style="height:30px; margin-left:10px;">  
                                </select>
                            </div>
                            <div id="dailyAttendanceContent" style="width:400px; height:300px; margin-top: 20px;">
                                <canvas id="dailyAttendanceChart" width="300" height="300" style="margin:auto;"></canvas>
                                <p style="margin-top:30px;"><strong>Present on Selected Day:</strong> <span id="dailyTotalPresent">0</span></p>
                                <p><strong>Absent on Selected Day:</strong> <span id="dailyTotalAbsent">0</span></p>
                                <p><strong>Attendance Rate for Selected Day:</strong> <span id="dailyAttendanceRate">0</span>%</p> <!-- New paragraph for Attendance Rate -->
                            </div>
                        </div>
                    </div>

                    <div id="attendanceSummary" style="margin-top: 40px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-left: auto; margin-right: auto;">
                        <h4 style="text-align: center; margin-bottom: 20px; color: #333;">Event Attendance Summary</h4>
                        <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 200px; margin-bottom: 15px;">
                                <p style="margin: 0; color: #666;">Total Volunteers</p>
                                <p style="font-size: 24px; font-weight: bold; margin: 5px 0;" id="totalUniqueVolunteers">-</p>
                            </div>
                            <div style="flex: 1; min-width: 200px; margin-bottom: 15px;">
                                <p style="margin: 0; color: #666;">Retained Volunteers</p>
                                <p style="font-size: 24px; font-weight: bold; margin: 5px 0;" id="retainedVolunteers">-</p>
                            </div>
                            <div style="flex: 1; min-width: 200px; margin-bottom: 15px;">
                                <p style="margin: 0; color: #666;">Attendance Rate</p>
                                <p style="font-size: 24px; font-weight: bold; margin: 5px 0;"><span id="attendanceRate">0</span>%</p>
                            </div>
                            <div style="flex: 1; min-width: 200px; margin-bottom: 15px;">
                                <p style="margin: 0; color: #666;">Retention Rate</p>
                                <p style="font-size: 24px; font-weight: bold; margin: 5px 0;"><span id="retentionRate">0</span>%</p>
                            </div>
                        </div>
                    </div>
                </div>
                    
                    `;
                        contentArea.innerHTML = content;
                        fetch('server/attendance_stats.php?event_id=' + <?php echo $event_id; ?>)
                            .then(response => response.json()) //convert the response to JSON, if success return data is stored in the data variable
                            .then(data => {
                                if (data.success === false) {
                                    // If the event hasn't passed, display the message
                                    document.getElementById('errorMessage').style.display = 'block';
                                    document.getElementById('errorMessage').textContent = data.message;
                                    document.getElementById('statsContent').style.display = 'none';
                                } else {

                                    document.getElementById('errorMessage').style.display = 'none';
                                    document.getElementById('statsContent').style.display = 'block';
                                    var eventDaySelect = document.getElementById('eventDay');
                                    data.attendance.event_days.forEach((day, index) => {
                                        var option = document.createElement('option');
                                        option.value = day;
                                        option.text = day;
                                        eventDaySelect.appendChild(option);
                                        if (index === 0) {
                                            option.selected = true; // Mark the first day as selected
                                            eventDaySelect.value = day; // Set the value of the dropdown to the first day
                                        }
                                    });

                                    var totalCtx = document.getElementById('totalAttendanceChart').getContext('2d');
                                    var totalAttendanceChart = new Chart(totalCtx, {
                                        type: 'pie',
                                        data: {
                                            labels: ['Present', 'Absent'],
                                            datasets: [{
                                                label: 'Total Attendance Rates',
                                                data: [data.attendance.attendance_data.Present, data.attendance.attendance_data.Absent],
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

                                    // Initialize daily chart as empty initially
                                    var dailyCtx = document.getElementById('dailyAttendanceChart').getContext('2d');
                                    var dailyAttendanceChart = new Chart(dailyCtx, {
                                        type: 'pie',
                                        data: {
                                            labels: ['Present', 'Absent'],
                                            datasets: [{
                                                label: 'Daily Attendance',
                                                data: [0, 0], // Initially empty
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

                                    if (data.attendance.event_days.length > 0) {
                                        updateDailyChartForSelectedDay(data.attendance.event_days[0], dailyAttendanceChart);
                                    }

                                    // Add event listener to handle day selection
                                    eventDaySelect.addEventListener('change', function() {
                                        var selectedDay = this.value;
                                        if (selectedDay !== "all") {
                                            updateDailyChartForSelectedDay(selectedDay, dailyAttendanceChart);
                                        }
                                    });


                                    const totalPresent = data.attendance.attendance_data.Present;
                                    const totalAbsent = data.attendance.attendance_data.Absent;
                                    document.getElementById('totalPresent').innerText = totalPresent; // Total present
                                    document.getElementById('totalAbsent').innerText = totalAbsent; // Total absent

                                    // Calculate Attendance Rate
                                    const attendanceRate = totalPresent + totalAbsent > 0 ? (totalPresent / (totalPresent + totalAbsent)) * 100 : 0;
                                    document.getElementById('attendanceRate').innerText = attendanceRate.toFixed(2); // Update Attendance Rate with two decimal places

                                    // Update summary information
                                    document.getElementById('totalEventDays').innerText = data.attendance.event_days.length; // Total event days
                                    document.getElementById('totalUniqueVolunteers').innerText = data.attendance.total_unique_volunteers;
                                    document.getElementById('totalPresent').innerText = data.attendance.attendance_data.Present; // Total present
                                    document.getElementById('totalAbsent').innerText = data.attendance.attendance_data.Absent; // Total absent
                                    document.getElementById('retainedVolunteers').innerText = data.attendance.retained_volunteers;
                                    document.getElementById('retentionRate').innerText = data.attendance.retention_rate;

                                }
                            })
                            .catch(error => console.error('Error fetching attendance data:', error));

                        function updateDailyChartForSelectedDay(selectedDay, dailyAttendanceChart) {
                            let apiUrl = 'server/attendance_stats.php?event_id=' + <?php echo $event_id; ?> + '&attendance_date=' + selectedDay;

                            fetch(apiUrl)
                                .then(response => response.json())
                                .then(data => {
                                    const dailyPresent = data.attendance.attendance_data.Present;
                                    const dailyAbsent = data.attendance.attendance_data.Absent;

                                    // Update daily chart data
                                    dailyAttendanceChart.data.datasets[0].data = [data.attendance.attendance_data.Present, data.attendance.attendance_data.Absent];
                                    dailyAttendanceChart.update();
                                    document.getElementById('dailyTotalPresent').innerText = dailyPresent;
                                    document.getElementById('dailyTotalAbsent').innerText = dailyAbsent;
                                    const dailyAttendanceRate = (dailyPresent + dailyAbsent > 0) ?
                                        (dailyPresent / (dailyPresent + dailyAbsent)) * 100 :
                                        0;

                                    document.getElementById('dailyAttendanceRate').innerText = dailyAttendanceRate.toFixed(2);
                                })
                                .catch(error => console.error('Error fetching daily attendance data:', error));
                        }
                        break;
                    case 'editEvent':
                        fetch(`server/fetch_event_data.php?event_id=<?php echo $event_id; ?>`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    const eventData = data.event;
                                    content = generateEditEventForm(eventData);
                                    contentArea.innerHTML = content;
                                    setupSkillsModal();
                                    setupDateValidation(eventData);
                                    setupTimeValidation();
                                    setupDonationLogic();
                                    setupImageUpload();
                                    setupEditEventListeners(eventData);
                                } else {
                                    contentArea.innerHTML = `<p>Error: ${data.message}</p>`;
                                }
                            })
                            .catch(error => console.error('Error:', error));
                        break;
                        // Function to handle skill modal functionality
                        function setupSkillsModal() {
                            const skillsModal = document.getElementById('skillsModal');
                            const editSkillsButton = document.getElementById('edit-skills');
                            const closeSkillsModal = document.querySelector('.close-skills');
                            const selectedSkillsDiv = document.getElementById('selected-skills');
                            const saveSkillsButton = document.getElementById('save-skills');

                            let selectedSkills = Array.from(selectedSkillsDiv.querySelectorAll('.badge'))
                                .map(badge => badge.textContent.trim());

                            editSkillsButton.addEventListener('click', () => {
                                skillsModal.style.display = 'block';
                                document.querySelectorAll('input[name="skills"]').forEach((checkbox) => {
                                    checkbox.checked = selectedSkills.includes(checkbox.value);
                                });
                            });

                            closeSkillsModal.addEventListener('click', () => {
                                skillsModal.style.display = 'none';
                            });

                            window.addEventListener('click', (e) => {
                                if (e.target == skillsModal) {
                                    skillsModal.style.display = 'none';
                                }
                            });

                            saveSkillsButton.addEventListener('click', (e) => {
                                e.preventDefault(); // Prevent default button action

                                // Clear the array and get the selected skills
                                selectedSkills = Array.from(document.querySelectorAll('input[name="skills"]:checked'))
                                    .map(checkbox => checkbox.value);

                                // Update the UI with selected skills
                                selectedSkillsDiv.innerHTML = selectedSkills.map(skill => `<span class="badge bg-primary me-1">${skill}</span>`).join('');

                                // Close the modal
                                skillsModal.style.display = 'none';
                            });
                        }

                        // Function to setup date validation and visibility
                        function setupDateValidation(eventData) {
                            const endDateContainer = document.getElementById('endDateContainer');
                            const endDateInput = document.getElementById('endDate');
                            const startDateInput = document.getElementById('startDate');

                            // Function to ensure the end date is not earlier than the start date
                            function validateDates() {
                                const startDate = new Date(startDateInput.value);
                                const endDate = new Date(endDateInput.value);

                                // If end date is earlier than start date
                                if (endDateInput.value && endDate < startDate) {
                                    alert("End date cannot be earlier than start date.");
                                    endDateInput.value = ''; // Clear invalid end date
                                }
                            }

                            // Function to set the minimum selectable date for the end date
                            function setEndDateMin() {
                                if (startDateInput.value) {
                                    const startDate = new Date(startDateInput.value);
                                    startDate.setDate(startDate.getDate() + 1); // Add one day to start date
                                    const minEndDate = startDate.toISOString().split('T')[0]; // Format to YYYY-MM-DD
                                    endDateInput.min = minEndDate; // Set min attribute of end date to start date + 1 day
                                    endDateInput.disabled = false;
                                } else {
                                    endDateInput.disabled = true; // Disable end date input if no start date selected
                                    endDateInput.value = ''; // Clear end date value if disabled
                                }
                            }


                            // Function to set the minimum start date
                            function setMinStartDate() {
                                const today = new Date();
                                const minCreateDate = new Date(today);
                                minCreateDate.setDate(today.getDate() + 7); // Set to one week from today
                                const createMinDate = minCreateDate.toISOString().split('T')[0]; // Format to YYYY-MM-DD
                                startDateInput.setAttribute('min', createMinDate); // Allow only one week from today for creating new events

                                if (eventData.id) { // If editing an event
                                    const originalStartDate = new Date(eventData.start_date);
                                    const threeDaysFromNow = new Date(today);
                                    threeDaysFromNow.setDate(today.getDate() + 3); // Get the date 3 days from today

                                    if (originalStartDate < threeDaysFromNow) {
                                        // If the original start date is less than 3 days from now, disable editing
                                        showNotification("Organizer are not allowed to edit the event that start in less than 3 days.", "error");
                                        // startDateInput.disabled = true; // Disable editing the start date
                                    } else {
                                        // Set the minimum to the original start date to prevent changing it to an earlier date
                                        startDateInput.setAttribute('min', originalStartDate.toISOString().split('T')[0]);
                                    }
                                }
                            }

                            startDateInput.addEventListener('change', function() {
                                setEndDateMin(); // Update end date min value based on start date
                            });
                            endDateInput.addEventListener('change', validateDates);

                            setMinStartDate(); // Set the minimum start date
                            setEndDateMin(); // Set the minimum selectable end date
                        }


                        // Function to setup time validation
                        function setupTimeValidation() {
                            const startTimeInput = document.getElementById('start_time');
                            const endTimeInput = document.getElementById('end_time');

                            function setEndTimeMin() {
                                if (startTimeInput.value) {
                                    endTimeInput.min = startTimeInput.value; // Set minimum end time to start time
                                    endTimeInput.disabled = false; // Enable end time input
                                } else {
                                    endTimeInput.min = ''; // Reset min if no start time
                                    endTimeInput.disabled = true; // Disable end time input
                                }
                            }

                            // Function to validate the end time
                            function validateEndTime() {
                                if (endTimeInput.value && startTimeInput.value) {
                                    if (endTimeInput.value <= startTimeInput.value) {
                                        showNotification('End Time must be later than Start Time.', 'error');
                                        endTimeInput.value = ''; // Clear the end time input
                                    }
                                }
                            }

                            startTimeInput.addEventListener('change', setEndTimeMin); // Set end time min when start time changes
                            endTimeInput.addEventListener('change', validateEndTime); // Validate end time on change
                        }

                        function setupDonationLogic() {
                            // Donation
                            const donationYes = document.getElementById('donationYes');
                            const donationNo = document.getElementById('donationNo');
                            const goalContainer = document.getElementById('goalContainer');
                            const goalValue = document.getElementById('goal');

                            // Function to toggle goal visibility
                            function toggleGoalVisibility() {
                                if (donationYes.checked) {
                                    goalContainer.style.display = 'block';
                                } else {
                                    goalValue.value = ''; // Clear the goal value if No is selected
                                    goalContainer.style.display = 'none';
                                }
                            }

                            // Add event listeners to the radio buttons
                            donationYes.addEventListener('change', toggleGoalVisibility);
                            donationNo.addEventListener('change', toggleGoalVisibility);

                            // Call the function once to set initial visibility based on current selection
                            toggleGoalVisibility();
                        }

                        function setupImageUpload() {
                            // Upload img
                            const uploadPhoto = document.getElementById('upload-photo');
                            const imagePreview = document.getElementById('image-preview');
                            const modal = document.getElementById('myModal');
                            const closeModal = document.querySelector('.close');
                            const cropButton = document.getElementById('crop-button');
                            const croppedResult = document.getElementById('cropped-result');
                            let cropper;
                            let posterBlob; // Store the cropped image blob globally

                            // Open file input when button is clicked
                            document.getElementById('upload-poster-btn').addEventListener('click', (event) => {
                                event.preventDefault();
                                uploadPhoto.click();
                            });

                            // Handle file input change event
                            uploadPhoto.addEventListener('change', (e) => {
                                const files = e.target.files;
                                if (files && files.length > 0) {
                                    const file = files[0];
                                    const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'jfif'];
                                    const fileName = file.name;
                                    const fileExtension = fileName.split('.').pop().toLowerCase();

                                    // Validate file type
                                    if (!allowedExtensions.includes(fileExtension)) {
                                        showNotification('Invalid file type. Allowed types: jpg, jpeg, png, gif, jfif.', 'error');
                                        uploadPhoto.value = ''; // Clear the input
                                        return;
                                    }

                                    const reader = new FileReader();
                                    reader.onload = (event) => {
                                        imagePreview.src = event.target.result;
                                        modal.style.display = 'block';
                                        if (cropper) {
                                            cropper.destroy();
                                        }
                                        cropper = new Cropper(imagePreview, {
                                            aspectRatio: 2 / 3,
                                            viewMode: 1,
                                            minCropBoxWidth: 350,
                                            minCropBoxHeight: 500,
                                        });
                                    };
                                    reader.readAsDataURL(file);
                                }
                            });

                            // Close modal
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

                            // Handle crop and save button click
                            cropButton.addEventListener('click', () => {
                                if (cropper) {
                                    const canvas = cropper.getCroppedCanvas({
                                        width: 350,
                                        height: 500
                                    });
                                    canvas.toBlob((blob) => {
                                        posterBlob = blob; // Store blob for later use
                                        const url = URL.createObjectURL(blob);
                                        croppedResult.innerHTML = `<img src="${url}" alt="Cropped Event Poster">`;
                                        modal.style.display = 'none';
                                    }, 'image/jpeg');
                                }
                            });
                        }


                    case 'volunteerList':
                        fetch('server/fetch_volunteer_list.php?event_id=<?php echo $event_id; ?>')
                            .then(response => response.text())
                            .then(html => {
                                contentArea.innerHTML = html;
                                addActionListeners();
                            })
                            .catch(error => console.error('Error:', error));
                        break;
                    case 'attendance':
                        fetch('server/fetch_attendance_list.php?event_id=<?php echo $event_id; ?>')
                            .then(response => response.text())
                            .then(html => {
                                contentArea.innerHTML = html;
                                setupAttendanceSubmission();
                            })
                            .catch(error => console.error('Error fetching attendance:', error));
                        break;
                    case 'recruitmentList':
                        fetch('server/fetch_recruitment_list.php?event_id=<?php echo $event_id; ?>')
                            .then(response => response.text())
                            .then(html => {
                                contentArea.innerHTML = html;
                                addActionListeners();
                            })
                            .catch(error => console.error('Error:', error));
                        break;
                    case 'cancelledList':
                        fetch('server/fetch_pending_cancel_list.php?event_id=<?php echo $event_id; ?>')
                            .then(response => response.text())
                            .then(html => {
                                contentArea.innerHTML = html;
                                addActionListeners();
                            })
                            .catch(error => console.error('Error:', error));
                        break;
                    case 'donationList':
                        fetch('server/fetch_donation_list.php?event_id=<?php echo $event_id; ?>')
                            .then(response => response.text())
                            .then(html => {
                                contentArea.innerHTML = html;
                                addActionListeners();
                            })
                            .catch(error => console.error('Error:', error));
                        break;
                    default:
                        content = `
                        <h4>Welcome to the Event Dashboard</h4>
                        <p>Select an option from the left sidebar to get started.</p>
                    `;
                }

                // Update the content area with the selected content
                contentArea.innerHTML = content;

                // Add event listeners to Accept and Reject buttons after content is loaded
                addActionListeners();
            }



            // Function to add action listeners to the accept and reject buttons
            function addActionListeners() {
                document.querySelectorAll('.accept-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const userId = this.getAttribute('data-id');
                        if (confirm("Do you want to recruit this volunteer?")) {
                            updateVolunteerStatus(userId, 'Approved', false);
                        }
                    });
                });

                document.querySelectorAll('.reject-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const userId = this.getAttribute('data-id');
                        if (confirm("Are you sure you want to reject this volunteer?")) {
                            const reason = prompt("Please provide a reason for rejection:");
                            if (reason !== null) { // If user clicks "OK" (even if it's empty)
                                if (reason.trim() !== '') {
                                    updateVolunteerStatus(userId, 'Rejected', reason, false);
                                } else {
                                    showNotification("Reject reason is required.", "error");
                                }
                            }
                        }
                    });
                });

                document.querySelectorAll('.cancel-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const userId = this.getAttribute('data-id');
                        if (confirm("Are you sure you want to cancel this application?")) {
                            const reason = prompt("Please provide a reason for cancellation:");
                            if (reason !== null) { // If user clicks "OK" (even if it's empty)
                                if (reason.trim() !== '') {
                                    updateVolunteerStatus(userId, 'Cancelled', reason, false);
                                } else {
                                    showNotification("Cancellation reason is required.", "error");
                                }
                            }
                        }
                    });
                });

                // Accept pending cancel with no penalty
                document.querySelectorAll('.accept-cancel-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const userId = this.getAttribute('data-id');
                        if (confirm("Do you want to confirm this cancellation request without penalty?")) {
                            updateVolunteerStatus(userId, 'Cancellation Approved', '', false); // Confirm cancellation
                        }
                    });
                });

                // Accept pending cancel with penalty
                document.querySelectorAll('.accept-cancel-penalty-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const userId = this.getAttribute('data-id');
                        if (confirm("Do you want to confirm this cancellation request with penalty (minus the volunteer credit score)?")) {
                            updateVolunteerStatus(userId, 'Cancellation Approved', '', true); // Confirm cancellation
                            deductCredit(userId); // Deduct credit
                        }
                    });
                });

                document.querySelectorAll('.view-evidence-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const evidenceUrl = this.getAttribute('data-url');

                        if (evidenceUrl) {
                            // Open the file in a new tab
                            window.open(evidenceUrl, '_blank');
                        } else {
                            alert('No evidence available.');
                        }
                    });
                });

                document.querySelectorAll('.leave-review-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const userId = this.getAttribute('data-user-id');
                        leaveReview(userId, false); // Leave a new review
                    });
                });

                document.querySelectorAll('.edit-review-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const userId = this.getAttribute('data-user-id');
                        leaveReview(userId, true); // Edit the existing review
                    });
                });
            }



            // Function to update the status of a volunteer
            function updateVolunteerStatus(userId, status, reason = '', applyPenalty) {
                showOverlay();
                const processingNotificationId = new Date().getTime();
                showNotification("Processing...", "info", processingNotificationId);
                fetch('server/update_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            user_id: userId,
                            event_id: '<?php echo $event_id; ?>',
                            status: status,
                            reason: reason,
                            apply_penalty: applyPenalty
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, "success");
                        } else {
                            showNotification(data.message, "error");
                        }
                        hideNotification(processingNotificationId);
                        hideOverlay();
                        // Reload the content to reflect changes
                        if (status === 'Approved' || status === 'Rejected') {
                            loadContent('recruitmentList');
                        } else if (status === 'Cancellation Approved') {
                            loadContent('cancelledList');
                        } else {
                            loadContent('volunteerList');
                        }
                    })
                    .catch(error => {
                        showNotification("An error occurred. Please try again.", "error");
                        hideNotification(processingNotificationId);
                        hideOverlay();
                        console.error('Error:', error)
                    });
            }

            function deductCredit(userId) {
                fetch('server/deduct_credit.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            user_id: userId,
                            credit: 10
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadContent('cancelledList');
                        } else {
                            showNotification(data.message, "error");
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }

            function setupAttendanceSubmission() {
                const attendanceForm = document.getElementById('attendance-form');
                if (attendanceForm) {
                    attendanceForm.addEventListener('submit', function(event) {
                        event.preventDefault();
                        // Validation: Check if every user has a radio button selected
                        const attendanceData = new FormData(attendanceForm);
                        let allSelected = true;
                        // Get the selected date from the form
                        const selectedDate = document.getElementById('attendance_date').value;
                        // Get today's date in the same format as the selected date (YYYY-MM-DD)
                        const today = new Date().toISOString().split('T')[0];
                        // Check if the selected date is today
                        if (selectedDate > today) {
                            showNotification('You cannot take attendance for a future date.', 'error');
                            return;
                        }
                        // Create a Set to track user IDs who have selected attendance
                        const selectedUserIds = new Set();
                        for (let [key, value] of attendanceData.entries()) {
                            if (key.startsWith('attendance[')) {
                                const userId = key.match(/\d+/)[0]; // Extract user ID from the key
                                if (value) { // If there is a selected value
                                    selectedUserIds.add(userId);
                                }
                            }
                        }
                        // Check if all user IDs have a selection
                        const totalUsers = attendanceForm.querySelectorAll('input[type="radio"][name^="attendance"]').length / 2; // Divided by 2 for Present and Absent
                        if (selectedUserIds.size < totalUsers) {
                            allSelected = false;
                        }
                        if (!allSelected) {
                            showNotification('Please select attendance for all users.', 'error');
                            return; // Stop the submission
                        }
                        // const formData = new FormData(attendanceForm);
                        // // Log form data for debugging
                        // for (let [key, value] of formData.entries()) {
                        //     console.log(`${key}: ${value}`);
                        // }
                        fetch('server/update_attendance.php', {
                                method: 'POST',
                                body: attendanceData
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (result.status === 'success') {
                                    showNotification('Attendance updated successfully', 'success');
                                } else {
                                    showNotification('Error updating attendance: ' + result.message, 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error submitting attendance:', error);
                            });
                    });

                }
                // Add event listener to PDF button after the page or dynamic content has loaded
                setupPdfButton();

                document.getElementById('attendance_date').addEventListener('change', function() {
                    const selectedDate = this.value;
                    fetch(`server/fetch_attendance_list.php?event_id=<?php echo $event_id; ?>&attendance_date=${selectedDate}`)
                        .then(response => response.text())
                        .then(html => {
                            contentArea.innerHTML = html;
                            setupAttendanceSubmission(); // Re-setup the attendance submission event listeners
                        })
                        .catch(error => console.error('Error fetching attendance:', error));
                });

                // Add the event listener for the attendance date dropdown
                const attendanceDateSelect = document.getElementById('attendance_date');
                if (attendanceDateSelect) {
                    attendanceDateSelect.addEventListener('change', function() {
                        const selectedDate = this.value;
                        fetch(`server/fetch_attendance_list.php?event_id=<?php echo $event_id; ?>&attendance_date=${selectedDate}`)
                            .then(response => response.text())
                            .then(html => {
                                contentArea.innerHTML = html;
                                setupAttendanceSubmission(); // Re-setup the attendance submission event listeners
                            })
                            .catch(error => console.error('Error fetching attendance:', error));
                    });
                }
            }

            function leaveReview(userId, isEdit) {
                const actionText = isEdit ? "Edit the review" : "Leave a review";
                var review = prompt(actionText + " for the volunteer " + ":");
                if (review === null) {
                    return; // Do nothing if "Cancel" is clicked
                }
                // Check if the review is empty or contains only whitespace
                if (review.trim() === "") {
                    showNotification('Review cannot be empty.', 'error');
                    return; // Exit the function if the review is invalid
                }
                const formData = new FormData();
                formData.set('review', review);
                formData.set('volunteer_id', userId);
                formData.set('event_id', <?php echo $event_id; ?>);
                formData.set('is_edit', isEdit ? '1' : '0');

                fetch('server/submit_review.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log(data);
                        showNotification(data.message, data.status);
                        loadContent('volunteerList');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred while submitting the review.', 'error');
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

        // Hide overlay
        function hideOverlay() {
            document.getElementById('overlay').style.display = 'none';
        }

        function setupPdfButton() {
            const generatePdfBtn = document.getElementById('generate-pdf-btn');
            if (generatePdfBtn) {
                generatePdfBtn.addEventListener('click', function() {
                    const selectedDate = document.getElementById('attendance_date').value; // Get selected date
                    const eventId = <?php echo $event_id; ?>; // Pass event_id (PHP variable)
                    if (!selectedDate) {
                        alert('Please select a date to generate the attendance list.');
                        return;
                    }
                    const pdfUrl = `server/generate_attendance_pdf.php?event_id=${eventId}&attendance_date=${selectedDate}`;
                    fetch(pdfUrl)
                        .then(response => response.text())
                        .then(result => {
                            try {
                                const jsonResult = JSON.parse(result); // Try parsing the result as JSON
                                if (jsonResult.status === 'error') { // Check if the response has an error status
                                    showNotification(jsonResult.message, 'error');
                                } else {
                                    window.open(pdfUrl, '_blank'); // Open the URL in a new tab
                                }
                            } catch (e) {
                                // If parsing fails, assume the result is a PDF
                                window.open(pdfUrl, '_blank'); // Open the PDF in a new tab
                            }
                        })
                        .catch(error => {
                            console.error('Error generating PDF:', error);
                            showNotification('An error occurred while generating the PDF.', 'error');
                        });
                });
            }


            const generateEmptyPdfBtn = document.getElementById('generate-empty-pdf-btn');
            if (generateEmptyPdfBtn) {
                generateEmptyPdfBtn.addEventListener('click', function() {
                    const selectedDate = document.getElementById('attendance_date').value; // Get selected date
                    const eventId = <?php echo $event_id; ?>; // Pass event_id (PHP variable)

                    if (!selectedDate) {
                        alert('Please select a date to generate the volunteer list.');
                        return;
                    }

                    // Open the PDF in a new tab
                    window.open(`server/generate_volunteer_pdf.php?event_id=${eventId}&attendance_date=${selectedDate}`, '_blank');
                });
            }

            document.getElementById('generate-ecert-btn').addEventListener('click', function() {
                showOverlay();
                const processingNotificationId = new Date().getTime();
                showNotification("Processing...", "info", processingNotificationId);
                fetch('server/generate_ecertificates.php?event_id=<?php echo $event_id; ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            hideNotification(processingNotificationId);
                            hideOverlay();
                        } else if (data.success === false) {
                            showNotification(data.message, 'error');
                            hideNotification(processingNotificationId);
                            hideOverlay();
                        } else {
                            alert('There was an error generating the E-Certificates: ' + data.message);
                            hideOverlay();
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        }

        function generateEditEventForm(eventData) {
            const startDate = eventData.start_date && eventData.start_date !== '0000-00-00' ? eventData.start_date : '';
            const endDate = eventData.end_date && eventData.end_date !== '0000-00-00' ? eventData.end_date : '';
            const skills = eventData.skills ? eventData.skills.split(',').map(skill => `<span class="badge bg-primary me-1">${skill.trim()}</span>`).join('') : '';
            const selectedState = eventData.state || ''; // Get the selected state from the event data
            const selectedCity = eventData.city || ''; // Get the selected city from the event data
            return `
        <h4>Edit Event</h4>
        <form id="editEventForm" novalidate>
            <input type="hidden" name="event_id" value="${eventData.id}">
            <h4>Step 1: About Event</h4>
            <div class="form-group col-md-12 col-sm-12">
                <label for="title">Title:</label>
                <input type="text" class="form-control" id="title" name="title" value="${eventData.title}" required>
            </div>
            <div class="form-group col-md-12 col-sm-12">
                <label for="description">Description:</label>
                <textarea class="form-control" id="description" name="description" style="resize: none;" rows="8"; required>${eventData.description}</textarea>
            </div>

            <h4>Step 2: Date/Time & Venue</h4>
            <div class="form-group col-md-12 col-sm-12">
                <label for="startDate">Start Date:</label>
                <input type="date" class="form-control" id="startDate" name="startDate" value="${startDate}" required>
            </div>
            <div class="form-group col-md-12 col-sm-12" id="endDateContainer" style="display: ${endDate ? 'block' : 'none'};">
                <label for="endDate">End Date:</label>
                <input type="date" class="form-control" id="endDate" name="endDate" value="${endDate || ''}" required>
            </div>
            <div class="form-group col-md-12 col-sm-12">
                <label for="start_time">Start Time:</label>
                <input type="time" class="form-control" id="start_time" name="start_time" value="${eventData.start_time}" required>
            </div>
            <div class="form-group col-md-12 col-sm-12">
                <label for="end_time">End Time:</label>
                <input type="time" class="form-control" id="end_time" name="end_time" value="${eventData.end_time}" required>
            </div>
            <div class="form-group col-md-12 col-sm-12">
                <label for="venue">Venue</label>
                <input type="text" class="form-control" id="venue" name="venue" value="${eventData.venue}" required>
            </div>
          
            <div class="form-group col-md-12 col-sm-12">
                <label for="state">State:</label>
                <select id="state" name="state" class="form-control">
                    <option value="">Select a state</option>
                    ${Object.keys(stateCodeMap).map(state => `
                        <option value="${state}" ${state === selectedState ? 'selected' : ''}>${state}</option>
                    `).join('')}
                </select>
            </div>
            <div class="form-group col-md-12 col-sm-12">
                <label for="city">City:</label>
                <select id="city" name="city" class="form-control">
                    <option value="">Select a city</option>
                </select>
            </div>

            <h4>Step 3: Details/Skills & Requirements</h4>
            <div class="form-group col-md-12 col-sm-12">
                <label for="volunteers">Estimated Volunteer Needed</label>
                <input type="number" class="form-control" id="volunteers" name="volunteers" min="1" value="${eventData.volunteers_needed}" required>
            </div>
            <div class="form-group col-md-12 col-sm-12">
                <label for="closeEvent">Close the event once the total number of estimated volunteers needed is met?</label>
                <div class="form-check">
                    <input type="radio" id="closeEventYes" name="closeEvent" value="Yes" ${eventData.close_event === 'Yes' ? 'checked' : ''}>
                    <label for="closeEventYes">Yes</label><br>
                </div>
                <div class="form-check col-md-12 col-sm-12">
                    <input type="radio" id="closeEventNo" name="closeEvent" value="No" ${eventData.close_event === 'No' ? 'checked' : ''}>
                    <label for="closeEventNo">No</label>
                </div>
                <span class="error-msg" id="closeEventError" style="color:red;display:none;">Please select an option.</span>
            </div>
            <div class="form-group col-md-12 col-sm-12">
                <label for="skills">Skills</label>
                <button id="edit-skills" type="button" style="border: none; background: white;">
                    <i class="fas fa-edit"></i>
                </button>
                <div id="selected-skills">
                    ${skills}
                </div>
            </div>

            <h4>Step 4: Requirements & Donation</h4>
            <div class="form-group col-md-12 col-sm-12">
                <label for="minAge">Minimum Age:</label>
                <input type="number" class="form-control" id="minAge" name="minAge" value="${eventData.min_age}" required>
            </div>
            <div class="form-group col-md-12 col-sm-12">
                <label for="comments">Additional Requirements:</label>
                <textarea class="form-control" id="comments" name="comments">${eventData.comments}</textarea>
            </div>
            <div class="form-group col-md-12 col-sm-12">
                <label for="donation">Is this event needs donation?</label>
                <div class="form-check">
                    <input type="radio" id="donationYes" name="donation" value="Yes" ${eventData.donation === 'Yes' ? 'checked' : ''}>
                    <label for="donationYes">Yes</label><br>
                </div>
                <div class="form-check col-md-12 col-sm-12">
                    <input type="radio" id="donationNo" name="donation" value="No" ${eventData.donation === 'No' ? 'checked' : ''}>
                    <label for="donationNo">No</label>
                </div>
                <span class="error-msg" id="donationError" style="color:red;display:none;">Please select an option.</span>
            </div>
            <div class="form-group col-md-12 col-sm-12" id="goalContainer" style="display: none;">
                <label for="goal">Set Goal:</label>
                <input type="number" class="form-control" id="goal" name="goal" value="${eventData.goal}">
            </div>

            <h4>Last Step: Upload an Event Poster</h4>
                <div class="form-group col-md-12 col-sm-12" style="display: flex; flex-direction:column">
                    <div id="cropped-result" style="margin: auto;">
                        <img src="${eventData.event_poster}" alt="Default Image">
                    </div>

            <!-- Button to change profile -->
                <button id="upload-poster-btn" type="button" class="btn btn-primary mt-3" style="margin: auto;">Upload Poster</button>
                <input type="file" id="upload-photo" name="posterImage" accept="image/*" style="display: none;">
            </div>
            
            <button type="submit" class="btn btn-primary">Update Event</button>
            <button type="button" id="cancelEventBtn" class="btn btn-danger">Cancel Event</button>
        </form>
    `;

        }

        function setupEditEventListeners(eventData) {
            // Fetch states on page load
            fetchStatesAndCities(eventData);

            // Fetch cities when a state is selected
            document.getElementById('state').addEventListener('change', function() {
                const stateName = this.value;
                if (stateName) {
                    fetchCitiesForState(stateName);
                } else {
                    document.getElementById('city').innerHTML = '<option value="">Select a city</option>'; // Clear city options if no state is selected
                }
            });

            // After generating the form, set the selected city if a state is already chosen
            const selectedState = document.getElementById('state').value;
            if (selectedState) {
                displayCitiesForState(selectedState); // Fetch and display cities for the selected state
            }


            // Now, set the city to the original event data city
            const selectedCity = document.getElementById('city');
            const originalCity = eventData.city || ''; // This should reference the eventData provided earlier
            if (originalCity) {
                const cityOption = document.createElement('option');
                cityOption.value = originalCity;
                cityOption.textContent = originalCity;
                selectedCity.appendChild(cityOption); // Add the original city as a selectable option
                selectedCity.value = originalCity; // Set it as selected
            }

            document.getElementById('editEventForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const title = document.getElementById('title').value.trim();
                const description = document.getElementById('description').value.trim();
                const startDate = document.getElementById('startDate').value;
                const startTime = document.getElementById('start_time').value;
                const endTime = document.getElementById('end_time').value;
                const venue = document.getElementById('venue').value.trim();
                const city = document.getElementById('city').value.trim(); // Get selected city
                const state = document.getElementById('state').value.trim(); // Get selected state
                const volunteers = parseInt(document.getElementById('volunteers').value, 10); // Convert to integer
                const minAge = document.getElementById('minAge').value;
                const closeEventElement = document.querySelector('input[name="closeEvent"]:checked');
                const closeEvent = closeEventElement ? closeEventElement.value : '';
                // const closeEvent = document.querySelector('input[name="closeEvent"]:checked').value ? document.querySelector('input[name="closeEvent"]:checked').value : '';

                // Optional endDate field (only if its container is visible)
                const endDateContainer = document.getElementById('endDateContainer');
                let endDate = null;
                if (endDateContainer && endDateContainer.style.display !== 'none') {
                    endDate = document.getElementById('endDate').value;
                }


                // Optional goal field (only if its container is visible)
                const goalContainer = document.getElementById('goalContainer');
                let goal = null;
                if (goalContainer && goalContainer.style.display !== 'none') {
                    goal = document.getElementById('goal').value.trim();
                    if (!goal) {
                        showNotification('Please fill in all required fields.', 'error');
                        return; // Stop form submission if goal is empty
                    }
                }

                if (volunteers <= 0) {
                    showNotification('Volunteers must be greater than 0.', 'error');
                    return; // Stop form submission if volunteers is 0 or less
                }

                // Check if any required field is empty
                if (!title || !description || !startDate || !startTime || !endTime || !venue || !city || !state || !volunteers || !minAge || !closeEvent) {
                    showNotification('Please fill in all required fields.', 'error');
                    return; // Stop form submission if any field is empty
                }

                const formData = new FormData(this);

                // Collect selected skills and add to formData
                const selectedSkills = [];
                document.querySelectorAll('input[name="skills"]:checked').forEach((checkbox) => {
                    selectedSkills.push(checkbox.value);
                });
                const skillsString = selectedSkills.join(',');
                formData.append('skills', skillsString);

                // Get the poster file input element
                const posterInput = document.getElementById('upload-photo');

                // Check if a poster file was selected, and append it to the form data
                if (posterInput.files.length > 0) {
                    formData.append('eventPoster', posterInput.files[0]); // Append the poster file
                }

                // Log all the form data to the console
                for (let [key, value] of formData.entries()) {
                    console.log(`${key}: ${value}`);
                }
                fetch('server/update_event.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success === true) {
                            showNotification("Event updated successfully!", 'success');
                            // console.log("Debug Info:", data.debug);
                        } else {
                            showNotification(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the event.');
                    });
            });


            document.getElementById('cancelEventBtn').addEventListener('click', function() {
                const eventId = document.querySelector('input[name="event_id"]').value;
                if (confirm('Are you sure you want to cancel this event? This action cannot be undone.')) {
                    showOverlay();
                    const processingNotificationId = new Date().getTime();
                    showNotification("Processing...", "info", processingNotificationId);
                    fetch('server/cancel_event.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `event_id=${eventId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                showNotification("Event cancelled successfully! You will redirect to the Manage Event Page in 3 seconds", 'success');
                                hideNotification(processingNotificationId);
                                setTimeout(() => {
                                    window.location.href = 'manage_event.php';
                                }, 3000);
                            } else {
                                hideNotification(processingNotificationId);
                                hideOverlay();
                                showNotification(data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            hideOverlay();
                            alert('An error occurred while cancelling the event.');
                        });
                }
            });

            function fetchStatesAndCities(eventData) {
                const username = 'lawkaijian';
                const url = `https://secure.geonames.org/childrenJSON?geonameId=1733045&username=${username}`; // Malaysia's GeoName ID

                fetch(url)
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
                        });

                        // Set the selected state and populate cities based on eventData
                        if (eventData.state) {
                            stateDropdown.value = eventData.state;
                            fetchCitiesForState(eventData.state, eventData.city); // Fetch cities and set saved city
                        }
                    })
                    .catch(error => console.error('Error fetching states:', error));
            }

            function fetchCitiesForState(stateName, savedCity = '') {
                const username = 'lawkaijian';
                const stateCode = stateCodeMap[stateName];
                const url = `https://secure.geonames.org/searchJSON?country=MY&adminCode1=${stateCode}&featureClass=P&maxRows=1000&username=${username}`;

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        const cities = data.geonames || [];
                        const cityDropdown = document.getElementById('city');
                        cityDropdown.innerHTML = '<option value="">Select a city</option>';

                        cities.forEach(city => {
                            const option = document.createElement('option');
                            option.value = city.name;
                            option.textContent = city.name;
                            cityDropdown.appendChild(option);
                        });

                        // Set the selected city if a saved city exists
                        if (savedCity) {
                            cityDropdown.value = savedCity;
                        }
                    })
                    .catch(error => console.error('Error fetching cities:', error));
            }

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
            }

        }
    </script>
</body>

</html>