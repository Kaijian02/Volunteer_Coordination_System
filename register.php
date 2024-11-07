<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['name'];
    $email = $_POST['email'];
    $dob = $_POST['dob'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmpassword'];
    $dateJoined = (new DateTime())->format('j F Y');
    $eventJoined = 0;
    $eventCreated = 0;

    if ($password !== $confirmPassword) {
        $messageRed = "Passwords do not match. Please try again.";
    } elseif (!preg_match('/^(?=.*[a-zA-Z])(?=.*[\W_]).{6,}$/', $password)) {
        $messageRed = "Password must be at least 6 characters long and contain at least one letter and one symbol.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(50)); // Generate a unique token
        $isVerified = 0; // User is not verified initially

        // Database connection
        $conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Check if email already exists
        $sql = "SELECT id, is_verified, token FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $is_verified, $existing_token);
        $stmt->fetch();
        $emailExists = $stmt->num_rows > 0; // Check if email exists
        $isEmailVerified = $is_verified == 1; // Check if the existing email is verified
        $stmt->close(); // Close the statement before the next query

        if ($emailExists && !$isEmailVerified) {
            // Resend verification email
            sendVerificationEmail($email, $existing_token);
            $messageRed = "Your account is not verified. A new verification email has been sent.";
        } elseif ($emailExists) {
            $messageRed = " Email already exist. Please use different email.";
        } else {
            // Insert user into database
            // $stmt->close(); // Close the previous statement
            $sql = "INSERT INTO users (name, email, dob, state, city, password, token, is_verified, date_joined, event_joined, event_created, credit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssssii", $username, $email, $dob, $state, $city, $hashedPassword, $token, $isVerified, $dateJoined, $eventJoined, $eventCreated, $defaultCredit);
            $defaultCredit = 100;
            if ($stmt->execute()) {
                // Send verification email
                sendVerificationEmail($email, $token);
                $message = "Registration successful! Verification email has been sent.";
            } else {
                $message = "Error: " . $sql . "<br>" . $conn->error;
            }
            $stmt->close();
        }

        // $stmt->close();
        $conn->close();
    }
}


function sendVerificationEmail($email, $token)
{
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jerrylaw02@gmail.com';
        $mail->Password   = 'zijexuiygafhswks';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('jerrylaw02@gmail.com', 'Volunteer Coordination System');
        $mail->addAddress($email);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification';
        $mail->Body    = '
        <html>
        <head>
            <title>Email Verification</title>
        </head>
        <body>
            <p>Click the link below to verify your email address:</p>
            <a href="http://localhost/VolunteerCoordinationSystem/verification.php?token=' . $token . '">Verify Email</a>
        </body>
        </html>
        ';

        $mail->send();
    } catch (Exception $e) {
        global $message;
        $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>


<!DOCTYPE html>
<html>

<head>
    <title>Volunteer Coordination System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/login.css">
    <script>
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

        function togglePasswordVisibility() {
            var passwordField = document.getElementById('password');
            var confirmpasswordField = document.getElementById('confirmpassword');
            var eyeIcon = document.getElementById('eye-icon');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                confirmpasswordField.type = 'text';
                eyeIcon.textContent = 'visibility';
            } else {
                passwordField.type = 'password';
                confirmpasswordField.type = 'password';
                eyeIcon.textContent = 'visibility_off';
            }
        }
    </script>
</head>

<body>


    <div class="container d-flex justify-content-center align-items-center">
        <div class="col-sm-6" style="background-color: #ececec; padding:40px; border-radius: 20px;">
            <h2>Register</h2>
            <p>Let's get you all set up so you can access your personal account.</p>
            <form method="post" action="">
                <div class="form__group field">
                    <input type="text" class="form__field" placeholder="Name" name="name" id='name' required />
                    <label for="Name" class="form__label">Name</label>
                </div>
                <div class="form__group field">
                    <input type="email" class="form__field" placeholder="Email" name="email" id='email' required />
                    <label for="Email" class="form__label">Email</label>
                </div>
                <div class="form__group field" style="padding-top: 15px; margin-top:20px;">
                    <input type="date" class="form__field" name="dob" id="dob" required />
                    <label for="dob" class="form__label">Date of Birth</label>
                </div>
                <div class="form__group field" style="padding-top: 15px; margin-top:20px;">
                    <select id="state" name="state" class="form__field" required>
                        <option value="">Select a state</option>
                    </select>
                    <label for="state" class="form__label">Your State</label>
                </div>
                <div class="form__group field" style="padding-top: 15px; margin-top:20px;">
                    <select id="city" name="city" class="form__field" required>
                        <option value="">Select a city</option>
                    </select>
                    <label for="city" class="form__label">Your City</label>
                </div>
                <div class="form__group field">
                    <input type="password" class="form__field" placeholder="Password" name="password" id='password' required />
                    <label for="password" class="form__label">Password</label>
                    <span class="toggle-password" onclick="togglePasswordVisibility()">
                        <span class="material-symbols-outlined" id="eye-icon">visibility_off</span>
                    </span>
                </div>
                <div class="form__group field">
                    <input type="password" class="form__field" placeholder="confirmPassword" name="confirmpassword" id='confirmpassword' required />
                    <label for="confirmpassword" class="form__label">Confirm Password</label>
                </div>
                <?php
                if (!empty($message)) {
                    echo "<p style='color: green; font-weight: bold; margin-top:5px;'>$message</p>";
                }
                if (!empty($messageRed)) {
                    echo "<p style='color: red; font-weight: bold; margin-top: 5px;'>$messageRed</p>";
                }
                ?>
                <input type="submit" class="login-button" value="Register">
            </form>

            <p style="text-align: center; margin-top:30px;">Already have an account? <a href="login.php"><u>Login</u></a></p>
        </div>
    </div>
</body>

</html>