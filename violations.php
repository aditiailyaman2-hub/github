<?php
require_once 'config/database.php';
include 'includes/header.php';

$db = new Database();
$conn = $db->connect();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $student_id = $_POST['student_id'] ?? '';
                $violation_type_id = $_POST['violation_type_id'] ?? '';
                $description = $_POST['description'] ?? '';
                $violation_date = $_POST['violation_date'] ?? '';
                $reported_by = $_POST['reported_by'] ?? '';
                
                if (empty($student_id) || empty($violation_type_id) || empty($violation_date)) {
                    $error = 'Please fill in all required fields';
                } else {
                    $stmt = $conn->prepare("INSERT INTO violations (student_id, violation_type_id, description, violation_date, reported_by) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$student_id, $violation_type_id, $description, $violation_date, $reported_by]);
                    $message = 'Violation recorded successfully';
                }
                break;
                
            case 'edit':
                $id = $_POST['id'] ?? '';
                $student_id = $_POST['student_id'] ?? '';
                $violation_type_id = $_POST['violation_type_id'] ?? '';
                $description = $_POST['description'] ?? '';
                $violation_date = $_POST['violation_date'] ?? '';
                $reported_by = $_POST['reported_by'] ?? '';
                $status = $_POST['status'] ?? 'active';
                
                if (empty($id) || empty($student_id) || empty($violation_type_id) || empty($violation_date)) {
                    $error = 'Please fill in all required fields';
                } else {
                    $stmt = $conn->prepare("UPDATE violations SET student_id = ?, violation_type_id = ?, description = ?, violation_date = ?, reported_by = ?, status = ? WHERE id = ?");
                    $stmt->execute([$student_id, $violation_type_id, $description, $violation_date, $reported_by, $status, $id]);
                    $message = 'Violation updated successfully';
                }
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? '';
                if (!empty($id)) {
                    $stmt = $conn->prepare("DELETE FROM violations WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Violation deleted successfully';
                }
                break;
        }
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Get all violations with student and violation type info
$query = "SELECT v.*, s.name as student_name, s.student_id as student_number, vt.name as violation_name, vt.severity_level, vt.points
          FROM violations v
          JOIN students s ON v.student_id = s.id
          JOIN violation_types vt ON v.violation_type_id = vt.id
          WHERE 1=1";

$params = [];

if (!empty($search)) {
    $query .= " AND (s.name LIKE ? OR s.student_id LIKE ? OR vt.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $query .= " AND v.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY v.violation_date DESC, v.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$violations = $stmt->fetchAll();

// Get all students for dropdown
$students = $conn->query("SELECT id, student_id, name FROM students ORDER BY name")->fetchAll();

// Get all violation types for dropdown
$violation_types = $conn->query("SELECT * FROM violation_types ORDER BY name")->fetchAll();
?>

<div class="page-header">
    <h1>Violation Management</h1>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ Record Violation</button>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="search-box">
    <form method="GET" action="">
        <input type="text" name="search" placeholder="Search violations..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="status">
            <option value="">All Status</option>
            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="resolved" <?php echo $status_filter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <?php if ($search || $status_filter): ?>
            <a href="violations.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Student</th>
                <th>Violation Type</th>
                <th>Severity</th>
                <th>Points</th>
                <th>Description</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($violations)): ?>
                <tr>
                    <td colspan="8" class="text-center">No violations found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($violations as $violation): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($violation['violation_date'])); ?></td>
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
                        <td><strong><?php echo $violation['points']; ?></strong></td>
                        <td><?php echo htmlspecialchars($violation['description'] ?: '-'); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $violation['status'] == 'active' ? 'warning' : 'success'; ?>">
                                <?php echo ucfirst($violation['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-secondary" onclick='editViolation(<?php echo json_encode($violation); ?>)'>Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteViolation(<?php echo $violation['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Violation Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Record New Violation</h2>
            <span class="close" onclick="closeModal('addModal')">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="student_id">Student *</label>
                <select id="student_id" name="student_id" required>
                    <option value="">Select Student</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student['id']; ?>">
                            <?php echo htmlspecialchars($student['name'] . ' (' . $student['student_id'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="violation_type_id">Violation Type *</label>
                <select id="violation_type_id" name="violation_type_id" required>
                    <option value="">Select Violation Type</option>
                    <?php foreach ($violation_types as $type): ?>
                        <option value="<?php echo $type['id']; ?>">
                            <?php echo htmlspecialchars($type['name'] . ' (' . $type['severity_level'] . ' - ' . $type['points'] . ' pts)'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="violation_date">Date *</label>
                <input type="date" id="violation_date" name="violation_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" placeholder="Additional details about the violation"></textarea>
            </div>
            <div class="form-group">
                <label for="reported_by">Reported By</label>
                <input type="text" id="reported_by" name="reported_by" placeholder="Teacher or staff name">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Record Violation</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Violation Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Violation</h2>
            <span class="close" onclick="closeModal('editModal')">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_id" name="id">
            <div class="form-group">
                <label for="edit_student_id">Student *</label>
                <select id="edit_student_id" name="student_id" required>
                    <option value="">Select Student</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student['id']; ?>">
                            <?php echo htmlspecialchars($student['name'] . ' (' . $student['student_id'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_violation_type_id">Violation Type *</label>
                <select id="edit_violation_type_id" name="violation_type_id" required>
                    <option value="">Select Violation Type</option>
                    <?php foreach ($violation_types as $type): ?>
                        <option value="<?php echo $type['id']; ?>">
                            <?php echo htmlspecialchars($type['name'] . ' (' . $type['severity_level'] . ' - ' . $type['points'] . ' pts)'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_violation_date">Date *</label>
                <input type="date" id="edit_violation_date" name="violation_date" required>
            </div>
            <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="edit_reported_by">Reported By</label>
                <input type="text" id="edit_reported_by" name="reported_by">
            </div>
            <div class="form-group">
                <label for="edit_status">Status *</label>
                <select id="edit_status" name="status" required>
                    <option value="active">Active</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Violation</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" action="" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" id="delete_id" name="id">
</form>

<script>
function editViolation(violation) {
    document.getElementById('edit_id').value = violation.id;
    document.getElementById('edit_student_id').value = violation.student_id;
    document.getElementById('edit_violation_type_id').value = violation.violation_type_id;
    document.getElementById('edit_violation_date').value = violation.violation_date;
    document.getElementById('edit_description').value = violation.description || '';
    document.getElementById('edit_reported_by').value = violation.reported_by || '';
    document.getElementById('edit_status').value = violation.status;
    openModal('editModal');
}

function deleteViolation(id) {
    if (confirm('Are you sure you want to delete this violation record?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
