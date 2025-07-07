<?php
require_once('../includes/db.php');
require_once('../views/header.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
$db = getDB();
// Malaysian bus types with their typical maximum capacities
$bus_types = [
    'express-bus' => ['name' => 'Express Bus', 'max_capacity' => 45],
    'luxury-coach' => ['name' => 'Luxury Coach', 'max_capacity' => 40],
    'vip-bus' => ['name' => 'VIP Bus (27-seater)', 'max_capacity' => 27],
    'super-vip' => ['name' => 'Super VIP (18-seater)', 'max_capacity' => 18],
    'city-bus' => ['name' => 'City Bus', 'max_capacity' => 60],
    'mini-bus' => ['name' => 'Mini Bus', 'max_capacity' => 25]
];
$bus = [
    'id' => '',
    'bus_number' => '',
    'license_plate' => '',
    'bus_type' => '',
    'capacity' => '',
    'company' => ''
];
$success = false;
$error = '';
$edit_mode = isset($_GET['id']);
if ($edit_mode) {
    $stmt = $db->prepare('SELECT * FROM buses WHERE id = ?');
    $stmt->execute([$_GET['id']]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$bus) {
        $error = 'Bus not found!';
        $bus = ['id'=>'','bus_number'=>'','license_plate'=>'','bus_type'=>'','capacity'=>'','company'=>''];
    }
}
// For add: auto-generate next bus number
if (!$edit_mode) {
    $bus['bus_number'] = '';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $license_plate = trim($_POST['license_plate']);
    $bus_type = $_POST['bus_type'];
    $capacity = (int)$_POST['capacity'];
    $company = $_POST['company'];
    // Validate license plate, bus type and capacity
    if (empty($license_plate)) {
        $error = 'License plate number is required.';
    } elseif (!preg_match('/^[A-Z]{3}[0-9]{4}$/i', $license_plate)) {
        $error = 'License plate must follow Malaysian format: 3 letters followed by 4 numbers (e.g., ABC1234).';
    } elseif (!array_key_exists($bus_type, $bus_types)) {
        $error = 'Invalid bus type.';
    } elseif ($capacity < 1) {
        $error = 'Capacity must be at least 1.';
    } elseif ($capacity > $bus_types[$bus_type]['max_capacity']) {
        $error = "Capacity cannot exceed {$bus_types[$bus_type]['max_capacity']} for {$bus_types[$bus_type]['name']}.";
    } else {
        // Check if license plate already exists (for both add and edit modes)
        if ($edit_mode) {
            $stmt = $db->prepare('SELECT COUNT(*) FROM buses WHERE license_plate = ? AND id != ?');
            $stmt->execute([$license_plate, $_GET['id']]);
        } else {
            $stmt = $db->prepare('SELECT COUNT(*) FROM buses WHERE license_plate = ?');
            $stmt->execute([$license_plate]);
        }

        if ($stmt->fetchColumn() > 0) {
            $error = 'This license plate number is already registered to another bus.';
        }
        if ($error == '') {
            if ($edit_mode) {
                // Get old values before updating
                $old_stmt = $db->prepare('SELECT * FROM buses WHERE id = ?');
                $old_stmt->execute([$_GET['id']]);
                $old_bus = $old_stmt->fetch(PDO::FETCH_ASSOC);

                $stmt = $db->prepare('UPDATE buses SET license_plate=?, bus_type=?, capacity=?, company=? WHERE id=?');
                $success = $stmt->execute([$license_plate, $bus_type, $capacity, $company, $_GET['id']]);

                // Send system notifications to users with bookings on this bus
                if ($success && $old_bus) {
                    // Build change details
                    $changes = [];
                    if ($old_bus['license_plate'] !== $license_plate) {
                        $changes[] = "License Plate: {$old_bus['license_plate']} → {$license_plate}";
                    }
                    if ($old_bus['bus_type'] !== $bus_type) {
                        $changes[] = "Bus Type: {$old_bus['bus_type']} → {$bus_type}";
                    }
                    if ($old_bus['capacity'] != $capacity) {
                        $changes[] = "Capacity: {$old_bus['capacity']} → {$capacity} seats";
                    }
                    if ($old_bus['company'] !== $company) {
                        $changes[] = "Company: {$old_bus['company']} → {$company}";
                    }

                    // Only send notifications if there are actual changes
                    if (!empty($changes)) {
                        $notification_sql = "SELECT DISTINCT u.id, u.username, b.bus_number
                                           FROM users u
                                           JOIN bookings bk ON u.id = bk.user_id
                                           JOIN schedules s ON bk.schedule_id = s.id
                                           JOIN buses b ON s.bus_id = b.id
                                           WHERE b.id = ? AND bk.status = 'Booked'";
                        $notification_stmt = $db->prepare($notification_sql);
                        $notification_stmt->execute([$_GET['id']]);
                        $affected_users = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Send system notifications to affected users (avoid duplicates)
                        foreach ($affected_users as $user) {
                            $change_details = implode('; ', $changes);
                            $message = "Bus Updated: {$user['bus_number']} - {$change_details}";

                            // Check if similar notification already exists in the last 24 hours
                            $check_duplicate = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND message LIKE ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)');
                            $check_duplicate->execute([$user['id'], "Bus Updated: {$user['bus_number']}%"]);

                            if ($check_duplicate->fetchColumn() == 0) {
                                $notification_insert = $db->prepare('INSERT INTO notifications (user_id, message, type, created_at, is_read) VALUES (?, ?, ?, NOW(), 0)');
                                $notification_insert->execute([$user['id'], $message, 'bus-update']);
                            }
                        }
                    }
                }
            } else {
                // Insert without bus_number first
                $stmt = $db->prepare('INSERT INTO buses (license_plate, bus_type, capacity, company) VALUES (?, ?, ?, ?)');
                $success = $stmt->execute([$license_plate, $bus_type, $capacity, $company]);
                if ($success) {
                    $bus_id = $db->lastInsertId();
                    $bus_number = 'B' . str_pad($bus_id, 3, '0', STR_PAD_LEFT);
                    $stmt = $db->prepare('UPDATE buses SET bus_number=? WHERE id=?');
                    $stmt->execute([$bus_number, $bus_id]);
                }
            }
        }
        // Re-fetch for display
        if ($edit_mode) {
            $stmt = $db->prepare('SELECT * FROM buses WHERE id = ?');
            $stmt->execute([$_GET['id']]);
            $bus = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $bus = ['id'=>'','bus_number'=>'','license_plate'=>'','bus_type'=>'','capacity'=>'','company'=>''];
        }
    }
}
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:400px;max-width:600px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <a href="admin_buses.php" class="back-btn" style="margin-bottom:1.2rem;"><i class="fa fa-arrow-left"></i> Back to Buses</a>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-bus icon-red"></i> <?php echo $edit_mode ? 'Edit' : 'Add'; ?> Bus</h1>
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:1.5rem; padding:1rem 1.5rem; border-radius:8px; border:none; background:#d4edda; color:#155724; font-weight:600; box-shadow:0 2px 8px rgba(40,167,69,0.15);">
                <i class="fa fa-check-circle" style="margin-right:0.5rem; color:#28a745;"></i>
                Bus saved successfully! All changes have been applied.
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:1.5rem; padding:1rem 1.5rem; border-radius:8px; border:none; background:#f8d7da; color:#721c24; font-weight:600; box-shadow:0 2px 8px rgba(220,53,69,0.15);">
                <i class="fa fa-exclamation-triangle" style="margin-right:0.5rem; color:#dc3545;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="post" action="" style="display:flex;flex-direction:column;gap:1rem;align-items:stretch;">
