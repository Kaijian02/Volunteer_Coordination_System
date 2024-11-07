<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/notification.css">
    <style>
        .step {
            display: none;
        }

        .step.active {
            display: block;
        }

        .step.completed {
            display: block;
        }

        .step-progressbar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .step-progressbar .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            line-height: 40px;
            text-align: center;
            font-weight: bold;
            color: #6c757d;
        }

        .step-progressbar .step-circle.active,
        .step-progressbar .step-circle.completed {
            background-color: #007bff;
            color: white;
        }

        .step-progressbar .step-line {
            flex: 1;
            height: 2px;
            background-color: #e9ecef;
            margin-top: 20px;
        }

        .step-progressbar .step-line.active,
        .step-progressbar .step-line.completed {
            background-color: #007bff;
        }

        .button-group {
            margin-top: 20px;
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

        .summary-container {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            max-width: 700px;
            font-family: Arial, sans-serif;
            margin: auto;
        }

        .summary-row {
            display: flex;
            flex-wrap: wrap;
            padding: 5px 0;
            border-bottom: 1px solid #eaeaea;
        }

        .summary-item {
            flex: 1;
            margin-right: 20px;
            font-size: 16px;
            line-height: 1.6;
            color: #333;
        }

        .poster-column {
            flex: 1 0 30%;
            /* max-width: 200px; */
            margin-right: 20px;
        }

        .summary-details {
            flex: 2 0 65%;
            display: flex;
            flex-direction: column;
        }

        .summary-container strong {
            color: #0056b3;
        }

        .summary-poster {
            max-width: 100%;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <div id="notificationContainer"></div>
    <section style="padding-bottom: 30px; min-height:500px;">
        <div class="container mt-5">
            <div class="step-progressbar">
                <div class="step-circle active">1</div>
                <div class="step-line"></div>
                <div class="step-circle">2</div>
                <div class="step-line"></div>
                <div class="step-circle">3</div>
                <div class="step-line"></div>
                <div class="step-circle">4</div>
                <div class="step-line"></div>
                <div class="step-circle">5</div>
                <div class="step-line"></div>
                <div class="step-circle">6</div>
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

            <form id="stepForm">
                <!-- Step 1 -->
                <div class="step active">
                    <h4>Step 1: About Event</h4>
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="Enter the title of the event" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <!-- <textarea type="description" class="form-control" id="description" name="description" required> -->
                        <textarea class="form-control" id="description" style="resize: none;" rows="8" name="description" placeholder="Enter a brief description about your event. (1 sentence) " required></textarea>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="step">
                    <h4>Step 2: Date/Time & Venue</h4>
                    <div class="form-group">
                        <label for="isOneDay">Is this an one day event?</label>
                        <div class="form-check">
                            <input type="radio" id="isOneDayYes" name="isOneDay" value="Yes">
                            <label for="oneDayYes">Yes</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" id="isOneDayNo" name="isOneDay" value="No">
                            <label for="oneDayNo">No</label>
                        </div>
                        <span class="error-msg" id="oneDayError" style="color:red;display:none;">Please select an option.</span>
                    </div>
                    <div class="form-group">
                        <label for="startDate">Start Date:</label>
                        <input type="date" class="form-control" id="startDate" name="startDate" required>
                    </div>
                    <div class="form-group" id="endDateContainer">
                        <label for="endDate">End Date:</label>
                        <input type="date" class="form-control" id="endDate" name="endDate" disabled>
                    </div>
                    <div class="form-group">
                        <label for="start_time">Start Time:</label>
                        <input type="time" class="form-control" id="start_time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="end_time">End Time:</label>
                        <input type="time" class="form-control" id="end_time" name="end_time" required>
                    </div>
                    <div class="form-group">
                        <label for="venue">Venue</label>
                        <input type="text" class="form-control" id="venue" name="venue" required>
                    </div>
                    <!-- <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" class="form-control" id="city" name="city" required>
                    </div> -->
                    <div class="form-group">
                        <label for="state">State</label>
                        <select id="state" name="state" class="form-control" required>
                            <option value="">Select a state</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <select id="city" name="city" class="form-control" required>
                            <option value="">Select a city</option>
                        </select>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="step">
                    <h4>Step 3: Details/Skills & Requirements</h4>
                    <div class="form-group">
                        <label for="volunteers">Estimated Volunteer Needed</label>
                        <input type="number" class="form-control" id="volunteers" name="volunteers" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="closeEvent">Close the event once total number of estimated volunteers needed is met?</label>
                        <div class="form-check">
                            <input type="radio" id="closeEventYes" name="closeEvent" value="Yes">
                            <label for="closeEventYes">Yes</label><br>
                        </div>
                        <div class="form-check">
                            <input type="radio" id="closeEventNo" name="closeEvent" value="No">
                            <label for="closeEventNo">No</label>
                        </div>
                        <span class="error-msg" id="closeEventError" style="color:red;display:none;">Please select an option.</span>
                    </div>

                    <div class="form-group">
                        <label for="skills">Skills</label>
                        <button id="edit-skills" type="button" style="border: none; background: white;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <!-- <span class="error-msg" id="skillsError" style="color:red;display:none;">Please select at least one skill.</span> -->
                        <div id="selected-skills"></div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="step">
                    <h4>Step 4: Requirements & Donation</h4>
                    <div class="form-group">
                        <label for="minAge">Minimum Age:</label>
                        <input type="number" class="form-control" id="minAge" name="minAge" required>
                    </div>
                    <div class="form-group">
                        <label for="comments">Additional Requirements:</label>
                        <textarea class="form-control" id="comments" name="comments"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="donation">Is this event needs donation?</label>
                        <div class="form-check">
                            <input type="radio" id="donationYes" name="donation" value="Yes">
                            <label for="donationYes">Yes</label><br>
                        </div>
                        <div class="form-check">
                            <input type="radio" id="donationNo" name="donation" value="No">
                            <label for="donationNo">No</label>
                        </div>
                        <span class="error-msg" id="donationError" style="color:red;display:none;">Please select an option.</span>
                    </div>
                    <div class="form-group" id="goalContainer" style="display: none;">
                        <label for="goal">Set Goal:</label>
                        <input type="number" class="form-control" id="goal" name="goal">
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="step">
                    <h4>Last Step: Upload an Event Poster</h4>
                    <div class="form-group" style="display: flex; flex-direction:column">
                        <div id="cropped-result" style="margin: auto;">
                            <img src="img/upload-photo.jpg" alt="Default Image">
                        </div>
                        <!-- Button to change profile -->
                        <button id="upload-poster-btn" type="button" class="btn btn-primary mt-3" style="margin: auto;">Upload Poster</button>
                        <input type="file" id="upload-photo" name="posterImage" accept="image/*" style="display: none;">
                    </div>
                </div>

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



                <!-- Step 5 -->
                <div class="step">
                    <h4>Step 6: Confirm Information</h4>
                    <div id="summary" class="summary-container"></div>
                </div>

                <!-- Navigation buttons -->
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" id="prevBtn" onclick="nextPrev(-1)">Previous</button>
                    <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextPrev(1)">Next</button>
                    <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">Submit</button>
                </div>
            </form>
        </div>
    </section>
    <?php include 'footer.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script>
        let currentStep = 0; // Current step is set to be the first step (0)
        showStep(currentStep); // Display the current step

        function showStep(n) {
            let steps = document.getElementsByClassName("step");
            let circles = document.getElementsByClassName("step-circle");
            let lines = document.getElementsByClassName("step-line");

            // Debugging: Check if elements are being retrieved correctly
            console.log('Steps:', steps);
            console.log('Circles:', circles);
            console.log('Lines:', lines);

            // Hide all steps
            for (let i = 0; i < steps.length; i++) {
                steps[i].style.display = "none";
            }

            // Show the current step
            if (steps[n]) {
                steps[n].style.display = "block";
            } else {
                console.error('Step not found:', n);
            }

            // Update step circles and lines
            for (let i = 0; i < circles.length; i++) {
                if (circles[i]) {
                    if (i < n) {
                        circles[i].classList.add("completed");
                        if (lines[i]) {
                            lines[i].classList.add("completed");
                        }
                    } else {
                        circles[i].classList.remove("completed");
                        if (lines[i]) {
                            lines[i].classList.remove("completed");
                        }
                    }

                    if (i === n) {
                        circles[i].classList.add("active");
                    } else {
                        circles[i].classList.remove("active");
                    }
                } else {
                    console.error('Circle not found:', i);
                }
            }

            // Show/hide navigation buttons
            document.getElementById("prevBtn").style.display = n === 0 ? "none" : "inline"; // Hide it at the first step
            document.getElementById("nextBtn").style.display = n === (steps.length - 1) ? "none" : "inline"; // Hide it at the last step
            document.getElementById("submitBtn").style.display = n === (steps.length - 1) ? "inline" : "none";

            // Display summary if on the last step
            if (n === (steps.length - 1)) {
                displaySummary();
            }
        }

        function nextPrev(n) {
            // Get all steps
            let steps = document.getElementsByClassName("step");

            // Get the current step element
            let currentStepElement = steps[currentStep];

            // If moving forward (n > 0), validate the current step
            if (n > 0) {
                let isValid = true;

                // General validation for all steps
                let inputs = currentStepElement.querySelectorAll('input, textarea, select');

                inputs.forEach((input) => {
                    if (input.type === 'radio') {
                        // Validate radio button groups
                        let name = input.name;
                        let radioGroup = document.querySelectorAll(`input[name="${name}"]`);
                        let isChecked = Array.from(radioGroup).some(radio => radio.checked);

                        // Check if the radio group is the "isOneDay" group
                        if (name === "isOneDay") {
                            if (isChecked) {
                                document.getElementById('oneDayError').style.display = 'none';
                            } else {
                                isValid = false;
                                document.getElementById('oneDayError').style.display = 'inline';
                            }
                        }

                        // Check if the radio group is the "closeEvent" group
                        if (name === "closeEvent") {
                            if (isChecked) {
                                document.getElementById('closeEventError').style.display = 'none';
                            } else {
                                isValid = false;
                                document.getElementById('closeEventError').style.display = 'inline';
                            }
                        }
                    } else if (input.tagName === 'SELECT' && input.hasAttribute('required') && !input.value) {
                        // Validate select dropdowns like state and city
                        isValid = false;
                        input.style.borderColor = 'red';
                    } else if (input.hasAttribute('required') && !input.value) {
                        isValid = false;
                        input.style.borderColor = 'red';
                    } else {
                        input.style.borderColor = '';
                    }
                });

                // Specific validation for Step 3 (skills selection)
                // if (currentStep === 2) { // Assuming Step 3 is index 2
                //     if (selectedSkills.length > 0) {
                //         document.getElementById('skillsError').style.display = 'none';
                //     } else {
                //         isValid = false;
                //         document.getElementById('skillsError').style.display = 'inline';
                //     }
                // }


                // Specific validation for Step 4
                if (currentStep === 3) { // Assuming Step 4 is index 3
                    let donationInput = document.querySelector('input[name="donation"]:checked');
                    let goalInput = document.querySelector('input[name="goal"]');

                    if (donationInput) {
                        document.getElementById('donationError').style.display = 'none';
                        if (donationInput && donationInput.value === "Yes") {
                            if (!goalInput.value) {
                                isValid = false;
                                goalInput.style.borderColor = 'red';
                            } else {
                                goalInput.style.borderColor = '';
                            }
                        } else if (donationInput && donationInput.value === "No") {
                            goalInput.style.borderColor = ''; // Clear any error highlighting
                        }
                    } else {
                        document.getElementById('donationError').style.display = 'inline';
                        isValid = false;
                    }
                }

                // Specific validation for Step 5 (image upload)
                if (currentStep === 4) { // Assuming Step 5 is index 4
                    if (uploadPhoto.files.length === 0) {
                        isValid = false;
                        // alert('Please upload an event poster before proceeding.');
                        showNotification('Please upload an event poster before proceeding.', 'error');
                    }
                }

                // If the current step is not valid, do not proceed to the next step
                if (!isValid) {
                    return false;
                }

            }

            // Hide the current step
            if (steps[currentStep]) {
                steps[currentStep].style.display = "none";
            }

            // Update currentStep to the new step
            currentStep = currentStep + n;

            // If the new step is within the valid range, show it
            if (currentStep >= 0 && currentStep < steps.length) {
                showStep(currentStep);
            }
        }


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

        // Hide end date if one day event
        const isOneDayYes = document.getElementById('isOneDayYes');
        const isOneDayNo = document.getElementById('isOneDayNo');
        const endDateContainer = document.getElementById('endDateContainer');
        const endDateInput = document.getElementById('endDate');
        const startDateInput = document.getElementById('startDate');

        // Function to toggle the visibility of the end date input
        function toggleEndDateVisibility() {
            if (isOneDayYes.checked) {
                endDateContainer.style.display = 'none';
                endDateInput.value = '';
                endDateInput.removeAttribute('required');
            } else {
                endDateContainer.style.display = 'block';
                endDateInput.setAttribute('required', true);
            }
        }

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


        // Ensure the start date is at least one week after today
        function setMinStartDate() {
            const today = new Date();
            today.setDate(today.getDate() + 7); // Set to one week from today
            const minDate = today.toISOString().split('T')[0]; // Format to YYYY-MM-DD
            startDateInput.setAttribute('min', minDate);
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

        isOneDayYes.addEventListener('change', toggleEndDateVisibility);
        isOneDayNo.addEventListener('change', toggleEndDateVisibility);
        startDateInput.addEventListener('change', function() {
            setEndDateMin(); // Update end date min value based on start date
        });
        endDateInput.addEventListener('change', validateDates);

        setMinStartDate(); // Set the minimum start date
        toggleEndDateVisibility();
        setEndDateMin(); // Set the minimum selectable end date



        // Time
        const startTimeInput = document.getElementById('start_time');
        const endTimeInput = document.getElementById('end_time');

        function setEndTimeMin() {
            if (startTimeInput.value) {
                // const minEndTime = startTimeInput;
                endTimeInput.min = startTimeInput.value;
                endTimeInput.disabled = false;
                // endTimeInput = minEndTime;
            } else {
                endTimeInput.min = '';
                endTimeInput.disabled = true;
            }
        }

        // Function to validate the end time
        function validateEndTime() {
            if (endTimeInput.value && startTimeInput.value) {
                if (endTimeInput.value <= startTimeInput.value) {
                    showNotification("End Time must be later than Start Time", "error");
                    endTimeInput.value = ''; // Clear the end time input
                }
            }
        }

        // Initial setup: Disable the end time input until a start time is selected
        endTimeInput.disabled = true;
        startTimeInput.addEventListener('change', setEndTimeMin);
        // Add event listener for when the end time changes
        endTimeInput.addEventListener('change', validateEndTime);



        // Select Skills
        const skillsModal = document.getElementById('skillsModal');
        const editSkillsButton = document.getElementById('edit-skills');
        const closeSkillsModal = document.querySelector('.close-skills');
        const selectedSkillsDiv = document.getElementById('selected-skills');

        editSkillsButton.addEventListener('click', () => {
            skillsModal.style.display = 'block';
        });

        closeSkillsModal.addEventListener('click', () => {
            skillsModal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target == skillsModal) {
                skillsModal.style.display = 'none';
            }
        });

        // Store selected skills globally
        let selectedSkills = [];

        document.getElementById('save-skills').addEventListener('click', (e) => {
            e.preventDefault(); // Prevent default button action

            // Clear the array and get the selected skills
            selectedSkills = [];
            document.querySelectorAll('input[name="skills"]:checked').forEach((checkbox) => {
                selectedSkills.push(checkbox.value);
            });

            // Update the UI with selected skills
            selectedSkillsDiv.innerHTML = selectedSkills.map(skill => `<span class="badge bg-primary me-1">${skill}</span>`).join('');

            // Close the modal
            skillsModal.style.display = 'none';
        });


        // Donation
        const donationYes = document.getElementById('donationYes');
        const donationNo = document.getElementById('donationNo');
        const goalContainer = document.getElementById('goalContainer');
        const goalValue = document.getElementById('goal');

        // Function to toggle visibility
        function toggleGoalVisibility() {
            if (donationYes.checked) {
                goalContainer.style.display = 'block';
            } else {
                goalValue.value = '';
                goalContainer.style.display = 'none';
            }
        }

        // Add event listeners to radio buttons
        donationYes.addEventListener('change', toggleGoalVisibility);
        donationNo.addEventListener('change', toggleGoalVisibility);


        // Check min volunteer needed
        const volunteersInput = document.getElementById('volunteers');

        volunteersInput.addEventListener('input', function() {
            // Ensure the value is always 1 or greater
            if (this.value < 1) {
                this.value = 1; // Reset the value to 1 if less than 1
            }
        });

        // Initial check to ensure correct visibility
        toggleGoalVisibility();

        function displaySummary() {
            const title = document.querySelector('#title').value;
            const description = document.querySelector('#description').value;
            const startDate = document.querySelector('#startDate').value;
            const endDate = document.querySelector('#endDate').value;
            const start_time = document.querySelector('#start_time').value;
            const end_time = document.querySelector('#end_time').value;
            const venue = document.querySelector('#venue').value;
            const volunteers = document.querySelector('#volunteers').value;
            const closeEvent = document.querySelector('input[name="closeEvent"]:checked').value ? document.querySelector('input[name="closeEvent"]:checked').value : '';
            const donation = document.querySelector('input[name="donation"]:checked').value ? document.querySelector('input[name="donation"]:checked').value : '';
            const minAge = document.querySelector('#minAge').value;
            const comments = document.querySelector('#comments').value;
            const goal = document.querySelector('#goal').value;
            const city = document.querySelector('#city').value;
            const state = document.querySelector('#state').value;
            const commentsText = comments ? comments : 'None';

            // console.log('Title:', title); // Debugging
            // console.log('Description:', description); // Debugging
            // console.log('Start Date:', startDate); // Debugging
            // console.log('End Date:', endDate); // Debugging
            // console.log('Start Time:', start_time); // Debugging
            // console.log('End Time:', end_time); // Debugging
            // console.log('Venue:', venue); // Debugging
            // console.log('City:', city); // Debugging
            // console.log('State:', state); // Debugging
            // console.log('Volunteers Needed:', volunteers); // Debugging
            // console.log('Close Event:', closeEvent); // Debugging
            // console.log('Minimum Age:', minAge); // Debugging
            // console.log('Comments:', comments); // Debugging
            // console.log('Donation:', donation); // Debugging
            // console.log('Goal:', goal); // Debugging
            // console.log('Selected Skills:', selectedSkills.join(', ')); // Debugging

            let summary = `
        <div class="summary-row">
            <div class="summary-item"><strong>Title:</strong> ${title}</div>      
        </div>
         <div class="summary-row">      
             <div class="summary-item"><strong>Description:</strong> ${description}</div>
        </div>
        <div class="summary-row">
           <div class="summary-item"><strong>Start Date:</strong> ${startDate}</div>
            <div class="summary-item"><strong>End Date:</strong> ${endDate}</div>
        </div>
        <div class="summary-row">
            <div class="summary-item"><strong>Start Time:</strong> ${start_time}</div>
            <div class="summary-item"><strong>End Time:</strong> ${end_time}</div>
        </div>
        <div class="summary-row">
            <div class="summary-item"><strong>Venue:</strong> ${venue}</div>
        </div>
         <div class="summary-row">
           <div class="summary-item"><strong>State:</strong> ${state}</div>
             <div class="summary-item"><strong>City:</strong> ${city}</div>
        </div>
        <div class="summary-row">
            <div class="summary-item"><strong>Volunteers Needed:</strong> ${volunteers}</div>
            <div class="summary-item"><strong>Close Event:</strong> ${closeEvent}</div>
        </div>
        <div class="summary-row">
            <div class="summary-item"><strong>Minimum Age:</strong> ${minAge}</div>
            <div class="summary-item"><strong>Donation:</strong> ${donation}</div>
        </div>
        <div class="summary-row">
            <div class="summary-item"><strong>Goal:</strong> ${goal}</div>
            <div class="summary-item"><strong>Additional Requirements:</strong> ${commentsText}</div>
        </div>
        <div class="summary-row">
            <div class="summary-item"><strong>Skills:</strong> ${selectedSkills.join(', ')}</div>
        </div>
    `;

            if (posterBlob) {
                const url = URL.createObjectURL(posterBlob);
                summary = `
        <div class="summary-row">
            <div class="summary-item poster-column">
                <strong>Event Poster:</strong><br>
                <img src="${url}" alt="Event Poster" class="summary-poster">
            </div>
            <div class="summary-details">
                ${summary}
            </div>
        </div>`;
            }
            document.getElementById('summary').innerHTML = summary;
        }


        document.getElementById('submitBtn').addEventListener('click', function(event) {
            console.log('Submit button clicked');
            submitEvent(event);
        });

        function submitEvent(event) {
            event.preventDefault(); // Prevent default form submission
            console.log('Form submitted'); // Debugging log

            // Collect form data
            const formData = new FormData(document.getElementById('stepForm'));

            // Log all the form data collected
            // for (const [key, value] of formData.entries()) {
            //     console.log(`${key}: ${value}`);
            // }

            // Append cropped image blob to formData
            if (posterBlob) {
                const posterFile = new File([posterBlob], 'eventPoster.jpg', {
                    type: 'image/jpeg'
                });
                formData.append('eventPoster', posterFile);
            }

            // Collect selected skills and add to formData
            const selectedSkills = [];
            document.querySelectorAll('input[name="skills"]:checked').forEach((checkbox) => {
                selectedSkills.push(checkbox.value);
            });
            const skillsString = selectedSkills.join(',');
            formData.append('skills', skillsString);

            // Get the one-day event value
            const isOneDay = document.querySelector('input[name="isOneDay"]:checked')?.value || 'No';
            formData.append('isOneDay', isOneDay);

            fetch('server/save_event.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // console.log('Response JSON:', data); // Log the response text
                    try {
                        if (data.success) {
                            alert('Event created successfully!');
                            window.location.href = 'find_event_new.php';
                        } else {
                            alert('Failed to create event: ' + data.message);
                        }
                    } catch (e) {
                        console.error('Failed to parse JSON:', e);
                        alert('Failed to parse response as JSON.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while saving the event.');
                });
        }

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
                notificationBox.style.backgroundColor = '#28a745';
            } else if (type === 'error') {
                notificationBox.style.backgroundColor = '#dc3545';
            } else if (type === 'info') {
                notificationBox.style.backgroundColor = '#17a2b8';
            }

            notificationBox.innerText = message;

            // Append the new notification at the top
            notificationContainer.prepend(notificationBox);

            // Fade in
            setTimeout(() => {
                notificationBox.classList.add('show');
            }, 10);


            setTimeout(() => {
                hideNotification(notificationId);
            }, 3000);
        }

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

        let stateCodeMap = {};
        let allCities = {};
        document.addEventListener('DOMContentLoaded', function() {
            // Fetch states on page load
            fetchStatesAndCities();

            // Fetch cities when a state is selected
            document.getElementById('state').addEventListener('change', function() {
                const stateName = this.value;
                if (stateName) {
                    displayCitiesForState(stateName); // Display cities for the selected state
                } else {
                    document.getElementById('city').innerHTML = '<option value="">Select a city</option>'; // Clear city options if no state is selected
                }
            });
        });

        function fetchStatesAndCities() {
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

                        // Fetch cities for each state and store them
                        fetchCitiesForState(state.adminName1);
                    });
                })
                .catch(error => console.error('Error fetching states:', error));
        }

        function fetchCitiesForState(stateName) {
            const username = 'lawkaijian';
            const stateCode = stateCodeMap[stateName]; // Get state code from the map
            const url = `https://secure.geonames.org/searchJSON?country=MY&adminCode1=${stateCode}&featureClass=P&maxRows=1000&username=${username}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const cities = data.geonames || [];
                    allCities[stateName] = cities; // Store cities for the state
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
    </script>

</body>

</html>