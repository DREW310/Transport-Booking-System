<?php
require_once('../includes/db.php');
require_once('../includes/status_helpers.php');
require_once('../views/header.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}

$db = getDB();
$schedule_id = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : null;

if (!$schedule_id) {
    header('Location: admin_schedules.php');
    exit();
}

// Get schedule details with comprehensive information
$schedule_sql = 'SELECT s.id, s.departure_time, s.available_seats,
                        b.bus_number, b.license_plate, b.bus_type, b.capacity, b.company,
                        r.source, r.destination, r.fare
                 FROM schedules s
                 JOIN buses b ON s.bus_id = b.id
                 JOIN routes r ON s.route_id = r.id
                 WHERE s.id = ?';
$schedule_stmt = $db->prepare($schedule_sql);
$schedule_stmt->execute([$schedule_id]);
$schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    header('Location: admin_schedules.php');
    exit();
}

// Get all bookings for this schedule with user details
$bookings_sql = 'SELECT b.seat_number, b.booking_id, b.booking_time, b.status, b.payment_method,
                        u.username, u.email, p.full_name, p.phone
                 FROM bookings b
                 JOIN users u ON b.user_id = u.id
                 LEFT JOIN profiles p ON u.id = p.user_id
                 WHERE b.schedule_id = ? AND b.status IN ("Booked", "Completed")
                 ORDER BY b.seat_number ASC';
$bookings_stmt = $db->prepare($bookings_sql);
$bookings_stmt->execute([$schedule_id]);
$bookings = $bookings_stmt->fetchAll(PDO::FETCH_ASSOC);

// Create seat booking map
$seat_bookings = [];
foreach ($bookings as $booking) {
    $seat_bookings[$booking['seat_number']] = $booking;
}

$capacity = $schedule['capacity'];
$booked_count = count($bookings);
$occupancy_rate = ($booked_count / $capacity) * 100;
?>

