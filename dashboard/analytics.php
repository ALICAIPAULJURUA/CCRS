<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if (!isLoggedIn()) {
    redirect('../auth/login.php', 'Please login to access the dashboard.', 'error');
}

$db = Database::getInstance()->getConnection();
$role = $_SESSION['role'];

// Get time filter from request
$time_filter = $_GET['filter'] ?? 'monthly';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

// Build date conditions based on filter
$date_condition = "";
switch ($time_filter) {
    case 'daily':
        $date_condition = "DATE(submission_date) = CURDATE()";
        $group_by = "HOUR(submission_date)";
        $label_format = "%H:00";
        break;
    case 'weekly':
        $date_condition = "YEARWEEK(submission_date) = YEARWEEK(CURDATE())";
        $group_by = "DATE(submission_date)";
        $label_format = "%a, %b %d";
        break;
    case 'monthly':
        $date_condition = "YEAR(submission_date) = $year AND MONTH(submission_date) = $month";
        $group_by = "DATE(submission_date)";
        $label_format = "%b %d";
        break;
    case 'yearly':
        $date_condition = "YEAR(submission_date) = $year";
        $group_by = "MONTH(submission_date)";
        $label_format = "%M";
        break;
    default:
        $date_condition = "YEAR(submission_date) = $year AND MONTH(submission_date) = $month";
        $group_by = "DATE(submission_date)";
        $label_format = "%b %d";
}

// Get reports by time period
$time_stats = $db->prepare("
    SELECT 
        DATE_FORMAT(submission_date, '$label_format') as period,
        COUNT(*) as count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'under_investigation' THEN 1 ELSE 0 END) as investigating,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
    FROM reports
    WHERE $date_condition
    GROUP BY $group_by
    ORDER BY submission_date ASC
");
$time_stats->execute();
$trend_data = $time_stats->fetchAll();

