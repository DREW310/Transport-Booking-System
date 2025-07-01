<?php
require_once('../includes/db.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
$db = getDB();
$feedback = null;
$error = '';
$success = false;
if (!isset($_GET['id'])) {
    header('Location: admin_feedback.php');
    exit();
}
$id = $_GET['id'];
$stmt = $db->prepare('SELECT f.*, b.booking_id, bu.bus_number, bu.company, r.source, r.destination, s.departure_time
    FROM feedback f
    JOIN bookings b ON f.booking_id = b.id
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bu ON s.bus_id = bu.id
    JOIN routes r ON s.route_id = r.id
    WHERE f.id = ?');
$stmt->execute([$id]);
$feedback = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$feedback) {
    $error = 'Feedback not found!';
}
$tags_list = [
    'Vehicle Condition', 'Punctuality', 'Staff behavior', 'Cleanliness', 'Seat comfort', 'Driving', 'Switching Buses', 'Multiple stops'
];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $feedback) {
    $rating = (int)$_POST['rating'];
    $tags = isset($_POST['tags']) ? implode(',', $_POST['tags']) : '';
    $review = trim($_POST['review']);
    $word_count = str_word_count($review);
    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating.';
    } elseif ($word_count > 80) {
        $error = 'Review must be 80 words or less.';
    } else {
        $stmt = $db->prepare('UPDATE feedback SET rating=?, tags=?, review=? WHERE id=?');
        $stmt->execute([$rating, $tags, $review, $id]);
        header('Location: admin_feedback.php?success=1');
        exit();
    }
}
require_once('header.php');
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:400px;max-width:600px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <a href="admin_feedback.php" class="btn btn-warning" style="margin-bottom:1.2rem;"><i class="fa fa-arrow-left"></i> Return to Feedback</a>
        <a href="admin_dashboard.php" class="btn btn-secondary" style="margin-bottom:1.2rem;margin-left:1rem;"><i class="fa fa-cogs"></i> Return to Admin Panel</a>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-comments icon-red"></i> Edit Feedback</h1>
        <?php if ($error): ?><div class="alert alert-danger" style="margin-bottom:1rem;">Error: <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($feedback): ?>
        <div style="margin-bottom:1.5rem;">
            <b><?php echo htmlspecialchars($feedback['source']); ?> → <?php echo htmlspecialchars($feedback['destination']); ?></b><br>
            <span style="color:#888;"><?php echo date('D d M, H:i A', strtotime($feedback['departure_time'])); ?></span><br>
            <b><?php echo htmlspecialchars($feedback['company']); ?></b><br>
            <span style="color:#888;">Seat <?php echo htmlspecialchars($feedback['seat_number']); ?></span>
        </div>
        <form method="post" id="feedbackForm" action="">
            <div style="text-align:center;margin-bottom:1.2rem;">
                <div style="font-size:1.2rem;margin-bottom:0.5rem;">Edit rating</div>
                <div id="starRating" style="font-size:2rem;color:#ccc;cursor:pointer;">
                    <?php for ($i=1; $i<=5; $i++): ?>
                        <span class="star" data-value="<?php echo $i; ?>">&#9733;</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="<?php echo isset($_POST['rating']) ? (int)$_POST['rating'] : (int)$feedback['rating']; ?>">
                <div id="ratingLabel" style="margin-top:0.5rem;font-size:1.1rem;color:#444;"></div>
            </div>
            <div style="margin-bottom:1.2rem;">
                <div style="font-size:1.1rem;margin-bottom:0.5rem;">Edit tags</div>
                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;justify-content:center;">
                    <?php $selected_tags = isset($_POST['tags']) ? $_POST['tags'] : (isset($feedback['tags']) ? explode(',', $feedback['tags']) : []); ?>
                    <?php foreach ($tags_list as $tag): ?>
                        <label class="tag-btn">
                            <input type="checkbox" name="tags[]" value="<?php echo htmlspecialchars($tag); ?>" style="display:none;" <?php if (in_array($tag, $selected_tags)) echo 'checked'; ?>>
                            <span><?php echo htmlspecialchars($tag); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="margin-bottom:1.2rem;">
                <textarea name="review" class="form-control" rows="3" maxlength="600" placeholder="Edit review (optional, max 80 words)"><?php echo isset($_POST['review']) ? htmlspecialchars($_POST['review']) : htmlspecialchars($feedback['review']); ?></textarea>
            </div>
            <div style="display:flex;gap:1rem;justify-content:center;">
                <button type="submit" class="btn btn-primary" style="min-width:120px;">Save</button>
                <a href="admin_feedback.php" class="btn btn-secondary" style="min-width:120px;">Discard</a>
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
        // Limit review to 80 words
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            const review = this.review.value.trim();
            if (review.split(/\s+/).length > 80) {
                alert('Review must be 80 words or less.');
                e.preventDefault();
            }
        });
        </script>
        <?php endif; ?>
    </div>
</main>
<?php require_once('footer.php'); ?> 