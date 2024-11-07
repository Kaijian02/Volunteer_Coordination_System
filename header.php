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
        .navbar-nav {
            margin-left: auto;
            margin-right: auto;
        }

        .nav-item {
            padding: 10px;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .navbar-brand img {
            margin-right: 10px;
        }

        footer {
            padding: 50px;
            background-color: #eeeeee;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <a class="navbar-brand" href="home.php">
                    <img src="img/logo.png" alt="Company Logo" width="100" height="110" class="d-inline-block align-text-top">
                    Voluntopia
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="home.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="find_event_new.php">Find Event</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="create_event.php">Create Event</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_event.php">Manage Event</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">My Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="application_history.php">History</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="server/logout.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
</body>

</html>