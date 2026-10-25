
<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole('admin');

require_once __DIR__ . '/../../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Invalid form token. Refresh and try again.');
    }

    $lecturerId = filter_input(
        INPUT_POST,
        'lecturer_id',
        FILTER_VALIDATE_INT
    );

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$lecturerId || $lecturerId < 1) {
        $error = 'Please select a valid lecturer.';
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        $error = 'Username must be 3–50 characters and contain only letters, numbers, dots, underscores or hyphens.';
    } elseif (strlen($password) < 10) {
        $error = 'Password must be at least 10 characters long.';
    } else {
        // Confirm the lecturer exists and has no linked account.
        $stmt = $conn->prepare(
            "SELECT lecturer_id
             FROM lecturers
             WHERE lecturer_id = ? AND user_id IS NULL"
        );
        $stmt->bind_param("i", $lecturerId);
        $stmt->execute();
        $stmt->store_result();
        $lecturerAvailable = $stmt->num_rows === 1;
        $stmt->close();

        if (!$lecturerAvailable) {
            $error = 'This lecturer already has an account or does not exist.';
        } else {
            $role = 'lecturer';

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Create the login account.
            $stmt = $conn->prepare(
                "INSERT INTO users (username, password, role)
                 VALUES (?, ?, ?)"
            );

            $stmt->bind_param(
                "sss",
                $username,
                $passwordHash,
                $role
            );

            if (!$stmt->execute()) {
                $error = $stmt->errno === 1062
                    ? 'That username already exists. Choose another.'
                    : 'Could not create the account. Check the database schema and try again.';
                $stmt->close();
            } else {
                $userId = (int)$conn->insert_id;
                $stmt->close();

                // Link the new user to the existing lecturer.
                $stmt = $conn->prepare(
                    "UPDATE lecturers
                     SET user_id = ?
                     WHERE lecturer_id = ? AND user_id IS NULL"
                );

                $stmt->bind_param("ii", $userId, $lecturerId);
                $stmt->execute();

                if ($stmt->affected_rows === 1) {
                    $success = 'Lecturer login account created and linked successfully.';
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    // Roll back the newly created account if linking fails.
                    $stmt->close();
                    $conn->begin_transaction();

                    try {
                        $del = $conn->prepare(
                            "DELETE FROM users WHERE user_id = ?"
                        );
                        $del->bind_param("i", $userId);
                        $del->execute();
                        $del->close();
                        $conn->commit();
                    } catch (Throwable $e) {
                        $conn->rollback();
                    }

                    $error = 'The lecturer could not be linked. Refresh the page and check the lecturer record.';
                    $stmt = null;
                }

                if ($stmt) {
                    $stmt->close();
                }
            }
        }
    }
}

// List lecturers who do not yet have login accounts.
$result = $conn->query(
    "SELECT lecturer_id, staff_no, first_name, last_name
     FROM lecturers
     WHERE user_id IS NULL
     ORDER BY last_name, first_name"
);

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Lecturer Account</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        label { display: block; margin-top: 15px; }
        input, select { width: 100%; max-width: 400px; padding: 10px; }
        button { margin-top: 20px; padding: 10px 18px; }
        .error { color: #b00020; }
        .success { color: #176b32; }
    </style>
</head>
<body>
    <h2>Create Lecturer Login Account</h2>
    <p><a href="index.php">Back to Lecturer Management</a></p>

    <?php if ($error): ?>
        <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="success"><?= h($success) ?></p>
    <?php endif; ?>

    <?php if ($result->num_rows === 0): ?>
        <p>All lecturer records already have accounts, or no lecturer records exist.</p>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="csrf_token"
                   value="<?= h($_SESSION['csrf_token']) ?>">

            <label for="lecturer_id">Select lecturer</label>
            <select id="lecturer_id" name="lecturer_id" required>
                <option value="">-- Choose lecturer --</option>
                <?php while ($lecturer = $result->fetch_assoc()): ?>
                    <option value="<?= (int)$lecturer['lecturer_id'] ?>"
                        <?= (string)($_POST['lecturer_id'] ?? '') ===
                            (string)$lecturer['lecturer_id'] ? 'selected' : '' ?>>
                        <?= h($lecturer['staff_no'] . ' - ' .
                            $lecturer['first_name'] . ' ' .
                            $lecturer['last_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="username">Username</label>
            <input id="username" name="username"
                   maxlength="50" required
                   value="<?= h($_POST['username'] ?? '') ?>">

            <label for="password">Temporary password</label>
            <input type="password" id="password" name="password"
                   minlength="10" autocomplete="new-password" required>

            <button type="submit">Create Lecturer Account</button>
        </form>
    <?php endif; ?>
</body>
</html>
<?php $conn->close(); ?>
