<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$current_user = getCurrentUser();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Violation System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h2>📚 Student Violation System</h2>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="students.php" class="<?php echo $current_page == 'students.php' ? 'active' : ''; ?>">Students</a></li>
                <li><a href="violations.php" class="<?php echo $current_page == 'violations.php' ? 'active' : ''; ?>">Violations</a></li>
                <li><a href="violation_types.php" class="<?php echo $current_page == 'violation_types.php' ? 'active' : ''; ?>">Violation Types</a></li>
                <li><a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">Reports</a></li>
            </ul>
            <div class="nav-user">
                <span>👤 <?php echo htmlspecialchars($current_user['username']); ?></span>
                <a href="?logout=1" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>
    
    <?php
    if (isset($_GET['logout'])) {
        logout();
    }
    ?>
    
    <div class="container">
