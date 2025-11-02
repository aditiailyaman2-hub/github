<?php
require_once 'config/database.php';
include 'includes/header.php';

$db = new Database();
$conn = $db->connect();

// Get filter parameters
$student_filter = $_GET['student'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$severity_filter = $_GET['severity'] ?? '';

// Build query
$query = "SELECT v.*, s.name as student_name, s.student_id as student_number, s.grade, s.class,
          vt.name as violation_name, vt.severity_level, vt.points
          FROM violations v
          JOIN students s ON v.student_id = s.id
          JOIN violation_types vt ON v.violation_type_id = vt.id
          WHERE 1=1";

$params = [];

if (!empty($student_filter)) {
    $query .= " AND v.student_id = ?";
    $params[] = $student_filter;
}

if (!empty($date_from)) {
    $query .= " AND v.violation_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND v.violation_date <= ?";
    $params[] = $date_to;
}

if (!empty($severity_filter)) {
    $query .= " AND vt.severity_level = ?";
    $params[] = $severity_filter;
}

$query .= " ORDER BY v.violation_date DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$violations = $stmt->fetchAll();

// Calculate statistics
$total_violations = count($violations);
$total_points = array_sum(array_column($violations, 'points'));

// Get all students for dropdown
$students = $conn->query("SELECT id, student_id, name FROM students ORDER BY name")->fetchAll();

// Group violations by student
$violations_by_student = [];
foreach ($violations as $violation) {
    $student_id = $violation['student_id'];
    if (!isset($violations_by_student[$student_id])) {
        $violations_by_student[$student_id] = [
            'student_name' => $violation['student_name'],
            'student_number' => $violation['student_number'],
            'grade' => $violation['grade'],
            'class' => $violation['class'],
            'violations' => [],
            'total_points' => 0
        ];
    }
    $violations_by_student[$student_id]['violations'][] = $violation;
    $violations_by_student[$student_id]['total_points'] += $violation['points'];
}
?>

<div class="page-header">
    <h1>Reports & Analytics</h1>
    <button class="btn btn-primary" onclick="window.print()">🖨️ Print Report</button>
</div>

<div class="filter-section">
    <h3>Filter Options</h3>
    <form method="GET" action="" class="filter-form">
        <div class="form-row">
            <div class="form-group">
                <label for="student">Student</label>
                <select id="student" name="student">
                    <option value="">All Students</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student['id']; ?>" <?php echo $student_filter == $student['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($student['name'] . ' (' . $student['student_id'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="date_from">Date From</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            
            <div class="form-group">
                <label for="date_to">Date To</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            
            <div class="form-group">
                <label for="severity">Severity</label>
                <select id="severity" name="severity">
                    <option value="">All Severities</option>
                    <option value="Minor" <?php echo $severity_filter == 'Minor' ? 'selected' : ''; ?>>Minor</option>
                    <option value="Moderate" <?php echo $severity_filter == 'Moderate' ? 'selected' : ''; ?>>Moderate</option>
                    <option value="Major" <?php echo $severity_filter == 'Major' ? 'selected' : ''; ?>>Major</option>
                </select>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Generate Report</button>
            <a href="reports.php" class="btn btn-secondary">Clear Filters</a>
        </div>
    </form>
</div>

<div class="report-summary">
    <h3>Report Summary</h3>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3><?php echo $total_violations; ?></h3>
                <p>Total Violations</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🎯</div>
            <div class="stat-content">
                <h3><?php echo $total_points; ?></h3>
                <p>Total Points</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3><?php echo count($violations_by_student); ?></h3>
                <p>Students Involved</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-content">
                <h3><?php echo $total_violations > 0 ? number_format($total_points / $total_violations, 1) : 0; ?></h3>
                <p>Avg Points/Violation</p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($violations_by_student)): ?>
<div class="report-details">
    <h3>Detailed Report by Student</h3>
    <?php foreach ($violations_by_student as $student_data): ?>
        <div class="student-report-card">
            <div class="student-report-header">
                <div>
                    <h4><?php echo htmlspecialchars($student_data['student_name']); ?></h4>
                    <p>Student ID: <?php echo htmlspecialchars($student_data['student_number']); ?> | 
                       Grade: <?php echo htmlspecialchars($student_data['grade']); ?> | 
                       Class: <?php echo htmlspecialchars($student_data['class']); ?></p>
                </div>
                <div class="student-report-stats">
                    <span class="badge badge-major"><?php echo count($student_data['violations']); ?> Violations</span>
                    <span class="badge badge-warning"><?php echo $student_data['total_points']; ?> Points</span>
                </div>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Violation Type</th>
                        <th>Severity</th>
                        <th>Points</th>
                        <th>Description</th>
                        <th>Reported By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($student_data['violations'] as $violation): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($violation['violation_date'])); ?></td>
                            <td><?php echo htmlspecialchars($violation['violation_name']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($violation['severity_level']); ?>">
                                    <?php echo htmlspecialchars($violation['severity_level']); ?>
                                </span>
                            </td>
                            <td><strong><?php echo $violation['points']; ?></strong></td>
                            <td><?php echo htmlspecialchars($violation['description'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($violation['reported_by'] ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="alert alert-info">
    No violations found matching the selected criteria.
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
