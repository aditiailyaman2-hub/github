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
                $name = $_POST['name'] ?? '';
                $description = $_POST['description'] ?? '';
                $severity_level = $_POST['severity_level'] ?? '';
                $points = $_POST['points'] ?? 0;
                
                if (empty($name) || empty($severity_level)) {
                    $error = 'Please fill in all required fields';
                } else {
                    $stmt = $conn->prepare("INSERT INTO violation_types (name, description, severity_level, points) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $description, $severity_level, $points]);
                    $message = 'Violation type added successfully';
                }
                break;
                
            case 'edit':
                $id = $_POST['id'] ?? '';
                $name = $_POST['name'] ?? '';
                $description = $_POST['description'] ?? '';
                $severity_level = $_POST['severity_level'] ?? '';
                $points = $_POST['points'] ?? 0;
                
                if (empty($id) || empty($name) || empty($severity_level)) {
                    $error = 'Please fill in all required fields';
                } else {
                    $stmt = $conn->prepare("UPDATE violation_types SET name = ?, description = ?, severity_level = ?, points = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $severity_level, $points, $id]);
                    $message = 'Violation type updated successfully';
                }
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? '';
                if (!empty($id)) {
                    // Check if violation type is being used
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM violations WHERE violation_type_id = ?");
                    $stmt->execute([$id]);
                    $count = $stmt->fetch()['count'];
                    
                    if ($count > 0) {
                        $error = 'Cannot delete violation type that is being used in violation records';
                    } else {
                        $stmt = $conn->prepare("DELETE FROM violation_types WHERE id = ?");
                        $stmt->execute([$id]);
                        $message = 'Violation type deleted successfully';
                    }
                }
                break;
        }
    }
}

// Get all violation types
$violation_types = $conn->query("SELECT * FROM violation_types ORDER BY severity_level, name")->fetchAll();
?>

<div class="page-header">
    <h1>Violation Types</h1>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Violation Type</button>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Severity Level</th>
                <th>Points</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($violation_types)): ?>
                <tr>
                    <td colspan="5" class="text-center">No violation types found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($violation_types as $type): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($type['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($type['description']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($type['severity_level']); ?>">
                                <?php echo htmlspecialchars($type['severity_level']); ?>
                            </span>
                        </td>
                        <td><strong><?php echo $type['points']; ?></strong></td>
                        <td>
                            <button class="btn btn-sm btn-secondary" onclick='editType(<?php echo json_encode($type); ?>)'>Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteType(<?php echo $type['id']; ?>, '<?php echo htmlspecialchars($type['name']); ?>')">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Violation Type Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add Violation Type</h2>
            <span class="close" onclick="closeModal('addModal')">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="severity_level">Severity Level *</label>
                <select id="severity_level" name="severity_level" required>
                    <option value="">Select Severity</option>
                    <option value="Minor">Minor</option>
                    <option value="Moderate">Moderate</option>
                    <option value="Major">Major</option>
                </select>
            </div>
            <div class="form-group">
                <label for="points">Points *</label>
                <input type="number" id="points" name="points" min="0" value="0" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Type</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Violation Type Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Violation Type</h2>
            <span class="close" onclick="closeModal('editModal')">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_id" name="id">
            <div class="form-group">
                <label for="edit_name">Name *</label>
                <input type="text" id="edit_name" name="name" required>
            </div>
            <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="edit_severity_level">Severity Level *</label>
                <select id="edit_severity_level" name="severity_level" required>
                    <option value="">Select Severity</option>
                    <option value="Minor">Minor</option>
                    <option value="Moderate">Moderate</option>
                    <option value="Major">Major</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_points">Points *</label>
                <input type="number" id="edit_points" name="points" min="0" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Type</button>
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
function editType(type) {
    document.getElementById('edit_id').value = type.id;
    document.getElementById('edit_name').value = type.name;
    document.getElementById('edit_description').value = type.description || '';
    document.getElementById('edit_severity_level').value = type.severity_level;
    document.getElementById('edit_points').value = type.points;
    openModal('editModal');
}

function deleteType(id, name) {
    if (confirm('Are you sure you want to delete violation type "' + name + '"?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
