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
    <title>Find Event</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/find_event.css">
    <style>

    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <section>
        <div class="bg-success py-2">
            <form id="searchForm" class="container">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-sm-4 search-container d-flex align-items-center position-relative">
                        <input type="text" id="searchInput" class="form-control form-select-sm" placeholder="Search by event name" aria-label="Search by event name" list="eventSuggestions">
                        <span class="clear-icon" id="clearEventInput">&times;</span>
                        <datalist id="eventSuggestions"></datalist>
                    </div>

                    <div class="col-12 col-sm-4 search-container d-flex align-items-center position-relative">
                        <select id="state" name="state" class="form-select form-select-sm" aria-label="Select State" required>
                            <option value="">Select a State</option>
                        </select>
                        <span class="clear-icon" id="clearStateDropdown">&times;</span>
                    </div>

                    <div class="col-12 col-sm-4 search-container d-flex align-items-center position-relative">
                        <select id="city" name="city" class="form-select form-select-sm" aria-label="Select City" required>
                            <option value="">Select a City</option>
                        </select>
                        <span class="clear-icon" id="clearCityDropdown">&times;</span>
                    </div>


                    <!-- Skill Dropdown -->
                    <div class="col-12 col-sm-4 search-container d-flex align-items-center position-relative">
                        <select id="skillSelect" class="form-select form-select-sm">
                            <option value="">Select Skill</option>
                            <option value="Tutoring and Mentoring Programs">Tutoring and Mentoring Programs</option>
                            <option value="Adult Education Classes">Adult Education Classes</option>
                            <option value="School Supplies Drives">School Supplies Drives</option>
                            <option value="Health Screenings">Health Screenings</option>
                            <option value="Health Education Workshops">Health Education Workshops</option>
                            <option value="Fitness and Wellness Programs">Fitness and Wellness Programs</option>
                            <option value="Community Clean-ups">Community Clean-ups</option>
                            <option value="Tree Planting and Gardening">Tree Planting and Gardening</option>
                            <option value="Recycling Drives">Recycling Drives</option>
                            <option value="Food Drives and Pantries">Food Drives and Pantries</option>
                            <option value="Clothing Drives">Clothing Drives</option>
                            <option value="Housing Assistance">Housing Assistance</option>
                            <option value="Cultural Festivals and Events">Cultural Festivals and Events</option>
                            <option value="Recreational Programs">Recreational Programs</option>
                            <option value="Public Libraries and Community Centers">Public Libraries and Community Centers</option>
                            <option value="Legal Aid Clinics">Legal Aid Clinics</option>
                            <option value="Advocacy Campaigns">Advocacy Campaigns</option>
                            <option value="Support Groups">Support Groups</option>
                            <option value="Job Fairs and Career Counseling">Job Fairs and Career Counseling</option>
                            <option value="Small Business Support">Small Business Support</option>
                        </select>
                        <span class="clear-icon" id="clearSkillDropdown">&times;</span>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                </div>
                <div class="col-12 mb-3">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="filterBySkill" id="filterBySkill" value="1">
                        <label class="form-check-label" for="filterBySkill">Only show events matching my skills</label>
                    </div>
                </div>
                <div class="col-12 mb-3">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="filterByDonation" id="filterByDonation" value="1">
                        <label class="form-check-label" for="filterByDonation">Only show events that need donation</label>
                    </div>
                </div>
            </form>
        </div>

        <div class="container py-4" style="min-height: 500px;">
            <div class="col-12" id="eventList"></div>
        </div>
    </section>




    <?php include 'footer.php'; ?>


    <script>
        let stateCodeMap = {};
        let allCities = {};
        document.addEventListener('DOMContentLoaded', function() {

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

            const searchInput = document.getElementById('searchInput');
            const skillSelect = document.getElementById('skillSelect');
            const clearEventInput = document.getElementById('clearEventInput');
            const clearSkillDropdown = document.getElementById('clearSkillDropdown');
            const eventSuggestions = document.getElementById('eventSuggestions');
            const eventList = document.getElementById('eventList');
            const filterBySkillCheckbox = document.getElementById('filterBySkill');
            const filterByDonationCheckbox = document.getElementById('filterByDonation');
            const stateDropdown = document.getElementById('state');
            const cityDropdown = document.getElementById('city');
            const clearStateDropdown = document.getElementById('clearStateDropdown');
            const clearCityDropdown = document.getElementById('clearCityDropdown');

            stateDropdown.addEventListener('change', function() {
                const stateName = this.value;
                displayCitiesForState(stateName);
                fetchFilteredEvents();
                toggleClearIcon(stateDropdown, clearStateDropdown);
            });

            cityDropdown.addEventListener('change', function() {
                fetchFilteredEvents();
                toggleClearIcon(cityDropdown, clearCityDropdown);
            });

            clearStateDropdown.addEventListener('click', function() {
                stateDropdown.value = '';
                cityDropdown.innerHTML = '<option value="">Select a city</option>';
                fetchFilteredEvents();
                toggleClearIcon(stateDropdown, clearStateDropdown);
            });

            clearCityDropdown.addEventListener('click', function() {
                cityDropdown.value = '';
                fetchFilteredEvents();
                toggleClearIcon(cityDropdown, clearCityDropdown);
            });


            searchInput.addEventListener('input', function() {
                fetchSuggestions(this.value, 'event');
                toggleClearIcon(this, clearEventInput);
            });

            searchInput.addEventListener('change', function() {
                fetchFilteredEvents();
            });

            skillSelect.addEventListener('change', function() {
                fetchFilteredEvents();
            });

            skillSelect.addEventListener('change', function() {
                fetchFilteredEvents();
                toggleClearIcon(this, clearSkillDropdown);

            });

            document.getElementById('searchForm').addEventListener('submit', function(e) {
                e.preventDefault();
                fetchFilteredEvents();
            });

            clearEventInput.addEventListener('click', function() {
                searchInput.value = '';
                fetchFilteredEvents();
                toggleClearIcon(searchInput, clearEventInput);
            });

            clearSkillDropdown.addEventListener('click', function() {
                skillSelect.value = '';
                fetchFilteredEvents();
                toggleClearIcon(skillSelect, clearSkillDropdown);
            })

            filterBySkillCheckbox.addEventListener('change', function() {
                fetchFilteredEvents();
            });

            filterByDonationCheckbox.addEventListener('change', function() {
                fetchFilteredEvents();
            });

            function fetchSuggestions(term, type) {
                if (term.length > 0) {
                    fetch(`server/fetch_suggestion.php?term=${encodeURIComponent(term)}`)
                        .then(response => response.json())
                        .then(data => {
                            let suggestions = '';
                            data.forEach(item => {
                                if (type === 'event') {
                                    suggestions += `<option value="${item.title}">`;
                                } else if (type === 'location') {
                                    suggestions += `<option value="${item.city}">`;
                                }
                            });
                            if (type === 'event') {
                                eventSuggestions.innerHTML = suggestions;
                            } else {
                                locationSuggestions.innerHTML = suggestions;
                            }
                        })
                        .catch(error => console.error('Error fetching suggestions:', error));
                }
            }

            function fetchFilteredEvents() {
                const eventTerm = searchInput.value.trim();
                const skillTerm = skillSelect.value;
                const filterBySkill = filterBySkillCheckbox.checked ? '1' : '0';
                const filterByDonation = filterByDonationCheckbox.checked ? '1' : '0';
                const stateTerm = stateDropdown.value;
                const cityTerm = cityDropdown.value;

                const formData = new FormData();
                formData.append('event_term', eventTerm);
                formData.append('state_term', stateTerm);
                formData.append('city_term', cityTerm);
                formData.append('skill_term', skillTerm);
                formData.append('filter_by_skill', filterBySkill);
                formData.append('filter_by_donation', filterByDonation);

                fetch('server/fetch_event_list.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        eventList.innerHTML = data;
                    })
                    .catch(error => console.error('Error fetching events:', error));
            }

            function toggleClearIcon(element, icon) {
                if (element.tagName === 'SELECT') {
                    icon.style.display = element.value ? 'block' : 'none';
                } else {
                    icon.style.display = element.value ? 'block' : 'none';
                }
            }

            // Fetch all events on initial page load
            fetchFilteredEvents();
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