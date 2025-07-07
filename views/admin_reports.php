<?php
/*
===========================================
STUDENT PROJECT: Business Reports Dashboard
FILE: admin_reports.php
WHAT THIS FILE DOES: Shows business statistics and charts for administrators
WHY ADMINS NEED THIS: To understand how the business is performing
WHAT I LEARNED: SQL aggregate functions, data visualization, business intelligence
COURSE CONCEPTS USED:
- Advanced SQL queries with SUM(), COUNT(), AVG() functions
- JOIN operations across multiple tables
- Data visualization with HTML/CSS charts
- Business logic and analytics
- Dashboard design and user interface
- PHP date/time functions and formatting
===========================================
*/

require_once('../views/header.php');
if (session_status() === PHP_SESSION_NONE) session_start();

// Check admin access
if (!isset($_SESSION['user']) || (!$_SESSION['user']['is_staff'] && !$_SESSION['user']['is_superuser'])) {
    header('Location: ../public/login.php');
    exit();
}

require_once('../includes/db.php');
$db = getDB();

/*
STUDENT EXPLANATION: Business Analytics Section
WHAT THIS DOES: Calculates important business numbers like total money earned
WHY WE NEED THIS: Admins need to know how well the business is doing
HOW IT WORKS: We use SQL functions to add up and count data from multiple tables
WHAT I LEARNED: Advanced SQL, business intelligence, data analysis
*/

// CALCULATION 1: Total Revenue and Booking Statistics
// STUDENT NOTE: This SQL query joins 3 tables to get complete booking information
// We use SUM() to add up all fares, COUNT() to count bookings, AVG() for average
$revenue_sql = "SELECT
    SUM(r.fare) as total_revenue,
    COUNT(b.id) as total_bookings,
    AVG(r.fare) as average_fare
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN routes r ON s.route_id = r.id
    WHERE b.status = 'Booked' OR b.status = 'Completed'";

$revenue_stmt = $db->query($revenue_sql);
$revenue_data = $revenue_stmt->fetch(PDO::FETCH_ASSOC);  // Get the results

// Handle null values for better display
$revenue_data['total_revenue'] = $revenue_data['total_revenue'] ?? 0;
$revenue_data['total_bookings'] = $revenue_data['total_bookings'] ?? 0;
$revenue_data['average_fare'] = $revenue_data['average_fare'] ?? 0;

// 2. Daily Bookings (Last 7 days)
$daily_sql = "SELECT 
    DATE(b.booking_time) as booking_date,
    COUNT(*) as daily_count,
    SUM(r.fare) as daily_revenue
    FROM bookings b 
    JOIN schedules s ON b.schedule_id = s.id 
    JOIN routes r ON s.route_id = r.id 
    WHERE b.booking_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND (b.status = 'Booked' OR b.status = 'Completed')
    GROUP BY DATE(b.booking_time) 
    ORDER BY booking_date DESC";
$daily_stmt = $db->query($daily_sql);
$daily_data = $daily_stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Popular Routes Analysis
$routes_sql = "SELECT 
    CONCAT(r.source, ' → ', r.destination) as route_name,
    COUNT(b.id) as booking_count,
    SUM(r.fare) as route_revenue,
    r.fare as route_fare
    FROM bookings b 
    JOIN schedules s ON b.schedule_id = s.id 
    JOIN routes r ON s.route_id = r.id 
    WHERE b.status = 'Booked' OR b.status = 'Completed'
    GROUP BY r.id, r.source, r.destination, r.fare 
    ORDER BY booking_count DESC 
    LIMIT 5";
$routes_stmt = $db->query($routes_sql);
$popular_routes = $routes_stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Bus Company Performance
$company_sql = "SELECT 
    bu.company,
    COUNT(b.id) as bookings,
    SUM(r.fare) as revenue,
    AVG(COALESCE(f.rating, 0)) as avg_rating
    FROM buses bu
    LEFT JOIN schedules s ON bu.id = s.bus_id
    LEFT JOIN bookings b ON s.id = b.schedule_id AND (b.status = 'Booked' OR b.status = 'Completed')
    LEFT JOIN routes r ON s.route_id = r.id
    LEFT JOIN feedback f ON bu.id = f.bus_id
    GROUP BY bu.company
    ORDER BY bookings DESC";
$company_stmt = $db->query($company_sql);
$company_data = $company_stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Monthly Statistics
$monthly_sql = "SELECT 
    MONTH(b.booking_time) as month,
    YEAR(b.booking_time) as year,
    COUNT(*) as monthly_bookings,
    SUM(r.fare) as monthly_revenue
    FROM bookings b 
    JOIN schedules s ON b.schedule_id = s.id 
    JOIN routes r ON s.route_id = r.id 
    WHERE b.booking_time >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    AND (b.status = 'Booked' OR b.status = 'Completed')
    GROUP BY YEAR(b.booking_time), MONTH(b.booking_time) 
    ORDER BY year DESC, month DESC";
