<?php
    include 'components/connect.php';

    session_start();

    if(isset($_SESSION['user_id'])){
        $user_id = $_SESSION['user_id'];
    } else {
        $user_id = '';
    }

?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
    <title>Booking | WORKVIBES Coworking Space</title>

    <!-- font awesome cdn link  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- custom css file link  -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" type="text/css" href="css/bootstrap.css" />
    <link rel="stylesheet" type="" href="css/footer.css">
    </head>
    <body>
    <!-- header section starts  -->
    <?php include 'components/user_header.php'; ?>
    <!-- header section ends -->
        <div class="heading">
            <h3>Booking Space</h3>
            <p><a href="home.php">home</a> <span> / Booking</span></p>
        </div>

    <div style="margin-left:0;" class="book-container">
        <div class="panel panel-default">
            <div class="panel-body">
                <strong><h3>MAKE A RESERVATION</h3></strong>
                <div class="container" style="margin-left: auto; margin-right: auto;">
                    <div class="row">
						<div class="col-lg-9 col-md-12 px-4" >
                        <div class="card mb-4 border-0 shadow" style="border: 1px solid #fff;  background-color: #f8f9fa; border-radius: 1.1rem; box-shadow: 0 0 5px rgba(0, 0, 0, 0.7); width: 1045px; margin-bottom:30px;">
                        <div class="row g-0 p-3 align-items-center">
                            <div class="col-md-5 mb-lg-0 mb-md-0 mb-3" style="margin-right:-40px;">
                            <img src="img/meetingroom2.jpg" class="img-fluid rounded" style="height: 250px; width:375px; margin-left: 20px;">
                            </div>
                            <div class="col-md-5 px-lg-3 px-md-3 px-0">
                            <h5 class="mb-3" style="font-size: 17px;">Meeting Room</h5>
                            <div class="features mb-4">
                                    <h6 class="mb-1">Features</h6>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Comfortable seating
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Audiovisual equipment
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Climate control
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Privacy
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Security and surveillance
                                    </span>
                                </div>
                                <div class="Facilities mb-3">
                                    <h6 class="mb-1">Facilities</h6>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Wifi high speed
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    24/7 Access
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Parking
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Coffee/Tea
                                    </span>
                                </div>
                                <div class="guests">
                                    <h6 class="mb-1">Capacity</h6>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    5-10 person
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-2 mt-lg-0 mt-md-0 mt-4 text-center">
                            <h6 class="mb-4" style="font-size: 15px; font-weight:bold ;">2000dhs per day</h6>
                            <a href="book-now.php" class="btn btn-sm w-100 text-white custom-bg shadow-none mb-2" onclick="showBookingForm(event, this)">Book Now</a>
                            <a href="spaces.php" class="btn btn-sm w-100 btn-outline-dark shadow-none">More details</a>
                            </div>
                        </div>
                        </div>
                        <div class="card mb-4 border-0 shadow" style="border: 1px solid #fff;  background-color: #f8f9fa; border-radius: 1.1rem; box-shadow: 0 0 5px rgba(0, 0, 0, 0.7); width: 1045px; margin-bottom: 30px;">
                        <div class="row g-0 p-3 align-items-center" style="margin-right:-40px;">
                            <div class="col-md-5 mb-lg-0 mb-md-0 mb-3">
                                <img src="img/virtual-office-3.jpg" class="img-fluid rounded" style="height: 250px; width:375px; margin-left:20px;">
                            </div>
                            <div class="col-md-5 px-lg-3 px-md-3 px-0" style="margin-left: -30px;">
                            <h5 class="mb-3" style="font-size: 17px;">Virtual Office</h5>
                            <div class="features mb-4">
                                    <h6 class="mb-1">Features</h6>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    PC Mac
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Professional business address
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Meeting room access
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Call answering and forwarding
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Security and surveillance
                                    </span>
                                </div>
                                <div class="Facilities mb-3">
                                    <h6 class="mb-1">Facilities</h6>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;" style="background-color: #213A35; color: #CF9D63;">
                                    Wifi high speed
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;" style="background-color: #213A35; color: #CF9D63;">
                                    Printing, scanning
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;" style="background-color: #213A35; color: #CF9D63;">
                                    24/7 Access
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;" style="background-color: #213A35; color: #CF9D63;">
                                    Parking
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;" style="background-color: #213A35; color: #CF9D63;">
                                    Coffee/Tea
                                    </span>
                                </div>
                                <div class="guests">
                                    <h6 class="mb-1">Capacity</h6>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;" style="background-color: #213A35; color: #CF9D63;">
                                    1-5 person
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                            <h6 class="mb-4" style="font-size: 15px; font-weight:bold ;">1500dhs per day </h6>
                            <a href="book-now.php" class="btn btn-sm w-100 text-white custom-bg shadow-none mb-2"  onclick="showBookingForm(event, this)">Book Now</a>
                            <a href="spaces.php" class="btn btn-sm w-100 btn-outline-dark shadow-none">More details</a>
                            </div>
                        </div>
                        </div>
                        <div class="card mb-4 border-0 shadow" style="border: 1px solid #fff;  background-color: #f8f9fa; border-radius: 1.1rem; box-shadow: 0 0 5px rgba(0, 0, 0, 0.7); width: 1045px; margin-bottom: 30px;">
                        <div class="row g-0 p-3 align-items-center" >
                            <div class="col-md-5 mb-lg-0 mb-md-0 mb-3" style="margin-right:-40px;">
                            <img src="img/officespace1.jpg" class="img-fluid rounded" style="height: 250px; width:375px; margin-left: 20px;">
                            </div>
                            <div class="col-md-5 px-lg-3 px-md-3 px-0">
                            <h5 class="mb-3" style="font-size: 17px;">Office Space</h5>
                            <div class="features mb-4">
                                    <h6 class="mb-1">Features</h6>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Climate control
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Meeting room access
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Security and surveillance
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Ergonomic desk and chair
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Cleaning and maintenance services
                                    </span>
                                </div>
                                <div class="Facilities mb-3">
                                    <h6 class="mb-1">Facilities</h6>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Wifi high speed
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    24/7 access
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Parking
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Coffee/Tea
                                    </span>
                                </div>
                                <div class="guests">
                                    <h6 class="mb-1">Capacity</h6>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    10-30 person
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                            <h6 class="mb-4" style="font-size: 15px; font-weight:bold ;">250dhs per day </h6>
                            <a href="book-now.php" class="btn btn-sm w-100 text-white custom-bg shadow-none mb-2"  onclick="showBookingForm(event, this)">Book Now</a>
                            <a href="spaces.php" class="btn btn-sm w-100 btn-outline-dark shadow-none">More details</a>
                            </div>
                        </div>
                        </div>
                        <div class="card mb-4 border-0 shadow" style="border: 1px solid #fff;  background-color: #f8f9fa; border-radius: 1.1rem; box-shadow: 0 0 5px rgba(0, 0, 0, 0.7); width: 1045px; margin-bottom: 30px;">
                        <div class="row g-0 p-3 align-items-center" >
                            <div class="col-md-5 mb-lg-0 mb-md-0 mb-3" style="margin-right:-40px;">
                            <img src="img/dedicateddesk3.jpg" class="img-fluid rounded" style="height: 250px; width:375px; margin-left: 20px;">
                            </div>
                            <div class="col-md-5 px-lg-3 px-md-3 px-0">
                            <h5 class="mb-3" style="font-size: 17px;">Dedicated desk</h5>
                            <div class="features mb-4">
                                    <h6 class="mb-1">Features</h6>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Climate control
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Meeting room access
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Security and surveillance
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Ergonomic desk and chair
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Cleaning and maintenance services
                                    </span>
                                </div>
                                <div class="Facilities mb-3">
                                    <h6 class="mb-1">Facilities</h6>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Wifi high speed
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    24/7 access
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Coffee/Tea
                                    </span>
                                </div>
                                <div class="guests">
                                    <h6 class="mb-1">Capacity</h6>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                        1-2 person
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                            <h6 class="mb-4" style="font-size: 15px; font-weight:bold ;">250dhs per day </h6>
                            <a href="book-now.php" class="btn btn-sm w-100 text-white custom-bg shadow-none mb-2"  onclick="showBookingForm(event, this)">Book Now</a>
                            <a href="spaces.php" class="btn btn-sm w-100 btn-outline-dark shadow-none">More details</a>
                            </div>
                        </div>
                        </div>
                        <div class="card mb-4 border-0 shadow" style="border: 1px solid #fff;  background-color: #f8f9fa; border-radius: 1.1rem; box-shadow: 0 0 5px rgba(0, 0, 0, 0.7); width: 1045px; margin-bottom: 30px;">
                        <div class="row g-0 p-3 align-items-center" >
                            <div class="col-md-5 mb-lg-0 mb-md-0 mb-3" style="margin-right:-40px;">
                            <img src="img/Membership-cowork.jpg" class="img-fluid rounded" style="height: 250px; width:375px; margin-left: 20px;">
                            </div>
                            <div class="col-md-5 px-lg-3 px-md-3 px-0">
                            <h5 class="mb-3" style="font-size: 17px;">Coworking membership</h5>
                            <div class="features mb-4">
                                    <h6 class="mb-1">Features</h6>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Flexible workspace options
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Comfortable workstations
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Networking opportunities
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Professional mailing address
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Business support services
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Secure access and surveillance
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Multiple membership plans
                                    </span>
                                </div>
                                <div class="Facilities mb-3">
                                    <h6 class="mb-1">Facilities</h6>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Wifi high speed
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    24/7 access
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Parking
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Access to all coworking spaces
                                    </span>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                    Coffee/Tea
                                    </span>
                                </div>
                                <div class="guests">
                                    <h6 class="mb-1">Capacity</h6>
                                    <span class="badge rounded-pill bg-light text-dark text-wrap" style="background-color: #213A35; color: #CF9D63;">
                                        1 person
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                            <h6 class="mb-4" style="font-size: 15px; font-weight:bold ;">490dhs/month basic plan</h6>
                            <a href="book-now.php" class="btn btn-sm w-100 text-white custom-bg shadow-none mb-2"  onclick="showBookingForm(event, this)">Book Now</a>
                            <a href="spaces.php" class="btn btn-sm w-100 btn-outline-dark shadow-none">More details</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- Booking form -->
