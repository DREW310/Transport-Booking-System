<?php
require_once('controllers/feedbackController.php');
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$bookingId = isset($_GET['booking_id']) ? $_GET['booking_id'] : null;
$feedbackController = new FeedbackController();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $bookingId) {
    $feedbackText = $_POST['feedback'];
    $userId = $_SESSION['user']['id'];
    $feedbackController->submitFeedback($userId, $bookingId, $feedbackText);
    echo "Feedback submitted successfully!";
}
?>

<?php require_once('views/header.php'); ?>

<main>
    <h1>Leave Feedback for Your Booking</h1>
    <form action="feedback.php?booking_id=<?php echo $bookingId; ?>" method="post">
        <textarea name="feedback" placeholder="Your feedback..." required></textarea>
        <button type="submit">Submit Feedback</button>
    </form>
</main>

<?php require_once('views/footer.php'); ?>