<?php if ($edit_mode): ?>
            <label style="color: #6c757d; font-weight: 600;">
                <i class="fa fa-lock" style="margin-right: 0.5rem; color: #6c757d;"></i>
                Bus ID (Read-only)
            </label>
            <input type="text" name="bus_number" class="form-control"
                   value="<?php echo htmlspecialchars($bus['bus_number'] ?? ''); ?>"
                   readonly
                   style="background-color: #f8f9fa; border: 2px dashed #dee2e6; color: #6c757d; cursor: not-allowed; font-weight: 600;">
            <small class="form-text text-muted" style="margin-bottom: 1rem;">
                <i class="fa fa-info-circle"></i> Bus ID is automatically generated and cannot be changed
            </small>
<?php endif; ?>

            <label style="font-weight: 600; color: #333;">
                <i class="fa fa-id-card" style="margin-right: 0.5rem; color: #007bff;"></i>
                License Plate Number <span style="color: #dc3545;">*</span>
            </label>
            <input type="text" name="license_plate" class="form-control"
                   value="<?php echo htmlspecialchars($bus['license_plate'] ?? ''); ?>"
                   required
                   placeholder="ABC1234"
                   pattern="[A-Z]{3}[0-9]{4}"
                   title="Malaysian format: 3 letters followed by 4 numbers (e.g., ABC1234)"
                   style="text-transform: uppercase; font-weight: 600; border: 2px solid #007bff; border-radius: 6px; letter-spacing: 1px; font-family: 'Courier New', monospace;"
                   maxlength="7"
                   minlength="7">
            <small class="form-text text-muted" style="margin-bottom: 1rem;">
                <i class="fa fa-info-circle"></i> Malaysian format: <strong>3 letters + 4 numbers</strong> (e.g., ABC1234, WMY5678)
            </small>

            <label>Bus Type:</label>
            <select name="bus_type" class="form-control" required id="busTypeSelect">
                <option value="">Select type</option>
                <?php foreach ($bus_types as $type_key => $type_info): ?>
                    <option value="<?php echo $type_key; ?>"
                            data-max-capacity="<?php echo $type_info['max_capacity']; ?>"
                            <?php if ($bus['bus_type'] == $type_key) echo 'selected'; ?>>
                        <?php echo $type_info['name']; ?> (Max: <?php echo $type_info['max_capacity']; ?> seats)
                    </option>
                <?php endforeach; ?>
            </select>
            <label>Capacity:</label>
            <input type="number" name="capacity" class="form-control" min="1" max="100"
                   value="<?php echo htmlspecialchars($bus['capacity'] ?? ''); ?>"
                   id="capacityInput" required>
            <small class="form-text text-muted" id="capacityHelp" style="font-weight: 500; transition: all 0.3s ease;">Select a bus type to see maximum capacity</small>
            <label>Company:</label>
            <input type="text" name="company" class="form-control" value="<?php echo htmlspecialchars($bus['company'] ?? ''); ?>" required>
            <button type="submit" class="bus-action-btn" style="margin-top:0.5rem;align-self:flex-start;"><i class="fa fa-save"></i> Save</button>
        </form>
    </div>
