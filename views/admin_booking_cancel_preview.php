<?php
require_once('../includes/db.php');
require_once('../includes/booking_cancellation_handler.php');
require_once('header.php');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: admin_bookings.php?error=Invalid booking ID');
    exit();
}

$booking_id = $_GET['id'];
$impact = getCancellationImpact($booking_id);

if (isset($impact['error'])) {
    header('Location: admin_bookings.php?error=' . urlencode($impact['error']));
    exit();
}
?>

<style>
.preview-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.preview-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    overflow: hidden;
}

.preview-header {
    background: linear-gradient(135deg, #ff9800, #f57c00);
    color: white;
    padding: 1.5rem;
    text-align: center;
}

.preview-content {
    padding: 2rem;
}

.impact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin: 1.5rem 0;
}

.impact-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    border-left: 4px solid #2196f3;
}

.impact-card h4 {
    margin: 0 0 0.5rem 0;
    color: #1976d2;
    font-size: 0.9rem;
}

.impact-card p {
    margin: 0;
    color: #666;
    font-size: 0.85rem;
}

.booking-details {
    background: #e3f2fd;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 1rem 0;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin: 0.5rem 0;
    padding: 0.25rem 0;
    border-bottom: 1px solid #bbdefb;
}

.detail-row:last-child {
    border-bottom: none;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-danger {
    background: #f44336;
    color: white;
}

.btn-danger:hover {
    background: #d32f2f;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.warning-box {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 1rem;
    margin: 1rem 0;
    color: #856404;
}
</style>

<div class="preview-container">
    <div class="preview-card">
        <div class="preview-header">
            <h1><i class="fa fa-exclamation-triangle"></i> Booking Cancellation Preview</h1>
            <p>Review the impact before cancelling this booking</p>
        </div>
        
        <div class="preview-content">
            <div class="booking-details">
                <h3><i class="fa fa-ticket-alt"></i> Booking Details</h3>
                <div class="detail-row">
                    <strong>Booking ID:</strong>
                    <span><?php echo htmlspecialchars($impact['booking_id']); ?></span>
                </div>
                <div class="detail-row">
                    <strong>Route:</strong>
                    <span><?php echo htmlspecialchars($impact['route']); ?></span>
                </div>
                <div class="detail-row">
                    <strong>Departure:</strong>
                    <span><?php echo date('M j, Y g:i A', strtotime($impact['departure_time'])); ?></span>
                </div>
                <div class="detail-row">
                    <strong>Seat Number:</strong>
                    <span><?php echo htmlspecialchars($impact['seat_number']); ?></span>
                </div>
                <div class="detail-row">
                    <strong>Fare Impact:</strong>
                    <span>RM <?php echo number_format($impact['fare_impact'], 2); ?></span>
                </div>
            </div>

            <h3><i class="fa fa-chart-line"></i> System Impact Analysis</h3>
            
            <div class="impact-grid">
                <div class="impact-card">
                    <h4><i class="fa fa-percentage"></i> Occupancy Rate</h4>
                    <p><?php echo $impact['current_occupancy']; ?>% → <?php echo $impact['after_occupancy']; ?>%</p>
                    <p><small>Decrease: <?php echo $impact['occupancy_change']; ?>%</small></p>
                </div>
                
                <div class="impact-card">
                    <h4><i class="fa fa-chair"></i> Available Seats</h4>
                    <p><?php echo $impact['available_seats_before']; ?> → <?php echo $impact['available_seats_after']; ?></p>
                    <p><small>+1 seat will become available</small></p>
                </div>
                
                <div class="impact-card">
                    <h4><i class="fa fa-money-bill"></i> Revenue Impact</h4>
                    <p>-RM <?php echo number_format($impact['fare_impact'], 2); ?></p>
                    <p><small>Will be excluded from reports</small></p>
                </div>
                
                <div class="impact-card">
                    <h4><i class="fa fa-bell"></i> Notifications</h4>
                    <p>User will be notified</p>
                    <p><small>System notification only</small></p>
                </div>
            </div>

            <div class="warning-box">
                <h4><i class="fa fa-info-circle"></i> Components That Will Be Updated:</h4>
                <ul style="margin: 0.5rem 0 0 1rem;">
                    <?php foreach ($impact['components_affected'] as $component => $description): ?>
                        <li><strong><?php echo ucfirst(str_replace('_', ' ', $component)); ?>:</strong> <?php echo $description; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="action-buttons">
                <a href="admin_bookings.php" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back to Bookings
                </a>
                <a href="admin_booking_cancel.php?id=<?php echo $booking_id; ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.');">
                    <i class="fa fa-ban"></i> Confirm Cancellation
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>
