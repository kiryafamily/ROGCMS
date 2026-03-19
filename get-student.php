<?php
// get-student.php - Return student data as JSON
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if ($student) {
    header('Content-Type: application/json');
    echo json_encode($student);
} else {
    header('HTTP/1.0 404 Not Found');
}
?>