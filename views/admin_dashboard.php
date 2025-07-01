<?php
require_once('../views/header.php');
session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:400px;max-width:900px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <h1 style="text-align:center;margin-bottom:2rem;"><i class="fa fa-cogs icon-red"></i> Admin Dashboard</h1>
        <div style="display:flex;flex-wrap:wrap;gap:2rem;justify-content:center;">
            <a href="admin_buses.php" class="btn btn-admin" style="min-width:180px;"><i class="fa fa-bus"></i> Manage Buses</a>
            <a href="admin_routes.php" class="btn btn-admin" style="min-width:180px;"><i class="fa fa-road"></i> Manage Routes</a>
            <a href="admin_schedules.php" class="btn btn-admin" style="min-width:180px;"><i class="fa fa-calendar-alt"></i> Manage Schedules</a>
            <a href="admin_bookings.php" class="btn btn-admin" style="min-width:180px;"><i class="fa fa-ticket-alt"></i> View Bookings</a>
            <a href="admin_feedback.php" class="btn btn-admin" style="min-width:180px;"><i class="fa fa-comments"></i> View Feedback</a>
            <a href="admin_users.php" class="btn btn-admin" style="min-width:180px;"><i class="fa fa-users"></i> View Users</a>
        </div>
    </div>
</main>
<?php require_once('../views/footer.php'); ?> 