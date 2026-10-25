
<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Please log in first.');
}

require_once __DIR__ . '/../config/database.php';

$userId = (int) $_SESSION['user_id'];
$error = '';
$success = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Invalid form token. Refresh and try again.');
    }

    if (!is_string($current) || !is_string($new) ||
        !is_string($confirm)) {
        $error = 'Invalid password input.';
    } elseif (strlen($new) < 10) {
        $error = 'The new password must contain at least 10 characters.';
    } elseif ($new !== $confirm) {
        $error = 'The new passwords do not match.';
    } elseif ($new === $current) {
        $error = 'Choose a different password.';
    } else {
        $stmt = $conn->prepare(
            "SELECT password FROM users WHERE user_id = ? LIMIT 1"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($current, $user['password'])) {
            $error = 'Your current password is incorrect.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "UPDATE users SET password = ? WHERE user_id = ?"
            );
            $stmt->bind_param("si", $hash, $userId);

            if ($stmt->execute()) {
                $success = 'Password changed successfully.';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $error = 'Could not update the password.';
            }

            $stmt->close();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Change Password</title>
<style>
body{font-family:Arial,sans-serif;max-width:600px;margin:35px auto;padding:0 16px}
label{display:block;margin-top:16px}
input{display:block;box-sizing:border-box;width:100%;padding:10px;margin-top:6px}
button{padding:10px 18px;margin-top:20px;cursor:pointer}
.error{color:#b00020}.success{color:#176b32}
</style>
</head>
<body>
<h2>Change Password</h2>
<p><a href="../index.php">Return to Home</a></p>

<?php if ($error !== ''): ?>
<p class="error"><?= h($error) ?></p>
<?php endif; ?>

<?php if ($success !== ''): ?>
<p class="success"><?= h($success) ?></p>
<?php endif; ?>

<form method="post">
<input type="hidden" name="csrf_token"
value="<?= h($_SESSION['csrf_token']) ?>">

<label>Current password
<input type="password" name="current_password"
autocomplete="current-password" required>
</label>

<label>New password
<input type="password" name="new_password"
minlength="10" autocomplete="new-password" required>
</label>

<label>Confirm new password
<input type="password" name="confirm_password"
minlength="10" autocomplete="new-password" required>
</label>

<button type="submit">Change Password</button>
</form>
</body>
</html>
