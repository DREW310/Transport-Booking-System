<?php
require_once('../includes/db.php');
session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
if (isset($_GET['id'])) {
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM buses WHERE id = ?');
    $stmt->execute([$_GET['id']]);
}
header('Location: admin_buses.php');
exit(); 