<div id="bookingForm" style="display: none;">
    <h2>Booking Form</h2>
    <div>
        <label for="selectedRoomName">Selected Room:</label>
        <select id="selectedRoomName"></select>
    </div>
    <form>
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" required>
        </div>
        <div class="mb-3">
            <label for="date" class="form-label">Start Date</label>
            <input type="date" class="form-control" id="date" required>
        </div>
        <div class="mb-3">
            <label for="message" class="form-label">Message</label>
            <textarea class="form-control" id="message" rows="3"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

<script>
    function showBookingForm(event, button) {
        var card = button.closest('.card');
        var roomName = card.querySelector('.col-md-5 h5').textContent;
        
        var selectedRoomName = document.getElementById('selectedRoomName');
        selectedRoomName.textContent = roomName;
        
        var bookingFormPopup = document.getElementById('bookingFormPopup');
        bookingFormPopup.style.display = 'block';

        event.preventDefault();
    }
    
    function hideBookingForm() {
        var bookingFormPopup = document.getElementById('bookingForm');
        bookingFormPopup.style.display = 'none';
    }
    
    // Example of adding multiple options
    var selectElement = document.getElementById('selectedRoomName');
    var rooms = ['Meeting Room', 'Virtual Office', 'Office Space', 'Dedicated Desk', 'Coworking Membership'];
    
    rooms.forEach(function(room) {
        var option = document.createElement('option');
        option.value = room;
        option.text = room;
        selectElement.appendChild(option);
    });
