<?php
require_once 'config/database.php';
include 'includes/header.php';

$db = new Database();
$conn = $db->connect();

// Get statistics
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch()['count'];
$total_violations = $conn->query("SELECT COUNT(*) as count FROM violations")->fetch()['count'];
$total_violation_types = $conn->query("SELECT COUNT(*) as count FROM violation_types")->fetch()['count'];
$active_violations = $conn->query("SELECT COUNT(*) as count FROM violations WHERE status = 'active'")->fetch()['count'];

// Get recent violations
$recent_violations = $conn->query("
    SELECT v.*, s.name as student_name, s.student_id as student_number, vt.name as violation_name, vt.severity_level
    FROM violations v
    JOIN students s ON v.student_id = s.id
    JOIN violation_types vt ON v.violation_type_id = vt.id
    ORDER BY v.created_at DESC
    LIMIT 5
")->fetchAll();

// Get top violators
$top_violators = $conn->query("
    SELECT s.name, s.student_id, COUNT(v.id) as violation_count, SUM(vt.points) as total_points
    FROM students s
    JOIN violations v ON s.id = v.student_id
    JOIN violation_types vt ON v.violation_type_id = vt.id
    WHERE v.status = 'active'
    GROUP BY s.id
    ORDER BY violation_count DESC
    LIMIT 5
")->fetchAll();

// Get violations by severity
$violations_by_severity = $conn->query("
    SELECT vt.severity_level, COUNT(v.id) as count
    FROM violations v
    JOIN violation_types vt ON v.violation_type_id = vt.id
    WHERE v.status = 'active'
    GROUP BY vt.severity_level
")->fetchAll();
?>

<div class="page-header">
    <h1>Dashboard</h1>
    <p>Overview of student violations</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-content">
            <h3><?php echo $total_students; ?></h3>
            <p>Total Students</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">⚠️</div>
        <div class="stat-content">
            <h3><?php echo $total_violations; ?></h3>
            <p>Total Violations</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">🔴</div>
        <div class="stat-content">
            <h3><?php echo $active_violations; ?></h3>
            <p>Active Violations</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-content">
            <h3><?php echo $total_violation_types; ?></h3>
            <p>Violation Types</p>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="dashboard-section">
        <h2>Recent Violations</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Violation</th>
                        <th>Severity</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_violations)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No violations recorded yet</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_violations as $violation): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($violation['student_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($violation['student_number']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($violation['violation_name']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($violation['severity_level']); ?>">
                                        <?php echo htmlspecialchars($violation['severity_level']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($violation['violation_date'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $violation['status'] == 'active' ? 'warning' : 'success'; ?>">
                                        <?php echo ucfirst($violation['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="dashboard-section">
        <h2>Top Violators</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Violations</th>
                        <th>Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_violators)): ?>
                        <tr>
                            <td colspan="3" class="text-center">No data available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($top_violators as $violator): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($violator['name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($violator['student_id']); ?></small>
                                </td>
                                <td><?php echo $violator['violation_count']; ?></td>
                                <td><strong><?php echo $violator['total_points']; ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($violations_by_severity)): ?>
<div class="dashboard-section">
    <h2>Violations by Severity</h2>
    <div class="severity-stats">
        <?php foreach ($violations_by_severity as $severity): ?>
            <div class="severity-item">
                <span class="badge badge-<?php echo strtolower($severity['severity_level']); ?>">
                    <?php echo htmlspecialchars($severity['severity_level']); ?>
                </span>
                <span class="severity-count"><?php echo $severity['count']; ?> violations</span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
