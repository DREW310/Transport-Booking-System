<?php
// Start output buffering to prevent header issues
ob_start();

require_once('../includes/db.php');
require_once('header.php');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../public/login.php');
    exit();
}

// Redirect admin/staff to admin dashboard (they don't need notifications)
if ((isset($_SESSION['user']['is_staff']) && $_SESSION['user']['is_staff']) ||
    (isset($_SESSION['user']['is_superuser']) && $_SESSION['user']['is_superuser'])) {
    header('Location: admin_dashboard.php');
    exit();
}

$db = getDB();
$user_id = $_SESSION['user']['id'];

// Debug: Check if we have valid database connection and user ID
if (!$db) {
    $error_message = "Database connection failed.";
}
if (!$user_id) {
    $error_message = "User ID not found in session.";
}

// Handle mark as read action
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    try {
        $notification_id = $_GET['mark_read'];
        $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        $result = $stmt->execute([$notification_id, $user_id]);

        // Check how many rows were affected
        $affected_rows = $stmt->rowCount();

        if ($result && $affected_rows > 0) {
            // Add a small delay to ensure database is updated
            usleep(100000); // 0.1 second

            // Clear output buffer and redirect with success message
            ob_end_clean();
            header('Location: notifications.php?success=marked_read');
            exit();
        } else {
            $error_message = "Failed to mark notification as read. Rows affected: " . $affected_rows;
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle mark all as read action
if (isset($_GET['mark_all_read'])) {
    try {
        $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $result = $stmt->execute([$user_id]);

        // Check how many rows were affected
        $affected_rows = $stmt->rowCount();

        if ($result) {
            // Add a small delay to ensure database is updated
            usleep(100000); // 0.1 second

            // Clear output buffer and redirect with success message
            ob_end_clean();
            header('Location: notifications.php?success=marked_all_read');
            exit();
        } else {
            $error_message = "Failed to mark notifications as read. No rows affected: " . $affected_rows;
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get all notifications for the user
$stmt = $db->prepare('
    SELECT id, message, created_at, is_read 
    FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
');
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread count
$stmt = $db->prepare('SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0');
$stmt->execute([$user_id]);
$unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
?>

<style>
.notifications-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.notifications-header {
    background: linear-gradient(135deg, #2196f3, #1976d2);
    color: white;
    padding: 2rem;
    border-radius: 12px 12px 0 0;
    text-align: center;
}

.notifications-content {
    background: white;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    overflow: hidden;
}

.notifications-actions {
    padding: 1rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-item {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.2s ease;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item.unread {
    background: #fff3e0;
    border-left: 4px solid #ff9800;
    box-shadow: 0 2px 8px rgba(255, 152, 0, 0.15);
}

.notification-item.read {
    background: #f8f9fa;
    opacity: 0.7;
    border-left: 4px solid #e0e0e0;
}

/* Different colors for different notification types */
.notification-item.unread.schedule-update {
    background: #e8f5e8;
    border-left: 4px solid #4caf50;
    box-shadow: 0 2px 8px rgba(76, 175, 80, 0.15);
}

.notification-item.unread.bus-update {
    background: #fff3e0;
    border-left: 4px solid #ff9800;
    box-shadow: 0 2px 8px rgba(255, 152, 0, 0.15);
}

.notification-item.unread.route-update {
    background: #f3e5f5;
    border-left: 4px solid #9c27b0;
    box-shadow: 0 2px 8px rgba(156, 39, 176, 0.15);
}

.notification-item.unread.booking-cancelled {
    background: #ffebee;
    border-left: 4px solid #f44336;
    box-shadow: 0 2px 8px rgba(244, 67, 54, 0.15);
}

.notification-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.notification-message {
    flex: 1;
    font-size: 0.9rem;
    line-height: 1.4;
}

.notification-time {
    font-size: 0.8rem;
    color: #666;
    white-space: nowrap;
}

.notification-type {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75em;
    font-weight: bold;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.notification-type.schedule-update {
    background: #4caf50;
    color: white;
}

.notification-type.bus-update {
    background: #ff9800;
    color: white;
}

.notification-type.route-update {
    background: #9c27b0;
    color: white;
}

.notification-type.booking-cancelled {
    background: #f44336;
    color: white;
}

.change-details {
    background: #f5f5f5;
    border-radius: 6px;
    padding: 10px;
    margin: 8px 0;
    font-size: 0.9em;
}

.change-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 4px 0;
    padding: 4px 0;
    border-bottom: 1px solid #eee;
}

.change-item:last-child {
    border-bottom: none;
}

.change-label {
    font-weight: bold;
    color: #333;
    min-width: 100px;
}

.change-values {
    display: flex;
    align-items: center;
    gap: 8px;
}

.old-value {
    color: #f44336;
    text-decoration: line-through;
    opacity: 0.7;
}

.new-value {
    color: #4caf50;
    font-weight: bold;
}

.arrow {
    color: #666;
    font-size: 0.8em;
}

.notification-actions-item {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary {
    background: #2196f3;
    color: white;
}

.btn-primary:hover {
    background: #1976d2;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #2196f3;
    text-decoration: none;
    margin-bottom: 1rem;
    font-weight: 600;
}

.back-link:hover {
    color: #1976d2;
}

.alert {
    padding: 1rem;
    margin: 1rem 0;
    border-radius: 8px;
    border: 1px solid;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}
</style>

<div class="notifications-container">
    <a href="dashboard.php" class="back-link">
        <i class="fa fa-arrow-left"></i> Back to Dashboard
    </a>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php if ($_GET['success'] === 'marked_read'): ?>
                <i class="fa fa-check-circle"></i> Notification marked as read successfully!
            <?php elseif ($_GET['success'] === 'marked_all_read'): ?>
                <i class="fa fa-check-double"></i> All notifications marked as read successfully!
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="notifications-content">
        <div class="notifications-header">
            <h1><i class="fa fa-bell"></i> Notifications</h1>
            <p>Stay updated with your booking and system changes</p>
            <?php if ($unread_count > 0): ?>
                <p><strong><?php echo $unread_count; ?></strong> unread notification<?php echo $unread_count > 1 ? 's' : ''; ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($notifications)): ?>
            <div class="notifications-actions">
                <span>Total: <?php echo count($notifications); ?> notifications</span>
                <?php if ($unread_count > 0): ?>
                    <a href="notifications.php?mark_all_read=1" class="btn btn-primary btn-sm">
                        <i class="fa fa-check-double"></i> Mark All as Read
                    </a>
                <?php endif; ?>
            </div>

            <?php foreach ($notifications as $notification): ?>
                <?php
                $type = $notification['type'] ?? 'general';
                $type_class = '';
                if (strpos($notification['message'], 'Bus') !== false) $type_class = 'bus-update';
                elseif (strpos($notification['message'], 'Route') !== false) $type_class = 'route-update';
                elseif (strpos($notification['message'], 'Schedule') !== false) $type_class = 'schedule-update';
                elseif (strpos($notification['message'], 'cancelled') !== false) $type_class = 'booking-cancelled';
                ?>
                <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?> <?php echo $type_class; ?>">
                    <div class="notification-content">
                        <div class="notification-message">
                            <?php if (!$notification['is_read']): ?>
                                <strong><i class="fa fa-circle" style="color: #ff9800; font-size: 0.5rem;"></i> </strong>
                            <?php endif; ?>

                            <!-- Notification Type Badge -->
                            <?php if ($type_class): ?>
                                <span class="notification-type <?php echo $type_class; ?>">
                                    <?php
                                    switch($type_class) {
                                        case 'bus-update': echo 'Bus Update'; break;
                                        case 'route-update': echo 'Route Update'; break;
                                        case 'schedule-update': echo 'Schedule Update'; break;
                                        case 'booking-cancelled': echo 'Booking Cancelled'; break;
                                        default: echo 'Notification'; break;
                                    }
                                    ?>
                                </span>
                            <?php endif; ?>

                            <!-- Enhanced Message Display -->
                            <?php
                            $message = htmlspecialchars($notification['message']);
                            // Parse change details if present
                            if (strpos($message, ' - ') !== false) {
                                $parts = explode(' - ', $message, 2);
                                $title = $parts[0];
                                $changes = $parts[1];
                                echo "<div style='font-weight: bold; margin-bottom: 5px;'>{$title}</div>";

                                // Format changes nicely
                                $change_items = explode('; ', $changes);
                                if (count($change_items) > 1) {
                                    echo "<div class='change-details'>";
                                    foreach ($change_items as $change) {
                                        if (strpos($change, ' → ') !== false) {
                                            $change_parts = explode(' → ', $change);
                                            $label_value = explode(': ', $change_parts[0], 2);
                                            if (count($label_value) == 2) {
                                                echo "<div class='change-item'>";
                                                echo "<span class='change-label'>{$label_value[0]}:</span>";
                                                echo "<div class='change-values'>";
                                                echo "<span class='old-value'>{$label_value[1]}</span>";
                                                echo "<span class='arrow'>→</span>";
                                                echo "<span class='new-value'>{$change_parts[1]}</span>";
                                                echo "</div>";
                                                echo "</div>";
                                            }
                                        }
                                    }
                                    echo "</div>";
                                } else {
                                    echo "<div style='color: #666; font-size: 0.9em;'>{$changes}</div>";
                                }
                            } else {
                                echo $message;
                            }
                            ?>
                        </div>
                        <div class="notification-actions-item">
                            <div class="notification-time">
                                <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                                <a href="notifications.php?mark_read=<?php echo $notification['id']; ?>" 
                                   class="btn btn-secondary btn-sm">
                                    <i class="fa fa-check"></i> Mark Read
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa fa-bell-slash" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                <h3>No Notifications</h3>
                <p>You don't have any notifications yet. When there are updates to your bookings or system changes, they'll appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Check if we just marked notifications as read and update the header badge
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === 'marked_all_read' || urlParams.get('success') === 'marked_read') {
        // Hide the notification badge in the header
        const notificationBadge = document.querySelector('.notification-badge');
        if (notificationBadge) {
            notificationBadge.style.display = 'none';
        }

        // Clear the success parameter from URL after 3 seconds
        setTimeout(function() {
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, document.title, url.pathname);
        }, 3000);
    }
});
</script>

<?php require_once('footer.php'); ?>
