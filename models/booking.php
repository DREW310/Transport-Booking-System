<?php
require_once('controllers/bookingController.php');
session_start();

if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['id'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $destination = $_POST['destination'];
        $date = $_POST['date'];

        $bookingController = new BookingController();
        $bookingController->createBooking($userId, $destination, $date);
        echo "Booking created successfully!";
    }
}

?>

<form action="booking.php" method="post">
    <input type="text" name="destination" placeholder="Destination" required>
    <input type="date" name="date" required>
    <button type="submit">Create Booking</button>
</form>
