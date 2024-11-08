<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$amount = $_GET['amount'] ?? '';
$paymentMethod = $_GET['method'] ?? '';
$eventId = $_GET['event_id'] ?? '';

if (!$amount || !$paymentMethod || !$eventId) {
    echo "Invalid request.";
    exit();
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/notification.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .payment-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .payment-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .payment-method {
            font-weight: bold;
            color: #007bff;
        }

        .qr-code {
            display: block;
            margin: 2rem auto;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1rem;
            background-color: #ffffff;
        }

        .btn-primary {
            width: 100%;
        }

        .go-back-link {
            cursor: pointer;
            color: #6c757d;
            text-decoration: none;
            margin-top: 1rem;
            display: inline-block;
        }

        .go-back-link:hover {
            color: #007bff;
        }
    </style>
</head>

<body>
    <div id="notificationContainer"></div>
    <div id="overlay">
        <div class="overlay-message"></div>
    </div>
    <div class="container">
        <div class="payment-container">
            <div class="payment-header">
                <h2 class="mb-4">Process Payment</h2>
                <p class="lead">Amount: <span class="text-primary">RM <?php echo htmlspecialchars($amount); ?></span></p>
                <p>Payment Method: <span class="payment-method"><?php echo htmlspecialchars($paymentMethod); ?></span></p>
                <!-- <p>Payment Method: <span class="payment-method">Credit/Debit Card</span></p> -->

            </div>

            <form id="paymentForm">
                <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($eventId); ?>">
                <input type="hidden" name="donation_amount" value="<?php echo htmlspecialchars($amount); ?>">

                <?php if ($paymentMethod === 'TouchNGo'): ?>
                    <h3 class="text-center mb-4">Scan QR Code to Pay</h3>
                    <img src="img/qr_code.jpg" alt="QR Code" class="qr-code" style="max-width: -webkit-fill-available;">

                <?php elseif ($paymentMethod === 'Visa'): ?>
                    <h3 class="text-center mb-4">Enter Card Details</h3>
                    <div class="mb-3">
                        <label for="cardNumber" class="form-label">Name on Card</label>
                        <input type="text" class="form-control" id="cardNumber" name="nameOnCard" required>
                    </div>
                    <div class="mb-3">
                        <label for="cardNumber" class="form-label">Card Number</label>
                        <input type="text" class="form-control" id="cardNumber" name="cardNumber" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label for="expiryDate" class="form-label">Expiry Date</label>
                            <input type="text" class="form-control" id="expiryDate" name="expiryDate" placeholder="MM/YY" required>
                        </div>
                        <div class="col">
                            <label for="cvv" class="form-label">CVV</label>
                            <input type="text" class="form-control" id="cvv" name="cvv" required>
                        </div>
                    </div>
                <?php elseif ($paymentMethod === 'OnlineBanking'): ?>
                    <h3 class="text-center mb-4">Enter Online Banking Details</h3>
                    <div class="mb-3">
                        <label for="bankName" class="form-label">Bank Name</label>
                        <input type="text" class="form-control" id="bankName" name="bankName" required>
                    </div>
                    <div class="mb-3">
                        <label for="accountNumber" class="form-label">Account Number</label>
                        <input type="text" class="form-control" id="accountNumber" name="accountNumber" required>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">Pay Now</button>
            </form>

            <p id="goBack" class="go-back-link mt-4">
                <i class="fas fa-arrow-left"></i> Go back
            </p>
        </div>
    </div>

    <script>
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const processingNotificationId = new Date().getTime();
            const formData = new FormData(this);
            showNotification("Processing...", "info", processingNotificationId);
            showOverlay();
            fetch('server/process_donation.php', {
                    method: 'POST',
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        hideNotification(processingNotificationId);
                        showNotification('Donation successfull. Donation receipt is sent to your email. You will be redirect to event details page in 3 seconds.', 'success');
                        // Redirect to event details page after successful donation
                        setTimeout(() => {
                            window.location.href = 'event_detail.php?event_id=<?php echo $eventId; ?>';
                        }, 3000);
                    } else {
                        showNotification(data.message, 'error');
                        hideNotification(processingNotificationId);
                        hideOverlay();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred. Please try again.', 'error');
                    hideNotification(processingNotificationId);
                    hideOverlay();
                });
        });

        document.getElementById('goBack').addEventListener('click', function() {
            window.history.back();
        });

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

        function showOverlay() {
            document.getElementById('overlay').style.display = 'block';
        }

        function hideOverlay() {
            document.getElementById('overlay').style.display = 'none';
        }
    </script>
</body>

</html>