<?php
require_once('../includes/db.php');
require_once('header.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Prevent admin/staff from submitting user feedback
if ((isset($_SESSION['user']['is_staff']) && $_SESSION['user']['is_staff']) ||
    (isset($_SESSION['user']['is_superuser']) && $_SESSION['user']['is_superuser'])) {
    header('Location: admin_dashboard.php');
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
$stmt->execute([$booking['id'], $user_id]);
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
        $stmt = $db->prepare('INSERT INTO feedback (user_id, booking_id, bus_id, rating, tags, comment) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $booking['id'], $booking['bus_id'], $rating, $tags, $comment]);
        $success = true;
    }
}
$tags_list = [
    'Vehicle Condition', 'Punctuality', 'Staff behavior', 'Cleanliness', 'Seat comfort', 'Driving', 'Switching Buses', 'Multiple stops'
];
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2.5rem 2.5rem 2rem 2.5rem;min-width:600px;max-width:800px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-star icon-red"></i> Submit Feedback</h1>

        <!-- Trip Details -->
        <div class="card-header bg-info" style="font-size:1.1rem;font-weight:600;color:#fff;border-radius:8px 8px 0 0;">Trip Details</div>
        <div class="card-body bg-light" style="padding:1.2rem;margin-bottom:1.5rem;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div><strong>Route:</strong> <?php echo htmlspecialchars($booking['source'] . ' → ' . $booking['destination']); ?></div>
                <div><strong>Date:</strong> <?php echo date('M j, Y', strtotime($booking['departure_time'])); ?></div>
                <div><strong>Bus:</strong> <?php echo htmlspecialchars($booking['company']); ?></div>
                <div><strong>Seat:</strong> <?php echo htmlspecialchars($booking['seat_number']); ?></div>
            </div>
        </div>
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:1rem;">Thank you for your feedback!</div>
            <a href="feedback.php" class="btn btn-secondary">Back to Feedback</a>
        <?php else: ?>
        <?php if ($error): ?><div class="alert alert-danger" style="margin-bottom:1rem;">Error: <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="post" id="feedbackForm" action="">
            <!-- Rating Section -->
            <div style="margin-bottom:1.5rem;">
                <label style="font-weight:600;margin-bottom:0.5rem;display:block;">Rating (1-5 stars):</label>
                <div id="starRating" style="font-size:2rem;margin:0.5rem 0;cursor:pointer;">
                    <?php for ($i=1; $i<=5; $i++): ?>
                        <span class="star" data-value="<?php echo $i; ?>" style="color:#ddd;transition:color 0.3s ease;cursor:pointer;">&#9733;</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="<?php echo isset($_POST['rating']) ? (int)$_POST['rating'] : 0; ?>">
            </div>

            <!-- Tags Section -->
            <div style="margin-bottom:1.5rem;">
                <label style="font-weight:600;margin-bottom:0.5rem;display:block;">What aspects would you like to comment on? (Optional):</label>
                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                    <?php foreach ($tags_list as $tag): ?>
                        <label style="display:inline-flex;align-items:center;background:#f8f9fa;padding:0.3rem 0.8rem;border-radius:20px;cursor:pointer;transition:background-color 0.3s ease;" onmouseover="this.style.backgroundColor='#e9ecef'" onmouseout="this.style.backgroundColor='#f8f9fa'">
                            <input type="checkbox" name="tags[]" value="<?php echo htmlspecialchars($tag); ?>" style="margin-right:0.5rem;">
                            <?php echo htmlspecialchars($tag); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Comment Section -->
            <div style="margin-bottom:1.5rem;">
                <label for="commentBox" style="font-weight:600;margin-bottom:0.5rem;display:block;">Additional Comments (Optional, max 80 words):</label>
                <textarea name="comment" id="commentBox" rows="4" maxlength="600" class="form-control"
                    placeholder="Share your experience..."><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
                <small class="text-muted" id="wordCount">0/80 words</small>
            </div>

            <!-- Action Buttons -->
            <div style="display:flex;gap:1rem;justify-content:flex-start;">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-paper-plane"></i> Submit Feedback
                </button>
                <a href="feedback.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        <style>
        .star {
            color: #ddd;
            transition: color 0.3s ease;
            cursor: pointer;
        }
        .star.selected {
            color: #FFD700;
        }
        </style>

        <script>
        // Simple star rating functionality
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingInput');

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.dataset.value;
                ratingInput.value = rating;

                // Update star colors
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('selected');
                    } else {
                        s.classList.remove('selected');
                    }
                });
            });
        });

        // Word count functionality
        const commentBox = document.getElementById('commentBox');
        const wordCount = document.getElementById('wordCount');

        if (commentBox && wordCount) {
            commentBox.addEventListener('input', function() {
                const words = this.value.trim().split(/\s+/).filter(word => word.length > 0);
                const count = this.value.trim() === '' ? 0 : words.length;
                wordCount.textContent = count + '/80 words';

                if (count > 80) {
                    wordCount.style.color = '#dc3545';
                } else {
                    wordCount.style.color = '#6c757d';
                }
            });
        }

        // Form validation
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            if (!ratingInput.value || ratingInput.value < 1) {
                alert('Please select a rating.');
                e.preventDefault();
                return;
            }

            if (commentBox) {
                const words = commentBox.value.trim().split(/\s+/).filter(word => word.length > 0);
                const count = commentBox.value.trim() === '' ? 0 : words.length;
                if (count > 80) {
                    alert('Comment must be 80 words or less.');
                    e.preventDefault();
                    return;
                }
            }
        });
                }
            });
        }
        </script>
        </script>
        <?php endif; ?>
    </div>
</main>
<?php require_once('footer.php'); ?> 