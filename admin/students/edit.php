
<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole('admin');
require_once __DIR__ . '/../../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid student ID.');
}

$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    http_response_code(404);
    exit('Student not found.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Reload and try again.';
    } else {
        $admission = trim($_POST['admission_no'] ?? '');
        $first = trim($_POST['first_name'] ?? '');
        $last = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $course = trim($_POST['course'] ?? '');

        if ($admission === '' || $first === '' || $last === '') {
            $error = 'Admission number and both names are required.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } else {
            $stmt = $conn->prepare(
                "UPDATE students
                 SET admission_no = ?, first_name = ?, last_name = ?,
                     email = ?, phone = ?, course = ?
                 WHERE student_id = ?"
            );

            $stmt->bind_param(
                "ssssssi",
                $admission, $first, $last, $email, $phone, $course, $id
            );

            if ($stmt->execute()) {
                $stmt->close();
                header('Location: index.php');
                exit;
            }

            $error = $stmt->errno === 1062
                ? 'That admission number already belongs to another student.'
                : 'Could not update the student. Please try again.';

            $stmt->close();
        }
    }
}

$values = [
    'admission_no' => $_POST['admission_no'] ?? $student['admission_no'],
    'first_name' => $_POST['first_name'] ?? $student['first_name'],
    'last_name' => $_POST['last_name'] ?? $student['last_name'],
    'email' => $_POST['email'] ?? $student['email'],
    'phone' => $_POST['phone'] ?? $student['phone'],
    'course' => $_POST['course'] ?? $student['course']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Student</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 650px; margin: 35px auto; padding: 0 18px; }
        label { display: block; margin-top: 15px; }
        input { width: 100%; box-sizing: border-box; padding: 11px; margin-top: 6px; }
        button { margin-top: 20px; padding: 11px 18px; }
        .error { color: #b91c1c; }
    </style>
</head>
<body>
    <h1>Edit Student</h1>

    <?php if ($error): ?>
        <p class="error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token"
               value="<?= e($_SESSION['csrf_token']) ?>">

        <label>Admission Number *</label>
        <input name="admission_no" required value="<?= e($values['admission_no']) ?>">

        <label>First Name *</label>
        <input name="first_name" required value="<?= e($values['first_name']) ?>">

        <label>Last Name *</label>
        <input name="last_name" required value="<?= e($values['last_name']) ?>">

        <label>Email</label>
        <input type="email" name="email" value="<?= e($values['email']) ?>">

        <label>Phone</label>
        <input name="phone" value="<?= e($values['phone']) ?>">

        <label>Course / Programme</label>
        <input name="course" value="<?= e($values['course']) ?>">

        <button type="submit">Update Student</button>
    </form>

    <p><a href="index.php">Cancel</a></p>
</body>
</html>
