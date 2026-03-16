<?php
define('DB_HOST', 'your_db_host');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
// ... rest of your config
// ============================================
// CLASS INFORMATION
// ============================================
define('CLASS_NAME', 'P.5 Purple');
define('CLASS_TEACHER', 'Mr. Kirya Amos');
define('ACADEMIC_YEAR', '2026');
define('CURRENT_TERM', '1');
define('CLASS_SLOGAN', 'Purple Hearts, Bright Minds');

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// DATABASE CONNECTION
// ============================================
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "<br>Please check your database credentials in config.php");
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function getCurrentTerm($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM academic_terms WHERE is_active = TRUE AND year = ?");
    $stmt->execute([ACADEMIC_YEAR]);
    return $stmt->fetch();
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return $diff . ' seconds ago';
    if ($diff < 3600) return floor($diff/60) . ' minutes ago';
    if ($diff < 86400) return floor($diff/3600) . ' hours ago';
    if ($diff < 2592000) return floor($diff/86400) . ' days ago';
    return date('M j, Y', $time);
}

function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

// Get current term
$current_term = getCurrentTerm($pdo);

// ============================================
// GRADING FUNCTIONS FOR REPORT CARDS
// ============================================

// Function to get aggregate (AGG) based on score (1-9 scale)
if (!function_exists('getAggregate')) {
    function getAggregate($score) {
        if ($score >= 90) return 1;  // D1
        if ($score >= 80) return 2;  // D2
        if ($score >= 70) return 3;  // C3
        if ($score >= 60) return 4;  // C4
        if ($score >= 50) return 5;  // C5
        if ($score >= 45) return 6;  // C6
        if ($score >= 40) return 7;  // P7
        if ($score >= 35) return 8;  // P8
        return 9;  // F9 (0-34)
    }
}

// Division calculation - Based on 4 CORE subjects
if (!function_exists('getDivision')) {
    function getDivision($total_agg) {
        // For 4 core subjects (min 4, max 36)
        if ($total_agg >= 4 && $total_agg <= 12) return 'I';
        if ($total_agg >= 13 && $total_agg <= 24) return 'II';
        if ($total_agg >= 25 && $total_agg <= 29) return 'III';
        if ($total_agg >= 30 && $total_agg <= 33) return 'IV';
        if ($total_agg >= 34 && $total_agg <= 36) return 'U'; // Ungraded
        return '-';
    }
}

// Auto-generate comments
if (!function_exists('autoComment')) {
    function autoComment($score, $subject, $student_name) {
        if ($score >= 90) return "Excellent performance in $subject! Keep it up!";
        if ($score >= 80) return "Very good in $subject. Aim even higher!";
        if ($score >= 70) return "Good work in $subject. Keep practicing.";
        if ($score >= 60) return "Quite good in $subject. Can do better.";
        if ($score >= 50) return "Fair performance in $subject. Work harder.";
        if ($score >= 45) return "Satisfactory in $subject. Needs more effort.";
        if ($score >= 40) return "Acceptable in $subject. Put in more effort.";
        if ($score >= 35) return "Minimum pass in $subject. Seek help.";
        return "Poor performance in $subject. Needs serious attention.";
    }
}

// Optional: Test connection
if (isset($_GET['test_db'])) {
    echo "✅ Database connection successful!";
    echo "<br>Host: " . DB_HOST;
    echo "<br>Database: " . DB_NAME;
    echo "<br>User: " . DB_USER;
    exit;
}
?>