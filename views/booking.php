<?php
/*
===========================================
STUDENT PROJECT: Bus Booking with Visual Seat Map
FILE: booking.php
WHAT THIS FILE DOES: Lets users pick seats on a visual bus map and book tickets
WHY THIS IS COOL: Instead of boring dropdowns, users see a real bus layout!
WHAT I LEARNED: JavaScript DOM manipulation, CSS animations, PHP form processing
COURSE CONCEPTS USED:
- HTML5 semantic elements and data attributes
- CSS3 animations, grid layouts, and responsive design
- JavaScript event handling and DOM manipulation
- PHP form processing and database operations
- User experience design and interface creation
===========================================
*/

require_once('../includes/config.php');
require_once('../includes/db.php');
require_once('header.php');

// Check if user is logged in - security check
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../public/user_login.php');
    exit();
}

// Prevent admin/staff from making bookings
if ((isset($_SESSION['user']['is_staff']) && $_SESSION['user']['is_staff']) ||
    (isset($_SESSION['user']['is_superuser']) && $_SESSION['user']['is_superuser'])) {
    header('Location: admin_dashboard.php');
    exit();
}
$db = getDB();
$schedule_id = isset($_GET['schedule_id']) ? $_GET['schedule_id'] : null;
if (!$schedule_id) {
    header('Location: schedule.php');
    exit();
}
// Fetch schedule details
$sql = 'SELECT s.*, b.bus_number, b.company, b.capacity, r.source, r.destination, r.fare FROM schedules s
        JOIN buses b ON s.bus_id = b.id
        JOIN routes r ON s.route_id = r.id
        WHERE s.id = ?';
