<?php
require_once('../includes/db.php');
require_once('../views/header.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
$db = getDB();
$bus_types = ['single-deck', 'double-deck', 'coach'];
$bus = [
    'id' => '',
    'bus_number' => '',
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
        $bus = ['id'=>'','bus_number'=>'','bus_type'=>'','capacity'=>'','company'=>''];
    }
}
// For add: auto-generate next bus number
if (!$edit_mode) {
    $bus['bus_number'] = '';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bus_type = $_POST['bus_type'];
    $capacity = (int)$_POST['capacity'];
    $company = $_POST['company'];
    if ($capacity < 1 || $capacity > 100) {
        $error = 'Capacity must be between 1 and 100.';
    } elseif (!in_array($bus_type, $bus_types)) {
        $error = 'Invalid bus type.';
    } else {
        if ($edit_mode) {
            $stmt = $db->prepare('UPDATE buses SET bus_type=?, capacity=?, company=? WHERE id=?');
            $success = $stmt->execute([$bus_type, $capacity, $company, $_GET['id']]);
        } else {
            // Insert without bus_number first
            $stmt = $db->prepare('INSERT INTO buses (bus_type, capacity, company) VALUES (?, ?, ?)');
            $success = $stmt->execute([$bus_type, $capacity, $company]);
            if ($success) {
                $bus_id = $db->lastInsertId();
                $bus_number = 'B' . str_pad($bus_id, 3, '0', STR_PAD_LEFT);
                $stmt = $db->prepare('UPDATE buses SET bus_number=? WHERE id=?');
                $stmt->execute([$bus_number, $bus_id]);
            }
        }
        // Re-fetch for display
        if ($edit_mode) {
            $stmt = $db->prepare('SELECT * FROM buses WHERE id = ?');
            $stmt->execute([$_GET['id']]);
            $bus = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $bus = ['id'=>'','bus_number'=>'','bus_type'=>'','capacity'=>'','company'=>''];
        }
    }
}
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:400px;max-width:600px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <a href="admin_buses.php" class="btn btn-warning" style="margin-bottom:1.2rem;"><i class="fa fa-arrow-left"></i> Back to Buses</a>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-bus icon-red"></i> <?php echo $edit_mode ? 'Edit' : 'Add'; ?> Bus</h1>
        <?php if ($success): ?>
            <div class="alert alert-info" style="margin-bottom:1rem;">Bus saved successfully!</div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:1rem;">Error: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" action="">
<?php if ($edit_mode): ?>
            <label>Bus ID:</label>
            <input type="text" name="bus_number" class="form-control" value="<?php echo htmlspecialchars($bus['bus_number']); ?>" readonly>
<?php endif; ?>
            <label>Bus Type:</label>
            <select name="bus_type" class="form-control" required>
                <option value="">Select type</option>
                <?php foreach ($bus_types as $type): ?>
                    <option value="<?php echo $type; ?>" <?php if ($bus['bus_type'] == $type) echo 'selected'; ?>><?php echo ucfirst($type); ?></option>
                <?php endforeach; ?>
            </select>
            <label>Capacity:</label>
            <input type="number" name="capacity" class="form-control" min="1" max="100" value="<?php echo htmlspecialchars($bus['capacity']); ?>" required>
            <label>Company:</label>
            <input type="text" name="company" class="form-control" value="<?php echo htmlspecialchars($bus['company']); ?>" required>
            <button type="submit" class="btn btn-primary" style="margin-top:1rem;"><i class="fa fa-save"></i> Save</button>
        </form>
    </div>
</main>
<?php require_once('../views/footer.php'); ?> 