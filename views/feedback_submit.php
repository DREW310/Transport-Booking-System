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
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;padding:1rem;">
    <div class="feedback-card" style="margin-top:2rem;padding:2.5rem;min-width:600px;max-width:800px;width:100%;">
        <h1 style="margin-bottom:2rem;text-align:center;color:#333;"><i class="fa fa-star" style="color:#FFD700;"></i> Share Your Experience</h1>

        <!-- Trip Details -->
        <div class="trip-details">
            <h3 style="margin:0 0 1.2rem 0;display:flex;align-items:center;"><i class="fa fa-bus" style="margin-right:0.5rem;"></i> Trip Details</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;font-size:1rem;">
                <div style="display:flex;align-items:center;">
                    <i class="fa fa-route" style="margin-right:0.8rem;opacity:0.8;"></i>
                    <div>
                        <strong>Route:</strong><br>
                        <span style="font-size:0.9rem;"><?php echo htmlspecialchars($booking['source'] . ' → ' . $booking['destination']); ?></span>
                    </div>
                </div>
                <div style="display:flex;align-items:center;">
                    <i class="fa fa-calendar" style="margin-right:0.8rem;opacity:0.8;"></i>
                    <div>
                        <strong>Date:</strong><br>
                        <span style="font-size:0.9rem;"><?php echo date('M j, Y', strtotime($booking['departure_time'])); ?></span>
                    </div>
                </div>
                <div style="display:flex;align-items:center;">
                    <i class="fa fa-building" style="margin-right:0.8rem;opacity:0.8;"></i>
                    <div>
                        <strong>Bus Company:</strong><br>
                        <span style="font-size:0.9rem;"><?php echo htmlspecialchars($booking['company']); ?></span>
                    </div>
                </div>
                <div style="display:flex;align-items:center;">
                    <i class="fa fa-chair" style="margin-right:0.8rem;opacity:0.8;"></i>
                    <div>
                        <strong>Seat Number:</strong><br>
                        <span style="font-size:0.9rem;"><?php echo htmlspecialchars($booking['seat_number']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($success): ?>
            <!-- Success Animation Container -->
            <div style="text-align:center;margin:2rem 0;">
                <div class="success-icon" style="font-size:4rem;color:#28a745;margin-bottom:1rem;animation:bounce 1s ease-in-out;">
                    <i class="fa fa-check-circle"></i>
                </div>

                <h2 style="color:#28a745;margin-bottom:1rem;font-weight:600;">
                    🎉 Thank You for Your Feedback!
                </h2>

                <div class="success-message" style="background:linear-gradient(135deg,#d4edda,#c3e6cb);border:2px solid #28a745;border-radius:12px;padding:2rem;margin:1.5rem 0;box-shadow:0 4px 15px rgba(40,167,69,0.2);">
                    <p style="font-size:1.1rem;color:#155724;margin-bottom:1rem;line-height:1.6;">
                        <strong>Your review has been successfully submitted!</strong>
                    </p>
                    <p style="color:#155724;margin-bottom:0;font-size:0.95rem;">
                        Your valuable feedback helps us improve our service and provide better experiences for all passengers. We truly appreciate you taking the time to share your thoughts with us.
                    </p>
                </div>

                <!-- Action Buttons -->
                <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;margin-top:2rem;">
                    <a href="feedback.php" class="btn-primary-custom" style="text-decoration:none;">
                        <i class="fa fa-list"></i> View All Feedback
                    </a>
                    <a href="dashboard.php" class="btn-secondary-custom" style="text-decoration:none;">
                        <i class="fa fa-dashboard"></i> Back to Dashboard
                    </a>
                </div>
            </div>

            <style>
            @keyframes bounce {
                0%, 20%, 60%, 100% { transform: translateY(0); }
                40% { transform: translateY(-20px); }
                80% { transform: translateY(-10px); }
            }

            .btn-primary-custom {
                background: linear-gradient(135deg, #5A9FD4, #4A90E2);
                color: white;
                padding: 0.8rem 1.5rem;
                border-radius: 8px;
                font-weight: 600;
                transition: all 0.3s ease;
                display: inline-block;
                border: none;
            }

            .btn-primary-custom:hover {
                background: linear-gradient(135deg, #4A90E2, #5A9FD4);
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(90, 159, 212, 0.3);
                color: white;
                text-decoration: none;
            }

            .btn-secondary-custom {
                background: #6c757d;
                color: white;
                padding: 0.8rem 1.5rem;
                border-radius: 8px;
                font-weight: 600;
                transition: all 0.3s ease;
                display: inline-block;
                border: none;
            }

            .btn-secondary-custom:hover {
                background: #5a6268;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
                color: white;
                text-decoration: none;
            }
            </style>
        <?php else: ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:2rem;padding:1.5rem;border-radius:8px;background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;">
                <i class="fa fa-exclamation-triangle"></i> <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" id="feedbackForm" action="">
            <!-- Rating Section -->
            <div style="margin-bottom:2rem;text-align:center;">
                <label style="font-weight:600;margin-bottom:1rem;display:block;font-size:1.1rem;color:#333;">
                    <i class="fa fa-star" style="color:#FFD700;"></i> How would you rate your trip?
                </label>
                <div id="starRating" style="margin:1rem 0;">
                    <?php for ($i=1; $i<=5; $i++): ?>
                        <span class="star" data-value="<?php echo $i; ?>">&#9733;</span>
                    <?php endfor; ?>
                </div>
                <small style="color:#666;font-style:italic;">Click on the stars to rate your experience</small>
                <input type="hidden" name="rating" id="ratingInput" value="<?php echo isset($_POST['rating']) ? (int)$_POST['rating'] : 0; ?>">
            </div>

            <!-- Tags Section -->
            <div style="margin-bottom:2rem;">
                <label style="font-weight:600;margin-bottom:1rem;display:block;font-size:1.1rem;color:#333;">
                    <i class="fa fa-tags" style="color:#5A9FD4;"></i> What aspects would you like to comment on? <span style="font-weight:normal;color:#666;">(Optional)</span>
                </label>
                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;justify-content:center;" id="tagsContainer">
                    <?php foreach ($tags_list as $tag): ?>
                        <label class="tag-label" style="cursor:pointer;">
                            <input type="checkbox" name="tags[]" value="<?php echo htmlspecialchars($tag); ?>" style="display:none;" <?php echo (isset($_POST['tags']) && in_array($tag, $_POST['tags'])) ? 'checked' : ''; ?>>
                            <span class="tag-display">
                                <i class="fa fa-check" style="margin-right:0.5rem;opacity:0;"></i><?php echo htmlspecialchars($tag); ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Comment Section -->
            <div style="margin-bottom:2rem;">
                <label for="commentBox" style="font-weight:600;margin-bottom:1rem;display:block;font-size:1.1rem;color:#333;">
                    <i class="fa fa-comment" style="color:#5A9FD4;"></i> Share your detailed experience <span style="font-weight:normal;color:#666;">(Optional)</span>
                </label>
                <textarea name="comment" id="commentBox" rows="5" maxlength="600" class="comment-box" style="width:100%;"
                    placeholder="Tell us about your journey - what went well, what could be improved..."><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
                <div style="text-align:right;margin-top:0.5rem;">
                    <small id="wordCount" style="color:#6c757d;">0/80 words</small>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="display:flex;gap:1rem;justify-content:center;margin-top:2rem;">
                <button type="submit" class="btn-submit">
                    <i class="fa fa-paper-plane"></i> Submit Feedback
                </button>
                <a href="feedback.php" class="btn-cancel">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </div>
        </form>
        <style>
        .feedback-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }

        .trip-details {
            background: linear-gradient(135deg, #5A9FD4, #4A90E2);
            color: white;
            padding: 1.2rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .star {
            color: #ddd;
            transition: all 0.2s ease;
            cursor: pointer;
            font-size: 2.2rem;
            margin-right: 0.2rem;
        }

        .star:hover {
            color: #FFD700;
            transform: scale(1.1);
        }

        .star.selected {
            color: #FFD700;
        }

        .tag-label {
            display: inline-block;
            margin: 0.3rem;
        }

        .tag-display {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            font-size: 0.9rem;
            user-select: none;
        }

        .tag-label:hover .tag-display {
            background: #e3f2fd;
            border-color: #5A9FD4;
            transform: translateY(-1px);
        }

        .tag-label.selected .tag-display {
            background: #5A9FD4;
            color: white;
            border-color: #5A9FD4;
        }

        .tag-display .fa-check {
            transition: opacity 0.3s ease;
        }

        .tag-label.selected .fa-check {
            opacity: 1 !important;
        }

        .comment-box {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            transition: border-color 0.3s ease;
            resize: vertical;
        }

        .comment-box:focus {
            border-color: #2196f3;
            outline: none;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #5A9FD4, #4A90E2);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #4A90E2, #5A9FD4);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(90, 159, 212, 0.3);
        }

        .btn-cancel {
            background: #6c757d;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Star rating functionality
            const stars = document.querySelectorAll('.star');
            const ratingInput = document.getElementById('ratingInput');
            let currentRating = parseInt(ratingInput.value) || 0;

            // Initialize stars based on existing rating
            updateStars(currentRating);

            stars.forEach((star, index) => {
                star.addEventListener('click', function() {
                    currentRating = index + 1;
                    ratingInput.value = currentRating;
                    updateStars(currentRating);
                });

                star.addEventListener('mouseover', function() {
                    updateStars(index + 1);
                });
            });

            document.getElementById('starRating').addEventListener('mouseleave', function() {
                updateStars(currentRating);
            });

            function updateStars(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.classList.add('selected');
                    } else {
                        star.classList.remove('selected');
                    }
                });
            }

            // Tag selection functionality
            const tagLabels = document.querySelectorAll('.tag-label');
            tagLabels.forEach(label => {
                const checkbox = label.querySelector('input[type="checkbox"]');
                const checkIcon = label.querySelector('.fa-check');

                // Initialize state
                if (checkbox.checked) {
                    label.classList.add('selected');
                    if (checkIcon) checkIcon.style.opacity = '1';
                }

                // Handle label click
                label.addEventListener('click', function(e) {
                    e.preventDefault();
                    checkbox.checked = !checkbox.checked;

                    if (checkbox.checked) {
                        label.classList.add('selected');
                        if (checkIcon) checkIcon.style.opacity = '1';
                    } else {
                        label.classList.remove('selected');
                        if (checkIcon) checkIcon.style.opacity = '0';
                    }
                });

                // Handle checkbox change (for form reset, etc.)
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        label.classList.add('selected');
                        if (checkIcon) checkIcon.style.opacity = '1';
                    } else {
                        label.classList.remove('selected');
                        if (checkIcon) checkIcon.style.opacity = '0';
                    }
                });
            });

            // Word count functionality
            const commentBox = document.getElementById('commentBox');
            const wordCount = document.getElementById('wordCount');

            if (commentBox && wordCount) {
                // Initial count
                updateWordCount();

                commentBox.addEventListener('input', updateWordCount);

                function updateWordCount() {
                    const words = commentBox.value.trim().split(/\s+/).filter(word => word.length > 0);
                    const count = commentBox.value.trim() === '' ? 0 : words.length;
                    wordCount.textContent = count + '/80 words';

                    if (count > 80) {
                        wordCount.style.color = '#dc3545';
                        wordCount.style.fontWeight = 'bold';
                    } else if (count > 70) {
                        wordCount.style.color = '#ff9800';
                        wordCount.style.fontWeight = 'normal';
                    } else {
                        wordCount.style.color = '#6c757d';
                        wordCount.style.fontWeight = 'normal';
                    }
                }
            }

            // Form validation
            document.getElementById('feedbackForm').addEventListener('submit', function(e) {
                if (!ratingInput.value || ratingInput.value < 1) {
                    alert('Please select a rating by clicking on the stars.');
                    e.preventDefault();
                    return;
                }

                if (commentBox) {
                    const words = commentBox.value.trim().split(/\s+/).filter(word => word.length > 0);
                    const count = commentBox.value.trim() === '' ? 0 : words.length;
                    if (count > 80) {
                        alert('Please reduce your comment to 80 words or less.');
                        e.preventDefault();
                        return;
                    }
                }
            });
        });
        </script>
        <?php endif; ?>
    </div>
</main>
<?php require_once('footer.php'); ?> 