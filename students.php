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
                $name = $_POST['name'] ?? '';
                $grade = $_POST['grade'] ?? '';
                $class = $_POST['class'] ?? '';
                $contact = $_POST['contact'] ?? '';
                
                if (empty($student_id) || empty($name) || empty($grade) || empty($class)) {
                    $error = 'Please fill in all required fields';
                } else {
                    try {
                        $stmt = $conn->prepare("INSERT INTO students (student_id, name, grade, class, contact) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$student_id, $name, $grade, $class, $contact]);
                        $message = 'Student added successfully';
                    } catch (PDOException $e) {
                        $error = 'Error: Student ID already exists';
                    }
                }
                break;
                
            case 'edit':
                $id = $_POST['id'] ?? '';
                $student_id = $_POST['student_id'] ?? '';
                $name = $_POST['name'] ?? '';
                $grade = $_POST['grade'] ?? '';
                $class = $_POST['class'] ?? '';
                $contact = $_POST['contact'] ?? '';
                
                if (empty($id) || empty($student_id) || empty($name) || empty($grade) || empty($class)) {
                    $error = 'Please fill in all required fields';
                } else {
                    try {
                        $stmt = $conn->prepare("UPDATE students SET student_id = ?, name = ?, grade = ?, class = ?, contact = ? WHERE id = ?");
                        $stmt->execute([$student_id, $name, $grade, $class, $contact, $id]);
                        $message = 'Student updated successfully';
                    } catch (PDOException $e) {
                        $error = 'Error: Student ID already exists';
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? '';
                if (!empty($id)) {
                    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Student deleted successfully';
                }
                break;
        }
    }
}

// Get search parameter
$search = $_GET['search'] ?? '';

// Get all students
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE name LIKE ? OR student_id LIKE ? OR grade LIKE ? OR class LIKE ? ORDER BY name");
    $search_param = "%$search%";
    $stmt->execute([$search_param, $search_param, $search_param, $search_param]);
    $students = $stmt->fetchAll();
} else {
    $students = $conn->query("SELECT * FROM students ORDER BY name")->fetchAll();
}
?>

<div class="page-header">
    <h1>Student Management</h1>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Student</button>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="search-box">
    <form method="GET" action="">
        <input type="text" name="search" placeholder="Search students..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-secondary">Search</button>
        <?php if ($search): ?>
            <a href="students.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Grade</th>
                <th>Class</th>
                <th>Contact</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
                <tr>
                    <td colspan="6" class="text-center">No students found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                        <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($student['grade']); ?></td>
                        <td><?php echo htmlspecialchars($student['class']); ?></td>
                        <td><?php echo htmlspecialchars($student['contact']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-secondary" onclick='editStudent(<?php echo json_encode($student); ?>)'>Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name']); ?>')">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Student Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Student</h2>
            <span class="close" onclick="closeModal('addModal')">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="student_id">Student ID *</label>
                <input type="text" id="student_id" name="student_id" required>
            </div>
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="grade">Grade *</label>
                <input type="text" id="grade" name="grade" placeholder="e.g., 10, 11, 12" required>
            </div>
            <div class="form-group">
                <label for="class">Class *</label>
                <input type="text" id="class" name="class" placeholder="e.g., A, B, Science" required>
            </div>
            <div class="form-group">
                <label for="contact">Contact</label>
                <input type="text" id="contact" name="contact" placeholder="Phone or email">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Student Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Student</h2>
            <span class="close" onclick="closeModal('editModal')">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_id" name="id">
            <div class="form-group">
                <label for="edit_student_id">Student ID *</label>
                <input type="text" id="edit_student_id" name="student_id" required>
            </div>
            <div class="form-group">
                <label for="edit_name">Name *</label>
                <input type="text" id="edit_name" name="name" required>
            </div>
            <div class="form-group">
                <label for="edit_grade">Grade *</label>
                <input type="text" id="edit_grade" name="grade" required>
            </div>
            <div class="form-group">
                <label for="edit_class">Class *</label>
                <input type="text" id="edit_class" name="class" required>
            </div>
            <div class="form-group">
                <label for="edit_contact">Contact</label>
                <input type="text" id="edit_contact" name="contact">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Student</button>
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
function editStudent(student) {
    document.getElementById('edit_id').value = student.id;
    document.getElementById('edit_student_id').value = student.student_id;
    document.getElementById('edit_name').value = student.name;
    document.getElementById('edit_grade').value = student.grade;
    document.getElementById('edit_class').value = student.class;
    document.getElementById('edit_contact').value = student.contact || '';
    openModal('editModal');
}

function deleteStudent(id, name) {
    if (confirm('Are you sure you want to delete student "' + name + '"? This will also delete all their violation records.')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
