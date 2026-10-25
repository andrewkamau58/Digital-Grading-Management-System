
<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole('admin');
require_once __DIR__ . '/../../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please reload and try again.';
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
                "INSERT INTO students
                (admission_no, first_name, last_name, email, phone, course)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssssss",
                $admission, $first, $last, $email, $phone, $course
            );

            if ($stmt->execute()) {
                $stmt->close();
                header('Location: index.php');
                exit;
            }

            $error = $stmt->errno === 1062
                ? 'That admission number already exists.'
                : 'Could not save the student. Please try again.';

            $stmt->close();
        }
    }
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Student</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 650px; margin: 35px auto; padding: 0 18px; }
        label { display: block; margin-top: 15px; }
        input { width: 100%; box-sizing: border-box; padding: 11px; margin-top: 6px; }
        button { margin-top: 20px; padding: 11px 18px; background: #1d4ed8; color: white; border: 0; cursor: pointer; }
        .error { color: #b91c1c; }
    </style>
</head>
<body>
    <h1>Register Student</h1>

    <?php if ($error): ?>
        <p class="error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token"
               value="<?= e($_SESSION['csrf_token']) ?>">

        <label>Admission Number *</label>
        <input name="admission_no" required
               value="<?= e($_POST['admission_no'] ?? '') ?>">

        <label>First Name *</label>
        <input name="first_name" required
               value="<?= e($_POST['first_name'] ?? '') ?>">

        <label>Last Name *</label>
        <input name="last_name" required
               value="<?= e($_POST['last_name'] ?? '') ?>">

        <label>Email</label>
        <input type="email" name="email"
               value="<?= e($_POST['email'] ?? '') ?>">

        <label>Phone</label>
        <input name="phone"
               value="<?= e($_POST['phone'] ?? '') ?>">

        <label>Course / Programme</label>
        <input name="course"
               value="<?= e($_POST['course'] ?? '') ?>">

        <button type="submit">Save Student</button>
    </form>

    <p><a href="index.php">Cancel and return to students</a></p>
</body>
</html>
