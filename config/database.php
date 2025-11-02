<?php
// Database configuration
class Database {
    private $db_file = __DIR__ . '/../database/violations.db';
    private $conn;

    public function connect() {
        try {
            $this->conn = new PDO("sqlite:" . $this->db_file);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $this->conn;
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function initialize() {
        $conn = $this->connect();
        
        // Create users table
        $conn->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'admin',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Create students table
        $conn->exec("CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            grade TEXT NOT NULL,
            class TEXT NOT NULL,
            contact TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Create violation_types table
        $conn->exec("CREATE TABLE IF NOT EXISTS violation_types (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            severity_level TEXT NOT NULL,
            points INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Create violations table
        $conn->exec("CREATE TABLE IF NOT EXISTS violations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER NOT NULL,
            violation_type_id INTEGER NOT NULL,
            description TEXT,
            violation_date DATE NOT NULL,
            reported_by TEXT,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (violation_type_id) REFERENCES violation_types(id)
        )");

        // Insert default admin user (username: admin, password: admin123)
        $default_password = password_hash('admin123', PASSWORD_BCRYPT);
        try {
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute(['admin', $default_password, 'admin']);
        } catch(PDOException $e) {
            // User already exists, ignore
        }

        // Insert default violation types
        $default_violations = [
            ['Late to Class', 'Student arrives late to class', 'Minor', 5],
            ['Uniform Violation', 'Not wearing proper uniform', 'Minor', 10],
            ['Disruptive Behavior', 'Causing disruption in class', 'Moderate', 15],
            ['Fighting', 'Physical altercation with another student', 'Major', 30],
            ['Cheating', 'Academic dishonesty', 'Major', 25],
            ['Truancy', 'Unexcused absence', 'Moderate', 20],
            ['Vandalism', 'Damaging school property', 'Major', 35],
            ['Bullying', 'Harassing or intimidating others', 'Major', 40]
        ];

        foreach ($default_violations as $violation) {
            try {
                $stmt = $conn->prepare("INSERT INTO violation_types (name, description, severity_level, points) VALUES (?, ?, ?, ?)");
                $stmt->execute($violation);
            } catch(PDOException $e) {
                // Violation type already exists, ignore
            }
        }

        return true;
    }
}
?>
