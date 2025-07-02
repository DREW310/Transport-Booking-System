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
    $source = $_POST['source'];
    $destination = $_POST['destination'];
    $fare = $_POST['fare'];
    if (isset($_GET['id'])) {
        $stmt = $db->prepare('UPDATE routes SET source=?, destination=?, fare=? WHERE id=?');
        $success = $stmt->execute([$source, $destination, $fare, $_GET['id']]);
    } else {
        $stmt = $db->prepare('INSERT INTO routes (source, destination, fare) VALUES (?, ?, ?)');
        $success = $stmt->execute([$source, $destination, $fare]);
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
            <div class="alert alert-info" style="margin-bottom:1rem;">Route saved successfully!</div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:1rem;">Error: <?php echo htmlspecialchars($error); ?></div>
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
            <input type="number" step="0.01" name="fare" class="form-control" min="1" max="500" value="<?php echo htmlspecialchars($route['fare']); ?>" required>
            <button type="submit" class="bus-action-btn" style="margin-top:0.5rem;align-self:flex-start;"><i class="fa fa-save"></i> Save</button>
        </form>
    </div>
</main>
<?php require_once('../views/footer.php'); ?> 