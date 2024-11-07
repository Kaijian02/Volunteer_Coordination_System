<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Event</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <?php include 'header.php'; ?>

    <section style="padding-bottom: 30px; min-height:500px;">
        <div class="container mt-5">
            <h2>Manage Event</h2>

            <!-- Filter Options -->
            <div class="mb-4">
                <label for="eventFilter">Filter Events:</label>
                <select id="eventFilter" class="form-control w-25">
                    <option value="ongoing" selected>Ongoing Events</option>
                    <option value="upcoming">Upcoming Events</option>
                    <option value="passed">Passed Events</option>
                    <option value="cancelled">Cancelled Events</option>
                </select>
            </div>

            <table class="table table-bordered">
                <thead id="eventsTableHeader">
                    <!-- Header will be dynamically populated based on filter -->
                </thead>
                <tbody id="eventsTableBody">
                    <!-- Data will be dynamically populated here -->
                </tbody>
            </table>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const eventFilter = document.getElementById('eventFilter');

            // Fetch and display events on page load with default filter
            fetchEvents(eventFilter.value);

            // Fetch and display events when the filter is changed
            eventFilter.addEventListener('change', function() {
                fetchEvents(eventFilter.value);
            });

            function fetchEvents(filter) {
                // console.log('Fetching events for filter:', filter); // Debugging

                const url = `server/fetch_user_events.php?filter=${encodeURIComponent(filter)}`;

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateTableHeader(filter);
                            displayEvents(data.events, filter);
                        } else {
                            alert(data.message || 'Failed to load events.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while fetching the events.');
                    });
            }

            function updateTableHeader(filter) {
                const tableHeader = document.getElementById('eventsTableHeader');
                tableHeader.innerHTML = ''; // Clear existing headers

                // Add common headers
                let headerRow = `
                    <tr>
                        <th>Title</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Venue</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                `;

                // Conditionally add headers based on filter
                if (filter !== 'cancelled') {
                    headerRow += `<th>Volunteers Needed</th>`;
                    headerRow += `<th>Request Number</th>`;
                    headerRow += `<th>Action</th>`;
                }

                headerRow += `</tr>`;
                tableHeader.innerHTML = headerRow;
            }

            function displayEvents(events, filter) {
                const tableBody = document.getElementById('eventsTableBody');
                tableBody.innerHTML = ''; // Clear the table before populating

                events.forEach(event => {
                    let row = `
                        <tr>
                            <td>${event.title}</td>
                            <td>${event.start_date}</td>
                            <td>${event.end_date || 'N/A'}</td>
                            <td>${event.venue}</td>
                            <td>${event.start_time}</td>
                            <td>${event.end_time}</td>
                    `;

                    // Conditionally display data based on filter
                    if (filter !== 'cancelled') {
                        row += `<td>${event.volunteers_needed}</td>`;
                        row += `<td>${event.volunteers_requested}</td>`;
                        row += `<td><a href="dashboard_home.php?event_id=${event.id}" class="btn btn-primary">Dashboard</a></td>`;
                    }

                    row += `</tr>`;
                    tableBody.innerHTML += row; // Append each row to the table body
                });
            }
        });
    </script>
</body>

</html>