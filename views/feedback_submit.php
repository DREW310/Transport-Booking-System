<?php
require_once('../includes/db.php');
require_once('header.php');
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
$db = getDB();
$user_id = $_SESSION['user']['id'];
$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : null;
if (!$booking_id) {
    header('Location: feedback.php');
    exit();
}
// Check booking is completed and belongs to user
$sql = 'SELECT b.id, s.bus_id, bu.bus_number, bu.company, r.source, r.destination, s.departure_time, b.seat_number
        FROM bookings b
        JOIN schedules s ON b.schedule_id = s.id
        JOIN buses bu ON s.bus_id = bu.id
        JOIN routes r ON s.route_id = r.id
        WHERE b.booking_id = ? AND b.user_id = ? AND b.status = "Completed"';
$stmt = $db->prepare($sql);
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$booking) {
    echo '<main><div class="alert alert-danger">Invalid or ineligible booking for feedback.</div></main>';
    require_once('footer.php');
    exit();
}
// Check if feedback already exists
$stmt = $db->prepare('SELECT 1 FROM feedback WHERE booking_id = ? AND user_id = ?');
$stmt->execute([$booking_id, $user_id]);
if ($stmt->fetch()) {
    echo '<main><div class="alert alert-info">You have already submitted feedback for this booking.</div></main>';
    require_once('footer.php');
    exit();
}
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)$_POST['rating'];
    $tags = isset($_POST['tags']) ? implode(',', $_POST['tags']) : '';
    $comment = trim($_POST['comment']);
    $word_count = str_word_count($comment);
    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating.';
    } elseif ($word_count > 80) {
        $error = 'Comment must be 80 words or less.';
    } else {
        $stmt = $db->prepare('INSERT INTO feedback (user_id, booking_id, bus_id, rating, tags, review, date) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$user_id, $booking['id'], $booking['bus_id'], $rating, $tags, $comment]);
        $success = true;
    }
}
$tags_list = [
    'Vehicle Condition', 'Punctuality', 'Staff behavior', 'Cleanliness', 'Seat comfort', 'Driving', 'Switching Buses', 'Multiple stops'
];
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:350px;max-width:500px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <h1 style="color:#e53935;margin-bottom:1.5rem;">Rate your trip!</h1>
        <div style="margin-bottom:1.5rem;">
            <b><?php echo htmlspecialchars($booking['source']); ?> → <?php echo htmlspecialchars($booking['destination']); ?></b><br>
            <span style="color:#888;"><?php echo date('D d M, H:i A', strtotime($booking['departure_time'])); ?></span><br>
            <b><?php echo htmlspecialchars($booking['company']); ?></b><br>
            <span style="color:#888;">Seat <?php echo htmlspecialchars($booking['seat_number']); ?></span>
        </div>
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:1rem;">Thank you for your feedback!</div>
            <a href="feedback.php" class="btn btn-secondary">Back to Feedback</a>
        <?php else: ?>
        <?php if ($error): ?><div class="alert alert-danger" style="margin-bottom:1rem;">Error: <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="post" id="feedbackForm" action="">
            <div style="text-align:center;margin-bottom:1.2rem;">
                <div style="font-size:1.2rem;margin-bottom:0.5rem;">Rate your trip experience!</div>
                <div id="starRating" style="font-size:2rem;color:#ccc;cursor:pointer;">
                    <?php for ($i=1; $i<=5; $i++): ?>
                        <span class="star" data-value="<?php echo $i; ?>">&#9733;</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="<?php echo isset($_POST['rating']) ? (int)$_POST['rating'] : 0; ?>">
                <div id="ratingLabel" style="margin-top:0.5rem;font-size:1.1rem;color:#444;"></div>
            </div>
            <div style="margin-bottom:1.2rem;">
                <div style="font-size:1.1rem;margin-bottom:0.5rem;">What did you like/dislike?</div>
                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;justify-content:center;">
                    <?php foreach ($tags_list as $tag): ?>
                        <label class="tag-btn">
                            <input type="checkbox" name="tags[]" value="<?php echo htmlspecialchars($tag); ?>" style="display:none;">
                            <span><?php echo htmlspecialchars($tag); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="margin-bottom:1.2rem;">
                <textarea name="comment" class="form-control" rows="3" maxlength="600" placeholder="Tell us more about your experience (optional, max 80 words)"><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
            </div>
            <div style="display:flex;gap:1rem;justify-content:center;">
                <button type="submit" class="btn btn-primary" style="min-width:120px;">Save</button>
                <a href="feedback.php" class="btn btn-secondary" style="min-width:120px;">Discard</a>
            </div>
        </form>
        <style>
        .star.selected, .star.hovered { color: #43a047; }
        .tag-btn { border:1px solid #bbb; border-radius:20px; padding:0.3rem 1rem; background:#fafafa; cursor:pointer; transition:all 0.2s; }
        .tag-btn input:checked + span, .tag-btn.selected { background:#43a047; color:#fff; border-color:#43a047; }
        .tag-btn span { pointer-events:none; }
        </style>
        <script>
        // Star rating logic
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingInput');
        const ratingLabel = document.getElementById('ratingLabel');
        const ratingLabels = ['', 'Terrible', 'Bad', 'Okay', 'Good', 'Excellent'];
        stars.forEach(star => {
            star.addEventListener('mouseover', function() {
                highlightStars(this.dataset.value);
            });
            star.addEventListener('mouseout', function() {
                highlightStars(ratingInput.value);
            });
            star.addEventListener('click', function() {
                ratingInput.value = this.dataset.value;
                highlightStars(this.dataset.value);
                ratingLabel.textContent = this.dataset.value + ' - ' + ratingLabels[this.dataset.value];
            });
        });
        function highlightStars(val) {
            stars.forEach(star => {
                if (star.dataset.value <= val) {
                    star.classList.add('selected');
                } else {
                    star.classList.remove('selected');
                }
            });
        }
        highlightStars(ratingInput.value);
        if (ratingInput.value) {
            ratingLabel.textContent = ratingInput.value + ' - ' + ratingLabels[ratingInput.value];
        }
        // Tag button logic
        document.querySelectorAll('.tag-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const input = this.querySelector('input');
                input.checked = !input.checked;
                this.classList.toggle('selected', input.checked);
                e.preventDefault();
            });
        });
        // Limit comment to 80 words
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            const comment = this.comment.value.trim();
            if (comment.split(/\s+/).length > 80) {
                alert('Comment must be 80 words or less.');
                e.preventDefault();
            }
        });
        </script>
        <?php endif; ?>
    </div>
</main>
<?php require_once('footer.php'); ?> 