// Get category distribution for selected period
$category_stats = $db->prepare("
    SELECT 
        rc.category_name,
        COUNT(r.id) as count
    FROM report_categories rc
    LEFT JOIN reports r ON rc.id = r.category_id
    WHERE $date_condition
    GROUP BY rc.id, rc.category_name
    ORDER BY count DESC
");
$category_stats->execute();
$categories = $category_stats->fetchAll();

// Get location distribution
$location_stats = $db->prepare("
    SELECT 
        SUBSTRING_INDEX(location, ',', 1) as area,
        COUNT(*) as count
    FROM reports
    WHERE $date_condition
    GROUP BY area
    ORDER BY count DESC
    LIMIT 10
");
$location_stats->execute();
$locations = $location_stats->fetchAll();

// Get available years for filter
$years = $db->query("
    SELECT DISTINCT YEAR(submission_date) as year 
    FROM reports 
    ORDER BY year DESC
")->fetchAll();

include '../includes/dashboard-header.php';
include '../includes/dashboard-sidebar.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Analytics & Statistics</h1>
        <p>Comprehensive view of reporting trends</p>
    </div>
    
    <!-- Time Filter Controls -->
    <div class="filter-controls">
        <div class="filter-group">
            <label>Time Period:</label>
            <select id="timeFilter" onchange="updateFilter()">
                <option value="daily" <?php echo $time_filter == 'daily' ? 'selected' : ''; ?>>Daily</option>
                <option value="weekly" <?php echo $time_filter == 'weekly' ? 'selected' : ''; ?>>This Week</option>
                <option value="monthly" <?php echo $time_filter == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                <option value="yearly" <?php echo $time_filter == 'yearly' ? 'selected' : ''; ?>>Yearly</option>
            </select>
        </div>
        
        <div id="monthYearFilter" style="display: <?php echo $time_filter == 'daily' || $time_filter == 'weekly' ? 'none' : 'flex'; ?>;">
            <?php if ($time_filter == 'monthly' || $time_filter == 'yearly'): ?>
            <div class="filter-group">
                <label>Year:</label>
                <select id="yearFilter" onchange="updateFilter()">
                    <?php foreach ($years as $y): ?>
                    <option value="<?php echo $y['year']; ?>" <?php echo $year == $y['year'] ? 'selected' : ''; ?>>
                        <?php echo $y['year']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if ($time_filter == 'monthly'): ?>
            <div class="filter-group">
                <label>Month:</label>
                <select id="monthFilter" onchange="updateFilter()">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" <?php echo $month == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Trend Chart - Reduced size -->
    <div class="recent-section">
        <h2>Report Trends</h2>
        <div class="chart-container">
            <canvas id="trendChart" height="200"></canvas>
        </div>
    </div>
    
    <!-- Summary Stats Cards -->
    <div class="stats-grid">
        <?php
        $total = array_sum(array_column($trend_data, 'count'));
        $total_pending = array_sum(array_column($trend_data, 'pending'));
        $total_resolved = array_sum(array_column($trend_data, 'resolved'));
        ?>
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($total); ?></div>
                <div class="stat-label">Total Reports</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($total_pending); ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($total_resolved); ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fas fa-percent"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total > 0 ? round(($total_resolved / $total) * 100, 1) : 0; ?>%</div>
                <div class="stat-label">Resolution Rate</div>
            </div>
        </div>
    </div>
    
    <!-- Category Distribution -->
    <div class="recent-section">
        <h2>Reports by Category</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Number of Reports</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                    <?php $percentage = $total > 0 ? round(($category['count'] / $total) * 100, 1) : 0; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                        <td><?php echo number_format($category['count']); ?></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                <span class="progress-label"><?php echo $percentage; ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Location Distribution -->
    <?php if (!empty($locations)): ?>
    <div class="recent-section">
        <h2>Reports by Location (Top 10)</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Area</th>
                        <th>Number of Reports</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($locations as $location): ?>
                    <?php $percentage = $total > 0 ? round(($location['count'] / $total) * 100, 1) : 0; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($location['area']); ?></td>
                        <td><?php echo number_format($location['count']); ?></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                <span class="progress-label"><?php echo $percentage; ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function updateFilter() {
    const filter = document.getElementById('timeFilter').value;
    const year = document.getElementById('yearFilter')?.value || '<?php echo date('Y'); ?>';
    const month = document.getElementById('monthFilter')?.value || '<?php echo date('m'); ?>';
    
    let url = 'analytics.php?filter=' + filter;
    if (filter === 'monthly' || filter === 'yearly') {
        url += '&year=' + year;
        if (filter === 'monthly') {
            url += '&month=' + month;
        }
    }
    window.location.href = url;
}

// Create chart with reduced height
const ctx = document.getElementById('trendChart').getContext('2d');
const trendData = <?php echo json_encode($trend_data); ?>;
const labels = trendData.map(item => item.period);
const counts = trendData.map(item => item.count);
const pending = trendData.map(item => item.pending);
const resolved = trendData.map(item => item.resolved);

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Total Reports',
                data: counts,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Pending',
                data: pending,
                borderColor: '#f39c12',
                backgroundColor: 'rgba(243, 156, 18, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Resolved',
                data: resolved,
                borderColor: '#27ae60',
                backgroundColor: 'rgba(39, 174, 96, 0.1)',
                tension: 0.4,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    boxWidth: 10,
                    font: { size: 11 }
                }
            },
            tooltip: {
                mode: 'index',
                intersect: false,
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            },
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 45,
                    font: { size: 10 }
                }
            }
        }
    }
});
</script>

<style>
.filter-controls {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-weight: 500;
    color: #666;
    font-size: 0.85rem;
}

.filter-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    background: white;
    cursor: pointer;
}

#monthYearFilter {
    display: flex;
    gap: 20px;
}

.chart-container {
    padding: 20px;
    background: white;
    border-radius: 5px;
    max-height: 320px;
}

@media (max-width: 768px) {
    .filter-controls {
        flex-direction: column;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .filter-group select {
        width: 100%;
    }
    
    #monthYearFilter {
        flex-direction: column;
        width: 100%;
    }
    
    .chart-container {
        max-height: 280px;
    }
}
</style>

<?php include '../includes/dashboard-footer.php'; ?>