</script>


	
    <!-- footer section starts  -->
    <?php include 'components/footer.php'; ?>
    <!-- footer section ends -->
    <script>
        // Wait for the document to be ready
        document.addEventListener("DOMContentLoaded", function() {
            // Add event listener to the filter button
            var filterButton = document.getElementById("filterButton");
            filterButton.addEventListener("click", applyFilters);
        });

        function applyFilters() {
            // Get selected values
            var checkInDate = document.getElementById("checkInDate").value;
            var checkOutDate = document.getElementById("checkOutDate").value;
            var capacityFilter = document.querySelector('input[name="capacityFilter"]:checked').value;
            var priceFilter = document.querySelector('input[name="priceFilter"]:checked').value;
            var facilitiesFilter = [];
            var facilitiesCheckboxes = document.querySelectorAll('input[name="facilitiesFilter"]:checked');
            facilitiesCheckboxes.forEach(function(checkbox) {
                facilitiesFilter.push(checkbox.value);
            });

            // Perform filtering based on selected values
            // Add your logic here

            // For demonstration purposes, display the selected values in the console
            console.log("Check-in Date:", checkInDate);
            console.log("Check-out Date:", checkOutDate);
            console.log("Capacity Filter:", capacityFilter);
            console.log("Price Filter:", priceFilter);
            console.log("Facilities Filter:", facilitiesFilter);
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>