</main>

<script>
// Dynamic capacity validation based on bus type
document.getElementById('busTypeSelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const maxCapacity = selectedOption.getAttribute('data-max-capacity');
    const capacityInput = document.getElementById('capacityInput');
    const capacityHelp = document.getElementById('capacityHelp');

    if (maxCapacity) {
        capacityInput.setAttribute('max', maxCapacity);
        capacityHelp.textContent = `Maximum capacity for this bus type: ${maxCapacity} seats`;
        capacityHelp.style.color = '#6c757d';

        // If current value exceeds max, reset it
        if (parseInt(capacityInput.value) > parseInt(maxCapacity)) {
            capacityInput.value = maxCapacity;
        }
    } else {
        capacityInput.setAttribute('max', '100');
        capacityHelp.textContent = 'Select a bus type to see maximum capacity';
        capacityHelp.style.color = '#6c757d';
    }
});

// Real-time capacity validation while typing
document.getElementById('capacityInput').addEventListener('input', function() {
    const busTypeSelect = document.getElementById('busTypeSelect');
    const selectedOption = busTypeSelect.options[busTypeSelect.selectedIndex];
    const maxCapacity = selectedOption.getAttribute('data-max-capacity');
    const capacityHelp = document.getElementById('capacityHelp');
    const currentValue = parseInt(this.value);

    if (maxCapacity && currentValue > parseInt(maxCapacity)) {
        this.style.borderColor = '#dc3545';
        this.style.backgroundColor = '#fff5f5';
        capacityHelp.textContent = `⚠️ Capacity cannot exceed ${maxCapacity} seats for this bus type!`;
        capacityHelp.style.color = '#dc3545';
    } else if (maxCapacity) {
        this.style.borderColor = '#28a745';
        this.style.backgroundColor = '#f8fff8';
        capacityHelp.textContent = `✓ Valid capacity (Max: ${maxCapacity} seats)`;
        capacityHelp.style.color = '#28a745';
    } else {
        this.style.borderColor = '';
        this.style.backgroundColor = '';
        capacityHelp.textContent = 'Select a bus type to see maximum capacity';
        capacityHelp.style.color = '#6c757d';
    }
});