$monthly_stmt = $db->query($monthly_sql);
$monthly_data = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- 
HTML STRUCTURE: Admin Reports Dashboard
PURPOSE: Display business analytics and reports
STUDENT LEARNING: Data presentation, dashboard design, business intelligence
-->
<main class="reports-container">
    <div class="reports-header">
        <h1><i class="fa fa-chart-bar"></i> Business Reports & Analytics</h1>
        <p>Comprehensive business insights and performance metrics</p>
    </div>

    <!-- 
    SECTION: Key Performance Indicators (KPIs)
    PURPOSE: Display main business metrics at a glance
    STUDENT LEARNING: Business metrics presentation, data visualization
    -->
    <div class="kpi-grid">
        <div class="kpi-card revenue">
            <div class="kpi-icon">
                <i class="fa fa-dollar-sign"></i>
            </div>
            <div class="kpi-content">
                <h3>Total Revenue</h3>
                <div class="kpi-value">RM <?php echo number_format($revenue_data['total_revenue'] ?? 0, 2); ?></div>
                <small>All time earnings</small>
            </div>
        </div>

        <div class="kpi-card bookings">
            <div class="kpi-icon">
                <i class="fa fa-ticket-alt"></i>
            </div>
            <div class="kpi-content">
                <h3>Total Bookings</h3>
                <div class="kpi-value"><?php echo number_format($revenue_data['total_bookings'] ?? 0); ?></div>
                <small>Successful bookings</small>
            </div>
        </div>

        <div class="kpi-card average">
            <div class="kpi-icon">
                <i class="fa fa-chart-line"></i>
            </div>
            <div class="kpi-content">
                <h3>Average Fare</h3>
                <div class="kpi-value">RM <?php echo number_format($revenue_data['average_fare'] ?? 0, 2); ?></div>
                <small>Per booking</small>
            </div>
        </div>

        <div class="kpi-card routes">
            <div class="kpi-icon">
                <i class="fa fa-route"></i>
            </div>
            <div class="kpi-content">
                <h3>Active Routes</h3>
                <div class="kpi-value"><?php echo count($popular_routes); ?></div>
                <small>Popular routes</small>
            </div>
        </div>
    </div>

    <!-- 
    SECTION: Daily Bookings Chart
    PURPOSE: Show booking trends over the last week
    STUDENT LEARNING: Data visualization, trend analysis
    -->
    <div class="report-section">
        <div class="section-header">
            <h2><i class="fa fa-calendar-alt"></i> Daily Bookings (Last 7 Days)</h2>
        </div>
        <div class="chart-container">
            <div class="simple-chart">
                <?php if (!empty($daily_data)): ?>
                    <?php
                    $daily_counts = array_column($daily_data, 'daily_count');
                    $max_bookings = !empty($daily_counts) ? max($daily_counts) : 0;
                    foreach ($daily_data as $day):
                        $daily_count = $day['daily_count'] ?? 0;
                        $daily_revenue = $day['daily_revenue'] ?? 0;
                        $percentage = $max_bookings > 0 ? ($daily_count / $max_bookings) * 100 : 0;
                    ?>
                        <div class="chart-bar">
                            <div class="bar-container">
                                <div class="bar" style="height: <?php echo $percentage; ?>%"></div>
                                <div class="bar-value"><?php echo $daily_count; ?></div>
                            </div>
                            <div class="bar-label">
                                <?php echo date('M d', strtotime($day['booking_date'])); ?>
                            </div>
                            <div class="bar-revenue">
                                RM <?php echo number_format($daily_revenue, 0); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fa fa-info-circle"></i>
                        <p>No booking data available for the last 7 days</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 
    SECTION: Popular Routes Table
    PURPOSE: Show most booked routes and their performance
    STUDENT LEARNING: Data ranking, business analysis
    -->
    <div class="report-section">
        <div class="section-header">
            <h2><i class="fa fa-star"></i> Top 5 Popular Routes</h2>
        </div>
        <div class="table-container">
            <table class="reports-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Route</th>
                        <th>Bookings</th>
                        <th>Fare</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($popular_routes)): ?>
                        <?php foreach ($popular_routes as $index => $route): ?>
                            <tr>
                                <td>
                                    <span class="rank-badge rank-<?php echo $index + 1; ?>">
                                        #<?php echo $index + 1; ?>
                                    </span>
                                </td>
                                <td class="route-name"><?php echo htmlspecialchars($route['route_name']); ?></td>
                                <td>
                                    <span class="booking-count">
                                        <i class="fa fa-ticket-alt"></i>
                                        <?php echo $route['booking_count']; ?>
                                    </span>
                                </td>
                                <td>RM <?php echo number_format($route['route_fare'], 2); ?></td>
                                <td class="revenue-cell">RM <?php echo number_format($route['route_revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="no-data-cell">
                                <i class="fa fa-info-circle"></i>
                                No route data available
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 
    SECTION: Company Performance
    PURPOSE: Compare different bus companies
    STUDENT LEARNING: Comparative analysis, business metrics
    -->
    <div class="report-section">
        <div class="section-header">
            <h2><i class="fa fa-building"></i> Company Performance</h2>
        </div>
        <div class="company-grid">
            <?php if (!empty($company_data)): ?>
                <?php foreach ($company_data as $company): ?>
                    <div class="company-card">
                        <div class="company-header">
                            <h3><?php echo htmlspecialchars($company['company']); ?></h3>
                            <?php if ($company['avg_rating'] > 0): ?>
                                <div class="rating">
                                    <?php 
                                    $rating = round($company['avg_rating'], 1);
                                    for ($i = 1; $i <= 5; $i++): 
                                    ?>
                                        <i class="fa fa-star <?php echo $i <= $rating ? 'filled' : ''; ?>"></i>
                                    <?php endfor; ?>
                                    <span>(<?php echo $rating; ?>)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="company-stats">
                            <div class="stat">
                                <span class="stat-label">Bookings:</span>
                                <span class="stat-value"><?php echo $company['bookings'] ?? 0; ?></span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Revenue:</span>
                                <span class="stat-value">RM <?php echo number_format($company['revenue'] ?? 0, 2); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fa fa-info-circle"></i>
                    <p>No company data available</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation Back to Dashboard -->
    <div class="report-actions">
        <a href="admin_dashboard.php" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Dashboard
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fa fa-print"></i> Print Report
        </button>
    </div>
</main>

<?php require_once('../views/footer.php'); ?>