<style>
.seat-map-container {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 2rem;
    margin: 1.5rem 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.bus-container {
    max-width: 400px;
    margin: 0 auto;
    background: #fff;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    position: relative;
}

.driver-section {
    text-align: center;
    padding: 1rem;
    background: #e3f2fd;
    border-radius: 12px;
    margin-bottom: 1rem;
    font-weight: 600;
    color: #1976d2;
    border: 2px solid #bbdefb;
}

.seat-map {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.seat-row {
    display: grid;
    grid-template-columns: 1fr 1fr 20px 1fr 1fr;
    gap: 8px;
    align-items: center;
}

.seat {
    width: 45px;
    height: 45px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.seat.available {
    background: #e8f5e8;
    border: 2px solid #4caf50;
    color: #2e7d32;
}

.seat.booked {
    background: #ffebee;
    border: 2px solid #f44336;
    color: #c62828;
    cursor: pointer;
}

.seat.booked:hover {
    background: #f44336;
    color: white;
    transform: scale(1.1);
}

.aisle {
    width: 20px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 0.7rem;
}

.legend {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin: 1.5rem 0;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.legend-seat {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    border: 2px solid;
}

.booking-details {
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 2rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.booking-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.booking-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.booking-card h4 {
    margin: 0 0 1rem 0;
    color: #1976d2;
    font-size: 1.1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e3f2fd;
}

.booking-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    font-size: 0.9rem;
}

.info-section {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    border-left: 4px solid #2196f3;
}

.info-section h5 {
    margin: 0 0 0.75rem 0;
    color: #1976d2;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e0e0e0;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #666;
    min-width: 100px;
}

.info-value {
    color: #333;
    font-weight: 500;
    text-align: right;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-booked {
    background: #e3f2fd;
    color: #1976d2;
}

.status-completed {
    background: #e8f5e8;
    color: #2e7d32;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: #fff;
    padding: 1.5rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-top: 4px solid;
}

.stat-card.occupancy { border-top-color: #2196f3; }
.stat-card.booked { border-top-color: #f44336; }
.stat-card.available { border-top-color: #4caf50; }
.stat-card.revenue { border-top-color: #ff9800; }

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

@media (max-width: 768px) {
    .seat-row {
        grid-template-columns: 1fr 1fr 15px 1fr 1fr;
        gap: 4px;
    }
    
    .seat {
        width: 35px;
        height: 35px;
        font-size: 0.7rem;
    }
    
    .legend {
        gap: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2rem 2rem 2.5rem;min-width:400px;max-width:1000px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
            <a href="admin_schedules.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back to Schedules</a>
            <div style="text-align: right; font-size: 0.9rem; color: #666;">
                <div><strong>Schedule ID:</strong> <?php echo $schedule_id; ?></div>
            </div>
        </div>
        
        <h1 style="margin-bottom:1.5rem;text-align:center;">
            <i class="fa fa-th icon-red"></i> Seat Map - <?php echo htmlspecialchars($schedule['bus_number']); ?>
        </h1>
        
        <!-- Schedule Information -->
        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; font-size: 0.9rem;">
                <div><strong>Route:</strong> <?php echo htmlspecialchars($schedule['source'] . ' → ' . $schedule['destination']); ?></div>
                <div><strong>Bus Type:</strong> <?php echo htmlspecialchars($schedule['bus_type']); ?></div>
                <div><strong>License:</strong> <code><?php echo htmlspecialchars($schedule['license_plate']); ?></code></div>
                <div><strong>Company:</strong> <?php echo htmlspecialchars($schedule['company']); ?></div>
                <div><strong>Departure:</strong> <?php echo date('M j, Y g:i A', strtotime($schedule['departure_time'])); ?></div>
                <div><strong>Fare:</strong> RM <?php echo number_format($schedule['fare'], 2); ?></div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card occupancy">
                <div class="stat-number"><?php echo number_format($occupancy_rate, 1); ?>%</div>
                <div class="stat-label">Occupancy Rate</div>
            </div>
            <div class="stat-card booked">
                <div class="stat-number"><?php echo $booked_count; ?></div>
                <div class="stat-label">Seats Booked</div>
            </div>
            <div class="stat-card available">
                <div class="stat-number"><?php echo $schedule['available_seats']; ?></div>
                <div class="stat-label">Available Seats</div>
            </div>
            <div class="stat-card revenue">
                <div class="stat-number">RM <?php echo number_format($booked_count * $schedule['fare'], 2); ?></div>
                <div class="stat-label">Revenue Generated</div>
            </div>
        </div>

        <!-- Seat Map -->
        <div class="seat-map-container">
            <div class="bus-container">
                <!-- Driver Section -->
                <div class="driver-section">
                    <i class="fa fa-steering-wheel"></i> Driver
                </div>

                <!-- Seat Map Grid -->
                <div class="seat-map" id="seatMap">
                    <?php
                    // Calculate rows (assuming 4 seats per row: 2 on each side)
                    $seats_per_row = 4;
                    $total_rows = ceil($capacity / $seats_per_row);

                    // Loop through each row
                    for ($row = 1; $row <= $total_rows; $row++) {
                        echo "<div class='seat-row' data-row='$row'>\n";

                        // Left side seats (2 seats)
                        for ($left_seat = 1; $left_seat <= 2; $left_seat++) {
                            $seat_number = (($row - 1) * $seats_per_row) + $left_seat;

                            // Stop if we exceed bus capacity
                            if ($seat_number > $capacity) break;

                            // Check if seat is booked
                            $is_booked = isset($seat_bookings[$seat_number]);
                            $seat_class = $is_booked ? 'seat booked' : 'seat available';
                            $seat_data = $is_booked ? 'data-booking="' . htmlspecialchars(json_encode($seat_bookings[$seat_number])) . '"' : '';

                            echo "<div class='$seat_class' data-seat='$seat_number' $seat_data>";
                            echo "<span class='seat-number'>$seat_number</span>";
                            echo "</div>\n";
                        }

                        // Aisle space
                        echo "<div class='aisle'>|</div>\n";

                        // Right side seats (2 seats)
                        for ($right_seat = 3; $right_seat <= 4; $right_seat++) {
                            $seat_number = (($row - 1) * $seats_per_row) + $right_seat;

                            // Stop if we exceed bus capacity
                            if ($seat_number > $capacity) break;

                            // Check if seat is booked
                            $is_booked = isset($seat_bookings[$seat_number]);
                            $seat_class = $is_booked ? 'seat booked' : 'seat available';
                            $seat_data = $is_booked ? 'data-booking="' . htmlspecialchars(json_encode($seat_bookings[$seat_number])) . '"' : '';

                            echo "<div class='$seat_class' data-seat='$seat_number' $seat_data>";
                            echo "<span class='seat-number'>$seat_number</span>";
                            echo "</div>\n";
                        }

                        echo "</div>\n"; // End seat-row
                    }
                    ?>
                </div>
            </div>

            <!-- Legend -->
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-seat" style="background: #e8f5e8; border-color: #4caf50;"></div>
                    <span>Available</span>
                </div>
                <div class="legend-item">
                    <div class="legend-seat" style="background: #ffebee; border-color: #f44336;"></div>
                    <span>Booked (Click to view details)</span>
                </div>
            </div>
        </div>

        <!-- Booking Details Section -->
        <?php if (!empty($bookings)): ?>
        <div class="booking-details">
            <h2 style="margin-bottom: 1.5rem;"><i class="fa fa-users"></i> Passenger Details (<?php echo count($bookings); ?> bookings)</h2>
            
            <?php foreach ($bookings as $booking): ?>
            <div class="booking-card" id="booking-<?php echo $booking['seat_number']; ?>">
                <h4><i class="fa fa-user"></i> Seat <?php echo $booking['seat_number']; ?> - <?php echo htmlspecialchars($booking['full_name'] ?: $booking['username']); ?></h4>
                <div class="booking-info">
                    <div class="info-section">
                        <h5><i class="fa fa-user-circle"></i> Personal Information</h5>
                        <div class="info-item">
                            <span class="info-label">Username:</span>
                            <span class="info-value"><?php echo htmlspecialchars($booking['username']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($booking['email'] ?: 'Not provided'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone:</span>
                            <span class="info-value"><?php echo htmlspecialchars($booking['phone'] ?: 'Not provided'); ?></span>
                        </div>
                    </div>
                    <div class="info-section">
                        <h5><i class="fa fa-ticket-alt"></i> Booking Details</h5>
                        <div class="info-item">
                            <span class="info-label">Booking ID:</span>
                            <span class="info-value"><code style="background: #f1f3f4; padding: 2px 6px; border-radius: 4px; font-size: 0.85rem;"><?php echo htmlspecialchars($booking['booking_id']); ?></code></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Booking Time:</span>
                            <span class="info-value"><?php echo date('M j, Y g:i A', strtotime($booking['booking_time'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Payment:</span>
                            <span class="info-value"><?php echo htmlspecialchars($booking['payment_method']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status:</span>
                            <span class="info-value">
                                <?php echo getStatusBadge($booking['status'], 'small'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: #666;">
            <i class="fa fa-info-circle" style="font-size: 3rem; margin-bottom: 1rem; color: #ddd;"></i>
            <h3>No Bookings Yet</h3>
            <p>This schedule has no confirmed bookings at the moment.</p>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
// Add click functionality to booked seats
document.addEventListener('DOMContentLoaded', function() {
    const bookedSeats = document.querySelectorAll('.seat.booked');
    
    bookedSeats.forEach(seat => {
        seat.addEventListener('click', function() {
            const seatNumber = this.getAttribute('data-seat');
            const bookingCard = document.getElementById('booking-' + seatNumber);
            
            if (bookingCard) {
                // Scroll to booking details
                bookingCard.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                
                // Highlight the booking card temporarily
                bookingCard.style.background = '#e3f2fd';
                bookingCard.style.borderLeftColor = '#1976d2';
                bookingCard.style.transform = 'scale(1.02)';
                bookingCard.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    bookingCard.style.background = '#f8f9fa';
                    bookingCard.style.borderLeftColor = '#2196f3';
                    bookingCard.style.transform = 'scale(1)';
                }, 2000);
            }
        });
    });
});
</script>

<?php require_once('../views/footer.php'); ?>