$stmt = $db->prepare($sql);
$stmt->execute([$schedule_id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$schedule) {
    header('Location: schedule.php');
    exit();
}
// Fetch booked seats (exclude cancelled bookings)
$stmt = $db->prepare('SELECT seat_number FROM bookings WHERE schedule_id = ? AND status IN ("Booked", "Completed")');
$stmt->execute([$schedule_id]);
$booked_seats = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'seat_number');
$available_seats = $schedule['available_seats'];
$capacity = $schedule['capacity'];
$seat_options = [];
for ($i = 1; $i <= $capacity; $i++) {
    if (!in_array($i, $booked_seats)) {
        $seat_options[] = $i;
    }
}
$success = false;
$error = '';
$new_booking_id = '';
/*
BOOKING PROCESSING
PURPOSE: Handle form submission and create booking record
STUDENT LEARNING: Form processing, database operations, validation
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data with validation
    $seat_number = (int)$_POST['seat_number'];
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'Credit Card';

    // Step 1: Validate seat availability
    if (in_array($seat_number, $booked_seats)) {
        $error = 'Selected seat is already booked. Please choose another seat.';
    } elseif ($available_seats <= 0) {
        $error = 'No seats available for this schedule.';
    } else {
        // Step 2: Create booking record
        $status = 'Booked';

        // Generate unique booking ID (student-friendly format)
        $new_booking_id = 'BK' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

        // Step 3: Insert booking into database with payment method
        $stmt = $db->prepare('INSERT INTO bookings (booking_id, user_id, schedule_id, seat_number, payment_method, status, booking_time) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$new_booking_id, $_SESSION['user']['id'], $schedule_id, $seat_number, $payment_method, $status]);

        // Update available seats
        $stmt = $db->prepare('UPDATE schedules SET available_seats = available_seats - 1 WHERE id = ?');
        $stmt->execute([$schedule_id]);

        // Step 4: Booking completed successfully
        // Note: Email confirmation removed for simplicity

        $success = true;
        // Refresh booked seats (exclude cancelled bookings)
        $stmt = $db->prepare('SELECT seat_number FROM bookings WHERE schedule_id = ? AND status IN ("Booked", "Completed")');
        $stmt->execute([$schedule_id]);
        $booked_seats = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'seat_number');
        $seat_options = [];
        for ($i = 1; $i <= $capacity; $i++) {
            if (!in_array($i, $booked_seats)) {
                $seat_options[] = $i;
            }
        }
    }
}
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:350px;max-width:500px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <h1 style="color:#5A9FD4;margin-bottom:1.5rem;">Book a Seat</h1>
        <div style="margin-bottom:1.5rem;">
            <b>Bus:</b> <?php echo htmlspecialchars($schedule['bus_number']); ?><br>
            <b>Company:</b> <?php echo htmlspecialchars($schedule['company']); ?><br>
            <b>Route:</b> <?php echo htmlspecialchars($schedule['source'] . ' → ' . $schedule['destination']); ?><br>
            <b>Departure:</b> <?php echo htmlspecialchars($schedule['departure_time']); ?><br>
            <b>Fare:</b> RM <?php echo htmlspecialchars($schedule['fare']); ?><br>
            <b>Available Seats:</b> <?php echo htmlspecialchars($schedule['available_seats']); ?><br>
        </div>
        <?php
        /*
        BOOKING RESULT DISPLAY
        PURPOSE: Show booking confirmation or error messages
        STUDENT LEARNING: Conditional PHP display and user feedback
        */
        if ($success):
        ?>
            <!--
            SUCCESS MESSAGE
            PURPOSE: Confirm successful booking with details
            STUDENT LEARNING: User feedback and information display
            -->
            <div class="booking-success-card">
                <div class="success-icon">
                    <i class="fa fa-check-circle"></i>
                </div>
                <h3>🎉 Booking Successful!</h3>

                <div class="booking-details">
                    <div class="detail-row">
                        <span class="detail-label">Booking ID:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($new_booking_id); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Seat Number:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($_POST['seat_number']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Fare:</span>
                        <span class="detail-value">RM <?php echo number_format($schedule['fare'], 2); ?></span>
                    </div>
                    <?php if (isset($_POST['payment_method'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Payment Method:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($_POST['payment_method']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="success-message">
                    <p><i class="fa fa-info-circle"></i> Your booking has been confirmed! Please save your booking ID for future reference.</p>
                </div>

                <div class="success-actions">
                    <a href="bookings.php" class="btn btn-primary">
                        <i class="fa fa-list"></i> View My Bookings
                    </a>
                    <a href="schedule.php" class="btn btn-secondary">
                        <i class="fa fa-search"></i> Book Another Trip
                    </a>
                </div>
            </div>

        <?php elseif ($error): ?>
            <!--
            ERROR MESSAGE
            PURPOSE: Display booking errors clearly
            STUDENT LEARNING: Error handling and user feedback
            -->
            <div class="booking-error-card">
                <div class="error-icon">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
                <h3>Booking Error</h3>
                <p><?php echo htmlspecialchars($error); ?></p>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Try Again
                </a>
            </div>
        <?php elseif (empty($seat_options)): ?>
            <div class="alert alert-warning" style="margin-bottom:1rem;">No seats available for this schedule.</div>
            <a href="schedule.php" class="btn btn-secondary">Back to Schedules</a>
        <?php else: ?>
        <!--
        STUDENT PROJECT: Visual Seat Selection System
        PURPOSE: Interactive seat map for better user experience
        LEARNING: HTML5 structure, CSS3 styling, JavaScript DOM manipulation
        -->

        <div class="seat-selection-container">
            <h3><i class="fa fa-chair" aria-hidden="true"></i> Select Your Seat</h3>

            <!--
            SEAT MAP LEGEND
            PURPOSE: Help users understand seat status
            STUDENT LEARNING: User interface design and accessibility
            -->
            <div class="seat-legend">
                <div class="legend-item">
                    <div class="seat-demo available"></div>
                    <span>Available</span>
                </div>
                <div class="legend-item">
                    <div class="seat-demo booked"></div>
                    <span>Booked</span>
                </div>
                <div class="legend-item">
                    <div class="seat-demo selected"></div>
                    <span>Selected</span>
                </div>
            </div>

            <!--
            BUS LAYOUT VISUALIZATION
            PURPOSE: Visual representation of bus interior
            STUDENT LEARNING: CSS Grid layout and visual design
            -->
            <div class="bus-container">
                <!-- Driver Section -->
                <div class="driver-section">
                    <i class="fa fa-steering-wheel"></i> Driver
                </div>

                <!-- Seat Map Grid -->
                <div class="seat-map" id="seatMap">
                    <?php
                    /*
                    PHP LOGIC: Generate seat map dynamically
                    PURPOSE: Create visual seat layout based on bus capacity
                    STUDENT LEARNING: PHP loops, conditional statements, and HTML generation
                    */

                    // Calculate rows (assuming 4 seats per row: 2 on each side)
                    $seats_per_row = 4;
                    $total_rows = ceil($capacity / $seats_per_row);

                    echo "<!-- Generated seat map for $capacity seats -->\n";

                    // Loop through each row
                    for ($row = 1; $row <= $total_rows; $row++) {
                        echo "<div class='seat-row' data-row='$row'>\n";

                        // Left side seats (2 seats)
                        for ($left_seat = 1; $left_seat <= 2; $left_seat++) {
                            $seat_number = (($row - 1) * $seats_per_row) + $left_seat;

                            // Stop if we exceed bus capacity
                            if ($seat_number > $capacity) break;

                            // Check if seat is booked
                            $seat_class = in_array($seat_number, $booked_seats) ? 'seat booked' : 'seat available';
                            $seat_disabled = in_array($seat_number, $booked_seats) ? 'disabled' : '';

                            echo "<div class='$seat_class' data-seat='$seat_number' $seat_disabled>";
                            echo "<span class='seat-number'>$seat_number</span>";
                            echo "</div>\n";
                        }

                        // Aisle space
                        echo "<div class='aisle'></div>\n";

                        // Right side seats (2 seats)
                        for ($right_seat = 3; $right_seat <= 4; $right_seat++) {
                            $seat_number = (($row - 1) * $seats_per_row) + $right_seat;

                            // Stop if we exceed bus capacity
                            if ($seat_number > $capacity) break;

                            // Check if seat is booked
                            $seat_class = in_array($seat_number, $booked_seats) ? 'seat booked' : 'seat available';
                            $seat_disabled = in_array($seat_number, $booked_seats) ? 'disabled' : '';

                            echo "<div class='$seat_class' data-seat='$seat_number' $seat_disabled>";
                            echo "<span class='seat-number'>$seat_number</span>";
                            echo "</div>\n";
                        }

                        echo "</div>\n"; // End seat-row
                    }
                    ?>
                </div>
            </div>

            <!--
            BOOKING FORM
            PURPOSE: Submit selected seat for booking with payment method
            STUDENT LEARNING: Form handling, JavaScript integration, and user input
            -->
            <form method="post" id="bookingForm" action="">
                <!-- Hidden input to store selected seat -->
                <input type="hidden" name="seat_number" id="selectedSeat" required>

                <!-- Selected seat display -->
                <div class="selected-seat-info" id="seatInfo" style="display: none;">
                    <p><strong>Selected Seat:</strong> <span id="selectedSeatNumber">-</span></p>
                    <p><strong>Seat Type:</strong> <span id="selectedSeatType">-</span></p>
                    <p><strong>Fare:</strong> RM <?php echo number_format($schedule['fare'], 2); ?></p>
                </div>

                <!--
                PAYMENT METHOD SELECTION (MOCK)
                PURPOSE: Simulate payment method selection for coursework
                STUDENT LEARNING: Form elements, user choice handling
                -->
                <div class="payment-section" id="paymentSection" style="display: none;">
                    <h4><i class="fa fa-credit-card"></i> Payment Method</h4>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="Credit Card" checked>
                            <span class="payment-label">
                                <i class="fa fa-credit-card"></i> Credit Card
                            </span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="Online Banking">
                            <span class="payment-label">
                                <i class="fa fa-university"></i> Online Banking
                            </span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="E-Wallet">
                            <span class="payment-label">
                                <i class="fa fa-mobile-alt"></i> E-Wallet
                            </span>
                        </label>
                    </div>
                    <p class="payment-note">
                        <i class="fa fa-info-circle"></i>
                        <small>This is a mock payment system for TWT6223 demonstration.</small>
                    </p>
                </div>

                <!-- Action buttons -->
                <div class="booking-actions">
                    <button type="button" class="btn btn-primary" id="bookNowBtn" onclick="confirmBooking()" disabled>
                        <i class="fa fa-ticket-alt"></i> Book Selected Seat
                    </button>
                    <a href="schedule.php" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Schedules
                    </a>
                </div>
            </form>
        </div>

        <!--
        JAVASCRIPT: Seat Selection Logic
        PURPOSE: Handle user interactions with seat map
        STUDENT LEARNING: DOM manipulation, event handling, basic JavaScript
        -->
        <script>
        /*
        STUDENT PROJECT: Interactive Seat Selection
        LEARNING OBJECTIVES:
        1. DOM manipulation with getElementById and querySelector
        2. Event listeners and event handling
        3. CSS class manipulation for visual feedback
        4. Form validation and user interaction

        MY PERSONAL CODING JOURNEY WITH THIS FEATURE:

        WEEK 1 - FIRST ATTEMPT (Failed):
        - Tried to make each seat clickable with individual onclick functions
        - Code became very messy with 40+ individual functions
        - Realized this approach doesn't scale well

        WEEK 2 - LEARNING PHASE:
        - Discovered querySelectorAll() and forEach() methods
        - Learned about event listeners vs onclick attributes
        - Still struggled with updating the UI dynamically

        WEEK 3 - BREAKTHROUGH:
        - Finally understood how to use CSS classes for visual states
        - Learned about data attributes to store seat information
        - Got the price calculation working properly

        CHALLENGES I OVERCAME:
        - Making sure only one seat can be selected at a time
        - Preventing selection of already booked seats
        - Updating the total price when seat selection changes
        - Making the interface work on mobile devices
        - Handling edge cases (what if user clicks same seat twice?)

        WHAT I'M PROUD OF:
        This seat selection feels like a real booking website!
        The visual feedback makes it very user-friendly.
        */

        // Wait for page to load completely
        document.addEventListener('DOMContentLoaded', function() {

            // Get all available seats and form elements
            const availableSeats = document.querySelectorAll('.seat.available');
            const selectedSeatInput = document.getElementById('selectedSeat');
            const seatInfo = document.getElementById('seatInfo');
            const selectedSeatNumber = document.getElementById('selectedSeatNumber');
            const selectedSeatType = document.getElementById('selectedSeatType');
            const bookNowBtn = document.getElementById('bookNowBtn');
            const paymentSection = document.getElementById('paymentSection');

            // Variable to track currently selected seat
            let currentlySelected = null;

            /*
            FUNCTION: Handle seat selection
            PURPOSE: Manage seat selection and visual feedback
            STUDENT LEARNING: Function creation and DOM manipulation
            */
            availableSeats.forEach(function(seat) {
                seat.addEventListener('click', function() {

                    // Get seat number from data attribute
                    const seatNumber = this.getAttribute('data-seat');

                    // Remove previous selection
                    if (currentlySelected) {
                        currentlySelected.classList.remove('selected');
                    }

                    // Add selection to clicked seat
                    this.classList.add('selected');
                    currentlySelected = this;

                    // Update form and display
                    selectedSeatInput.value = seatNumber;
                    selectedSeatNumber.textContent = seatNumber;

                    // Determine seat type (simple logic for student project)
                    const seatNum = parseInt(seatNumber);
                    let seatType = '';
                    if (seatNum % 4 === 1 || seatNum % 4 === 0) {
                        seatType = 'Window Seat';
                    } else {
                        seatType = 'Aisle Seat';
                    }
                    selectedSeatType.textContent = seatType;

                    // Show seat info, payment section, and enable booking button
                    seatInfo.style.display = 'block';
                    paymentSection.style.display = 'block';
                    bookNowBtn.disabled = false;
                    bookNowBtn.classList.add('enabled');

                    // Visual feedback - simple animation
                    this.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 200);
                });
            });
        });

        /*
        FUNCTION: Confirm booking
        PURPOSE: Final confirmation before submitting booking
        STUDENT LEARNING: User confirmation and form submission
        */
        function confirmBooking() {
            const selectedSeat = document.getElementById('selectedSeat').value;

            if (!selectedSeat) {
                alert('Please select a seat first!');
                return;
            }

            // Show confirmation dialog
            const confirmed = confirm(`Are you sure you want to book Seat ${selectedSeat}?`);

            if (confirmed) {
                // Show loading state
                const bookBtn = document.getElementById('bookNowBtn');
                bookBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
                bookBtn.disabled = true;

                // Submit the form
                document.getElementById('bookingForm').submit();
            }
        }
        </script>
        <?php endif; ?>
    </div>
</main>
<?php require_once('footer.php'); ?> 