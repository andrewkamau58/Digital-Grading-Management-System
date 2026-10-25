
<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole('admin');

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';

if (!is_string($token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('Invalid request. Return to the lecturer list and try again.');
}

$id = filter_input(INPUT_POST, 'lecturer_id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: index.php');
    exit;
}

$stmt = $conn->prepare(
    "DELETE FROM lecturers WHERE lecturer_id = ?"
);
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    $stmt->close();
    header('Location: index.php');
    exit;
}

$stmt->close();
http_response_code(500);
exit('Unable to delete lecturer. Please try again.');
