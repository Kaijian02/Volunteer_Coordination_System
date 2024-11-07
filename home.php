<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Home</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .home-button {
            margin: 30px auto 0 auto;
            width: 30%;
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

        .right-align-text {
            text-align: right;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <!-- <p>You are logged in as <?php echo $_SESSION['email']; ?></p> -->

    <section>
        <div class="container pt-3 pr-2 pl-4 pb-0 ps-md-5 pe-md-0 ml-md-5">
            <div class="row">
                <div class="col-12">
                    <h2 class="display-4" style="font-size:64px;">Create Your Own Events with </br>Voluntopia</h2>
                    <h3></h3>
                    <p style="font-size: 22px;">Easily find dedicated volunteers for your events whenever you need them.</p>
                </div>
            </div>
        </div>
        <div class="container py-4">
            <div class="row align-items-center">
                <div class="col-lg-7 mb-4 mb-lg-0">
                    <p class="lead">With the largest network of volunteers and events, Voluntopia brings together passionate people and meaningful causes.</p>
                    <button onclick="window.location.href='create_event.php'" class="home-button">Create Now</button>
                </div>
                <div class="col-lg-5 text-center">
                    <img src="img/recruit.jpeg" class="img-fluid rounded-circle" alt="Recruit Image" />
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="container text-center pt-5 pr-2 pl-4 pb-0 ps-md-5 pe-md-0 ml-md-5">
            <div class="row">
                <div class="col-12">
                    <h2 class="display-4" style="font-size:64px;">Explore Opportunities</h2>
                    <h3></h3>
                    <p style="font-size: 22px;">Discover a variety of volunteer events and find the perfect match for your skills and interests.</p>
                </div>
            </div>
        </div>
        <div class="container py-3">
            <div class="row align-items-center">
                <div class="col-lg-5 text-center mb-4">
                    <img src="img/recruit2.jpg" class="img-fluid rounded-circle" alt="Recruit Image" />
                </div>
                <div class="col-lg-6 text-center mb-4">
                    <p class="lead">Join a community dedicated to making a difference and start your journey of impactful volunteerism today.</p>
                    <button onclick="window.location.href='find_event_new.php'" class="home-button">Apply Now</button>
                </div>

            </div>
        </div>
    </section>

    <section style="padding-bottom: 30px;">
        <div class="container pt-3 pr-2 pl-4 pb-3 ps-md-5 pe-md-0 ml-md-5">
            <div class="row">
                <div class="col-12">
                    <h2 class="display-4 " style="font-size:64px;">Make a Monetary Donation</h2>
                    <h3></h3>
                    <p style="font-size: 22px;">Contribute financially to support impactful projects and causes.</p>
                </div>
            </div>
        </div>
        <div class="container py-4">
            <div class="row align-items-center">
                <div class="col-lg-7 mb-4 mb-lg-0">
                    <p class="lead">Your financial support helps drive essential initiatives, providing resources and assistance to those who need it most.</p>
                    <button type="submit" class="home-button">Donate Now</button>
                </div>
                <div class="col-lg-5 text-center">
                    <img src="img/donate.jpg" class="img-fluid rounded-circle" alt="Recruit Image" />
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'; ?>
</body>

</html>