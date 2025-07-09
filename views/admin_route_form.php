<?php
require_once('../includes/db.php');
require_once('../views/header.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
$db = getDB();
$route = [
    'source' => '',
    'destination' => '',
    'fare' => ''
];
$success = false;
$error = '';
if (isset($_GET['id'])) {
    $stmt = $db->prepare('SELECT * FROM routes WHERE id = ?');
    $stmt->execute([$_GET['id']]);
    $route = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$route) {
        $error = 'Route not found!';
        $route = ['source'=>'','destination'=>'','fare'=>''];
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $source = trim($_POST['source']);
    $destination = trim($_POST['destination']);
    $fare = (float)$_POST['fare'];

    // Validate input
    if (empty($source)) {
        $error = 'Source location is required.';
    } elseif (empty($destination)) {
        $error = 'Destination location is required.';
    } elseif ($source === $destination) {
        $error = 'Source and destination cannot be the same.';
    } elseif ($fare <= 0) {
        $error = 'Fare must be greater than 0.';
    } else {
        // Check for duplicate routes (same source, destination, and fare)
        if (isset($_GET['id'])) {
            // For edit mode, exclude current route from duplicate check
            $stmt = $db->prepare('SELECT COUNT(*) FROM routes WHERE source = ? AND destination = ? AND fare = ? AND id != ?');
            $stmt->execute([$source, $destination, $fare, $_GET['id']]);
        } else {
            // For add mode, check all routes
            $stmt = $db->prepare('SELECT COUNT(*) FROM routes WHERE source = ? AND destination = ? AND fare = ?');
            $stmt->execute([$source, $destination, $fare]);
        }

        if ($stmt->fetchColumn() > 0) {
            $error = "A route from {$source} to {$destination} with fare RM{$fare} already exists. Please use different details.";
        } else {
            // Proceed with save if no duplicates found
            if (isset($_GET['id'])) {
                // Get old values before updating
                $old_stmt = $db->prepare('SELECT * FROM routes WHERE id = ?');
                $old_stmt->execute([$_GET['id']]);
                $old_route = $old_stmt->fetch(PDO::FETCH_ASSOC);

                $stmt = $db->prepare('UPDATE routes SET source=?, destination=?, fare=? WHERE id=?');
                $success = $stmt->execute([$source, $destination, $fare, $_GET['id']]);

                // Send system notifications to users with bookings on this route
                if ($success && $old_route) {
                    // Build change details
                    $changes = [];
                    if ($old_route['source'] !== $source) {
                        $changes[] = "Source: {$old_route['source']} → {$source}";
                    }
                    if ($old_route['destination'] !== $destination) {
                        $changes[] = "Destination: {$old_route['destination']} → {$destination}";
                    }
                    if ($old_route['fare'] != $fare) {
                        $changes[] = "Fare: RM{$old_route['fare']} → RM{$fare}";
                    }

                    // Only send notifications if there are actual changes
                    if (!empty($changes)) {
                        $notification_sql = "SELECT DISTINCT u.id, u.username, r.source, r.destination
                                           FROM users u
                                           JOIN bookings bk ON u.id = bk.user_id
                                           JOIN schedules s ON bk.schedule_id = s.id
                                           JOIN routes r ON s.route_id = r.id
                                           WHERE r.id = ? AND bk.status = 'Booked'";
                        $notification_stmt = $db->prepare($notification_sql);
                        $notification_stmt->execute([$_GET['id']]);
                        $affected_users = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Send system notifications to affected users (avoid duplicates)
                        foreach ($affected_users as $user) {
                            $change_details = implode('; ', $changes);
                            $message = "Route Updated: {$source} → {$destination} - {$change_details}";

                            // Check if similar notification already exists in the last 24 hours
                            $check_duplicate = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND message LIKE ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)');
                            $check_duplicate->execute([$user['id'], "Route Updated: {$source} → {$destination}%"]);

                            if ($check_duplicate->fetchColumn() == 0) {
                                $notification_insert = $db->prepare('INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)');
                                $notification_insert->execute([$user['id'], $message]);
                            }
                        }
                    }
                }
            } else {
                $stmt = $db->prepare('INSERT INTO routes (source, destination, fare) VALUES (?, ?, ?)');
                $success = $stmt->execute([$source, $destination, $fare]);
            }
        }
    }
    // Re-fetch for display
    if (isset($_GET['id'])) {
        $stmt = $db->prepare('SELECT * FROM routes WHERE id = ?');
        $stmt->execute([$_GET['id']]);
        $route = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $route = ['source'=>'','destination'=>'','fare'=>''];
    }
}
$states = [
    'Johor', 'Kedah', 'Kelantan', 'Kuala Lumpur', 'Labuan', 'Melaka', 'Negeri Sembilan',
    'Pahang', 'Penang', 'Perak', 'Perlis', 'Putrajaya', 'Selangor', 'Terengganu'
];
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:400px;max-width:600px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <a href="admin_routes.php" class="back-btn" style="margin-bottom:1.2rem;"><i class="fa fa-arrow-left"></i> Back to Routes</a>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-road icon-red"></i> <?php echo isset($_GET['id']) ? 'Edit' : 'Add'; ?> Route</h1>
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:1.5rem; padding:1rem 1.5rem; border-radius:8px; border:none; background:#d4edda; color:#155724; font-weight:600; box-shadow:0 2px 8px rgba(40,167,69,0.15);">
                <i class="fa fa-check-circle" style="margin-right:0.5rem; color:#28a745;"></i>
                Route saved successfully! All changes have been applied.
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:1.5rem; padding:1rem 1.5rem; border-radius:8px; border:none; background:#f8d7da; color:#721c24; font-weight:600; box-shadow:0 2px 8px rgba(220,53,69,0.15);">
                <i class="fa fa-exclamation-triangle" style="margin-right:0.5rem; color:#dc3545;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="post" action="" style="display:flex;flex-direction:column;gap:1rem;align-items:stretch;">
            <label>Source:</label>
            <select name="source" class="form-control" required>
                <option value="">Select state</option>
                <?php foreach ($states as $state): ?>
                    <option value="<?php echo $state; ?>" <?php if ($route['source'] == $state) echo 'selected'; ?>><?php echo $state; ?></option>
                <?php endforeach; ?>
            </select>
            <label>Destination:</label>
            <select name="destination" class="form-control" required>
                <option value="">Select state</option>
                <?php foreach ($states as $state): ?>
                    <option value="<?php echo $state; ?>" <?php if ($route['destination'] == $state) echo 'selected'; ?>><?php echo $state; ?></option>
                <?php endforeach; ?>
            </select>
            <label>Fare (RM):</label>
            <input type="number" step="0.01" name="fare" class="form-control" min="1" max="500" value="<?php echo htmlspecialchars($route['fare'] ?? ''); ?>" required>
            <small class="form-text text-muted" style="margin-bottom: 1rem;">
                <i class="fa fa-info-circle"></i> Enter the fare amount in Malaysian Ringgit (RM)
            </small>
            <button type="submit" class="bus-action-btn" style="margin-top:0.5rem;align-self:flex-start;"><i class="fa fa-save"></i> Save</button>
        </form>
    </div>
