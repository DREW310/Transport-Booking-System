<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once('../views/header.php');
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}
require_once('../includes/db.php');
$db = getDB();
$feedbacks = [];
$sql = 'SELECT f.id, b.booking_id, u.username, bu.bus_number, r.source, r.destination, f.rating, f.comment, f.created_at as date
        FROM feedback f
        JOIN bookings b ON f.booking_id = b.id
        JOIN users u ON b.user_id = u.id
        JOIN schedules s ON b.schedule_id = s.id
        JOIN buses bu ON s.bus_id = bu.id
        JOIN routes r ON s.route_id = r.id
        ORDER BY f.date DESC';
$stmt = $db->query($sql);
if ($stmt) {
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<main style="display:flex;flex-direction:column;align-items:center;min-height:80vh;">
    <div class="card" style="margin-top:2.5rem;padding:2rem;min-width:600px;max-width:1200px;width:100%;box-shadow:0 4px 24px rgba(229,57,53,0.08);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;">
            <a href="admin_dashboard.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        <h1 style="margin-bottom:1.5rem;"><i class="fa fa-comments icon-red"></i> All Feedback</h1>

        <!-- Search and Filter Section -->
        <div style="margin-bottom:1.5rem;padding:1rem;background:#f8f9fa;border-radius:8px;">
            <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
                <div style="flex:1;min-width:200px;">
                    <input type="text" id="searchInput" placeholder="Search by user, booking ID, or comment..."
                           style="width:100%;padding:0.5rem;border:1px solid #ddd;border-radius:4px;">
                </div>
                <div>
                    <select id="ratingFilter" style="padding:0.5rem;border:1px solid #ddd;border-radius:4px;">
                        <option value="">All Ratings</option>
                        <option value="5">5 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="2">2 Stars</option>
                        <option value="1">1 Star</option>
                    </select>
                </div>
                <div>
                    <button onclick="clearFilters()" class="btn btn-secondary btn-sm">Clear Filters</button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <?php if (empty($feedbacks)): ?>
                <div class="alert alert-warning" style="margin:1rem 0;">No feedback yet.</div>
            <?php else: ?>
            <table class="table table-striped table-bordered" style="font-size: 0.85rem;">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th><i class="fa fa-user"></i> User</th>
                        <th><i class="fa fa-id-card"></i> Booking ID</th>
                        <th><i class="fa fa-bus"></i> Bus</th>
                        <th><i class="fa fa-route"></i> Route</th>
                        <th><i class="fa fa-star"></i> Rating</th>
                        <th><i class="fa fa-comment"></i> Comment</th>
                        <th><i class="fa fa-calendar"></i> Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feedbacks as $fb): ?>
                    <tr class="feedback-row" data-user="<?php echo strtolower($fb['username']); ?>"
                        data-booking="<?php echo strtolower($fb['booking_id']); ?>"
                        data-comment="<?php echo strtolower($fb['comment'] ?? ''); ?>"
                        data-rating="<?php echo $fb['rating']; ?>">
                        <td>
                            <div style="font-weight: 600; color: #e53935;">
                                <?php echo htmlspecialchars($fb['username']); ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo htmlspecialchars($fb['booking_id']); ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #e53935;">
                                <?php echo htmlspecialchars($fb['bus_number']); ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo htmlspecialchars($fb['source']); ?>
                                <i class="fa fa-arrow-right" style="color: #666; margin: 0 4px;"></i>
                                <?php echo htmlspecialchars($fb['destination']); ?>
                            </div>
                        </td>
                        <td>
                            <div style="color:#FFD700; font-weight: 600;">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php if($i <= $fb['rating']): ?>★<?php else: ?>☆<?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <small style="color: #666;">
                                (<?php echo $fb['rating']; ?>/5)
                            </small>
                        </td>
                        <td style="max-width:200px;word-wrap:break-word;">
                            <div style="font-weight: 500;">
                                <?php echo htmlspecialchars($fb['comment'] ?? 'No comment'); ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo date('M j, Y', strtotime($fb['date'])); ?>
                            </div>
                            <small style="color: #666;">
                                <?php echo date('g:i A', strtotime($fb['date'])); ?>
                            </small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.bus-action-group {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.bus-action-group a:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
}
</style>

<script>
// Search and filter functionality
function filterFeedback() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const ratingFilter = document.getElementById('ratingFilter').value;
    const rows = document.querySelectorAll('.feedback-row');

    rows.forEach(row => {
        const user = row.dataset.user;
        const booking = row.dataset.booking;
        const comment = row.dataset.comment;
        const rating = row.dataset.rating;

        const matchesSearch = !searchTerm ||
            user.includes(searchTerm) ||
            booking.includes(searchTerm) ||
            comment.includes(searchTerm);

        const matchesRating = !ratingFilter || rating === ratingFilter;

        if (matchesSearch && matchesRating) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    updateResultsCount();
}

function updateResultsCount() {
    const visibleRows = document.querySelectorAll('.feedback-row[style=""], .feedback-row:not([style])');
    const totalRows = document.querySelectorAll('.feedback-row');

    // Add or update results counter
    let counter = document.getElementById('resultsCounter');
    if (!counter) {
        counter = document.createElement('div');
        counter.id = 'resultsCounter';
        counter.style.cssText = 'margin-top:0.5rem;color:#666;font-size:0.9rem;';
        document.querySelector('.table-responsive').insertBefore(counter, document.querySelector('table'));
    }

    counter.textContent = `Showing ${visibleRows.length} of ${totalRows.length} feedback entries`;
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('ratingFilter').value = '';
    filterFeedback();
}

// Add event listeners
document.getElementById('searchInput').addEventListener('input', filterFeedback);
document.getElementById('ratingFilter').addEventListener('change', filterFeedback);

// Initialize results count
document.addEventListener('DOMContentLoaded', function() {
    updateResultsCount();
});
</script>

<?php require_once('../views/footer.php'); ?>