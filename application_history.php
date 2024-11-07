<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application History</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/notification.css">
</head>

<body>
    <?php include 'header.php'; ?>
    <div id="notificationContainer"></div>
    <section style="padding-bottom: 30px; min-height:500px;">
        <div class="container mt-5">
            <h2>Application History</h2>

            <!-- Filter Options -->
            <div class="mb-4">
                <label for="statusFilter">Filter by Status:</label>
                <select id="statusFilter" class="form-control w-25">
                    <option value="Applying" selected>Applying</option>
                    <option value="Approved">Approved</option>
                    <option value="Cancelled">Cancelled</option>
                    <option value="Participated">Participated</option>
                    <option value="Rejected">Got Rejected</option>
                    <option value="Pending Cancellation">Pending Cancellation</option>
                </select>
            </div>

            <table class="table table-bordered">
                <thead id="applicationsTableHeader">
                    <!-- Header will be dynamically populated based on status -->
                </thead>
                <tbody id="applicationsTableBody">
                    <!-- Data will be dynamically populated here -->
                </tbody>
            </table>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusFilter = document.getElementById('statusFilter');

            // Fetch and display applications on page load with default status
            fetchApplications(statusFilter.value);

            // Fetch and display applications when the filter is changed
            statusFilter.addEventListener('change', function() {
                fetchApplications(statusFilter.value);
            });

            function fetchApplications(status) {
                // console.log('Fetching applications for status:', status); // Debugging
                const url = `server/fetch_application_history.php?status=${encodeURIComponent(status)}`;
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateTableHeader(status);
                            displayApplications(data.applications, status);
                        } else {
                            alert(data.message || 'Failed to load application history.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while fetching the application history.');
                    });
            }

            function updateTableHeader(status) {
                const tableHeader = document.getElementById('applicationsTableHeader');
                tableHeader.innerHTML = ''; // Clear existing headers

                // Add common headers
                let headerRow = `
                <tr>
                    <th>Event Name</th>
                    <th>Status</th>
                    <th>Applied Date</th>
            `;

                // Conditionally add headers based on status
                if (status === 'Cancelled') {
                    headerRow += `<th>Cancelled Date</th>`;
                    headerRow += `<th>Reason</th>`;
                } else if (status === 'Approved') {
                    headerRow += `<th>Approval Date</th>`;
                    headerRow += `<th>Action</th>`;
                } else if (status === 'Participated') {
                    headerRow += `<th>Start Date</th>`;
                    headerRow += `<th>End Date</th>`;
                } else if (status === 'Applying') {
                    headerRow += `<th>Action</th>`;
                } else if (status === 'Pending Cancellation') {
                    headerRow += `<th>Date Sent</th>`;
                    headerRow += `<th>Reason</th>`;
                    headerRow += `<th>Evidence Uploaded</th>`;
                } else if (status === 'Rejected') {
                    headerRow += `<th>Rejected Date</th>`;
                    headerRow += `<th>Reason</th>`;
                }

                headerRow += `</tr>`;
                tableHeader.innerHTML = headerRow;
            }

            function displayApplications(applications, status) {
                const tableBody = document.getElementById('applicationsTableBody');
                tableBody.innerHTML = ''; // Clear the table before populating

                applications.forEach(application => {
                    let row = `
                <tr>
                    <td>${application.event_name}</td>
                    <td>${application.status}</td>
                    <td>${application.applied_date || 'N/A'}</td>
            `;

                    // Conditionally display data based on status
                    if (status === 'Cancelled') {
                        row += `<td>${application.cancelled_date || 'N/A'}</td>`;
                        row += `<td>${application.reason || 'N/A'}</td>`;
                    } else if (status === 'Approved') {
                        row += `<td>${application.approval_date || 'N/A'}</td>`;
                        if (application.can_cancel) {
                            if (application.requires_reason) {
                                // Show input field for reason and a button to submit cancellation request
                                row += `<td>
                                            <input type="text" id="reason_${application.id}" placeholder="Provide a reason" class="form-control" />
                                            <input type="file" id="evidence_${application.id}" accept=".pdf, .jpg, .jpeg, .png" class="form-control mt-2" />
                                            <button class="btn btn-danger btn-sm mt-2" onclick="cancelWithReason(${application.id})">Request Cancellation</button>
                                            <span class="text-muted">${application.cancel_message}</span>
                                        </td>`;
                            } else {
                                // Allow direct cancellation
                                row += `<td><button class="btn btn-danger btn-sm" onclick="cancelApplication(${application.id})">Cancel</button></td>`;
                            }
                        } else {
                            row += `<td><span class="text-muted">${application.cancel_message}</span></td>`;
                        }
                    } else if (status === 'Participated') {
                        row += `<td>${application.start_date || 'N/A'}</td>`;
                        row += `<td>${application.end_date || 'N/A'}</td>`;
                    } else if (status === 'Applying') {
                        row += `<td><button class="btn btn-danger btn-sm" onclick="cancelApplication(${application.id})">Cancel</button></td>`;
                    } else if (status === 'Pending Cancellation') {
                        row += `<td>${application.pending_cancelled_date}</td>`
                        row += `<td>${application.reason}</td>`
                        row += `<td>${getFileDisplayElement(application.evidence)}</td>`;
                    } else if (status === 'Rejected') {
                        row += `<td>${application.rejected_date}</td>`
                        row += `<td>${application.reason}</td>`
                    }

                    row += `</tr>`;
                    tableBody.innerHTML += row; // Append each row to the table body
                });
            }

            // Function to handle the cancel button click
            window.cancelApplication = function(applicationId) {
                if (confirm('Are you sure you want to cancel this application?')) {
                    fetch(`server/cancel_application.php?id=${applicationId}`, {
                            method: 'POST'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // alert('Application has been successfully cancelled.');
                                showNotification(data.message, 'success');
                                fetchApplications(statusFilter.value); // Refresh the table
                            } else {
                                // alert(data.message || 'Failed to cancel the application.');
                                showNotification(data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while cancelling the application.');
                        });
                }
            };

            // Function to handle cancellation with reason
            window.cancelWithReason = function(applicationId) {
                const reason = document.getElementById(`reason_${applicationId}`).value;
                const evidence = document.getElementById(`evidence_${applicationId}`).files[0];
                if (!reason.trim()) {
                    showNotification("Please provide a reason for the cancellation.", "error");
                    return;
                }
                if (confirm('Are you sure you want to request cancellation?')) {
                    const formData = new FormData();
                    formData.append('id', applicationId);
                    formData.append('reason', reason);
                    if (evidence) {
                        formData.append('evidence', evidence); // Add file to the form data
                    }
                    fetch(`server/cancel_application.php?id=${applicationId}&reason=${encodeURIComponent(reason)}`, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification(data.message, 'success');
                                fetchApplications(statusFilter.value); // Refresh the table
                            } else {
                                showNotification(data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while requesting the cancellation.');
                        });
                }
            };
        });

        function getFileDisplayElement(filePath) {
            if (!filePath) return 'No file uploaded';

            const fileExtension = filePath.split('.').pop().toLowerCase();
            const fileName = filePath.split('/').pop();

            if (fileExtension === 'pdf') {
                return `<a href="${filePath}" target="_blank" class="btn btn-sm btn-primary">View PDF</a>`;
            } else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
                return `<a href="${filePath}" target="_blank" class="btn btn-sm btn-info">View Image</a>`;
            } else {
                return `<a href="${filePath}" target="_blank" class="btn btn-sm btn-secondary">View File</a>`;
            }
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
    </script>
</body>

</html>