</main>

<script>
// Prevent same source and destination selection
document.addEventListener('DOMContentLoaded', function() {
    const sourceSelect = document.querySelector('select[name="source"]');
    const destinationSelect = document.querySelector('select[name="destination"]');
    const form = document.querySelector('form');

    function validateRoute() {
        const source = sourceSelect.value;
        const destination = destinationSelect.value;

        if (source && destination && source === destination) {
            // Show error styling
            sourceSelect.style.borderColor = '#dc3545';
            destinationSelect.style.borderColor = '#dc3545';

            // Show error message
            let errorMsg = document.getElementById('route-error');
            if (!errorMsg) {
                errorMsg = document.createElement('div');
                errorMsg.id = 'route-error';
                errorMsg.style.cssText = 'color: #dc3545; font-weight: 600; margin-top: 0.5rem; padding: 0.5rem; background: #f8d7da; border-radius: 4px; border: 1px solid #f5c6cb;';
                destinationSelect.parentNode.insertBefore(errorMsg, destinationSelect.nextSibling);
            }
            errorMsg.innerHTML = '<i class="fa fa-exclamation-triangle"></i> Source and destination cannot be the same location!';
            return false;
        } else {
            // Remove error styling
            sourceSelect.style.borderColor = '';
            destinationSelect.style.borderColor = '';

            // Remove error message
            const errorMsg = document.getElementById('route-error');
            if (errorMsg) {
                errorMsg.remove();
            }
            return true;
        }
    }

    // Validate on change
    sourceSelect.addEventListener('change', validateRoute);
    destinationSelect.addEventListener('change', validateRoute);

    // Prevent form submission if validation fails
    form.addEventListener('submit', function(e) {
        if (!validateRoute()) {
            e.preventDefault();
            alert('Please select different source and destination locations.');
            return false;
        }
    });
});
</script>

<?php require_once('../views/footer.php'); ?>