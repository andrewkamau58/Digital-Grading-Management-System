
<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole('admin');

require_once __DIR__ . '/../../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$staff_no = $first_name = $last_name = '';
$email = $phone = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid form request. Refresh the page and try again.';
    } else {
        $staff_no = trim($_POST['staff_no'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($staff_no === '' || $first_name === '' || $last_name === '') {
            $error = 'Staff number, first name and last name are required.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $sql = "INSERT INTO lecturers
                    (staff_no, first_name, last_name, email, phone)
                    VALUES (?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                'sssss',
                $staff_no,
                $first_name,
                $last_name,
                $email,
                $phone
            );

            if ($stmt->execute()) {
                header('Location: index.php');
                exit;
            }

            if ($stmt->errno === 1062) {
                $error = 'That staff number is already registered.';
            } else {
                $error = 'Unable to register lecturer. Please try again.';
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Lecturer</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 30px; }
        .container { max-width: 600px; margin: auto; background: white; padding: 25px; border-radius: 8px; }
        label { display: block; margin-top: 15px; }
        input { width: 100%; padding: 10px; box-sizing: border-box; margin-top: 5px; }
        button, .btn { display: inline-block; margin-top: 20px; padding: 10px 15px; background: #1769aa; color: white; border: 0; text-decoration: none; border-radius: 4px; cursor: pointer; }
        .error { color: #b71c1c; }
    </style>
</head>
<body>
<div class="container">
    <h1>Register Lecturer</h1>

    <?php if ($error !== ''): ?>
        <p class="error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token"
               value="<?= e($_SESSION['csrf_token']) ?>">

        <label>Staff Number *</label>
        <input type="text" name="staff_no" required maxlength="50"
               value="<?= e($staff_no) ?>">

        <label>First Name *</label>
        <input type="text" name="first_name" required maxlength="100"
               value="<?= e($first_name) ?>">

        <label>Last Name *</label>
        <input type="text" name="last_name" required maxlength="100"
               value="<?= e($last_name) ?>">

        <label>Email</label>
        <input type="email" name="email" maxlength="150"
               value="<?= e($email) ?>">

        <label>Phone</label>
        <input type="text" name="phone" maxlength="30"
               value="<?= e($phone) ?>">

        <button type="submit">Save Lecturer</button>
        <a class="btn" href="index.php">Cancel</a>
    </form>
</div>
</body>
</html>