// Prevent form submission if capacity exceeds maximum
document.querySelector('form').addEventListener('submit', function(e) {
    const busTypeSelect = document.getElementById('busTypeSelect');
    const selectedOption = busTypeSelect.options[busTypeSelect.selectedIndex];
    const maxCapacity = selectedOption.getAttribute('data-max-capacity');
    const capacityInput = document.getElementById('capacityInput');
    const currentValue = parseInt(capacityInput.value);

    if (maxCapacity && currentValue > parseInt(maxCapacity)) {
        e.preventDefault();
        alert(`Error: Capacity cannot exceed ${maxCapacity} seats for the selected bus type.`);
        capacityInput.focus();
        return false;
    }
});

// Trigger the event on page load if a bus type is already selected
document.addEventListener('DOMContentLoaded', function() {
    const busTypeSelect = document.getElementById('busTypeSelect');
    if (busTypeSelect.value) {
        busTypeSelect.dispatchEvent(new Event('change'));
    }
});

// Malaysian license plate formatting and validation
document.querySelector('input[name="license_plate"]').addEventListener('input', function(e) {
    // Convert to uppercase
    this.value = this.value.toUpperCase();

    // Remove invalid characters (only allow letters and numbers)
    this.value = this.value.replace(/[^A-Z0-9]/g, '');

    // Limit to 7 characters max
    if (this.value.length > 7) {
        this.value = this.value.substring(0, 7);
    }

    // Visual feedback for Malaysian format (3 letters + 4 numbers)
    const malaysianFormat = /^[A-Z]{3}[0-9]{4}$/;
    const isValidLength = this.value.length === 7;
    const isValidFormat = malaysianFormat.test(this.value);

    if (isValidFormat) {
        this.style.borderColor = '#28a745';
        this.style.backgroundColor = '#f8fff9';
        this.style.boxShadow = '0 0 0 0.2rem rgba(40, 167, 69, 0.25)';
    } else if (this.value.length > 0) {
        this.style.borderColor = '#ffc107';
        this.style.backgroundColor = '#fffbf0';
        this.style.boxShadow = '0 0 0 0.2rem rgba(255, 193, 7, 0.25)';
    } else {
        this.style.borderColor = '#007bff';
        this.style.backgroundColor = '#ffffff';
        this.style.boxShadow = 'none';
    }

    // Update helper text dynamically
    const helpText = this.parentNode.querySelector('.form-text');
    if (this.value.length === 0) {
        helpText.innerHTML = '<i class="fa fa-info-circle"></i> Malaysian format: <strong>3 letters + 4 numbers</strong> (e.g., ABC1234, WMY5678)';
        helpText.style.color = '#6c757d';
    } else if (this.value.length < 3) {
        helpText.innerHTML = '<i class="fa fa-pencil"></i> Enter 3 letters first (e.g., ABC, WMY, KLM)';
        helpText.style.color = '#ffc107';
    } else if (this.value.length === 3) {
        helpText.innerHTML = '<i class="fa fa-arrow-right"></i> Now enter 4 numbers (e.g., ' + this.value + '1234)';
        helpText.style.color = '#ffc107';
    } else if (this.value.length < 7) {
        const remaining = 7 - this.value.length;
        helpText.innerHTML = '<i class="fa fa-pencil"></i> Enter ' + remaining + ' more number' + (remaining > 1 ? 's' : '') + ' (e.g., ' + this.value + '0'.repeat(remaining) + ')';
        helpText.style.color = '#ffc107';
    } else if (isValidFormat) {
        helpText.innerHTML = '<i class="fa fa-check-circle"></i> Perfect! Valid Malaysian license plate format';
        helpText.style.color = '#28a745';
    } else {
        helpText.innerHTML = '<i class="fa fa-exclamation-triangle"></i> Must be 3 letters followed by 4 numbers (e.g., ABC1234)';
        helpText.style.color = '#dc3545';
    }
});

// Prevent form submission with invalid license plate
document.querySelector('form').addEventListener('submit', function(e) {
    const licensePlateInput = document.querySelector('input[name="license_plate"]');
    const licensePlate = licensePlateInput.value.trim();

    if (!licensePlate) {
        e.preventDefault();
        alert('Please enter a license plate number.');
        licensePlateInput.focus();
        return false;
    }

    if (!/^[A-Z]{3}[0-9]{4}$/.test(licensePlate)) {
        e.preventDefault();
        alert('License plate must follow Malaysian format: 3 letters followed by 4 numbers (e.g., ABC1234).');
        licensePlateInput.focus();
        return false;
    }
});
</script>

<?php require_once('../views/footer.php'); ?>