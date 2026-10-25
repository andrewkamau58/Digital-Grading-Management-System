
<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole('admin');

require_once __DIR__ . '/../../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $studentId = filter_input(
        INPUT_POST,
        'student_id',
        FILTER_VALIDATE_INT
    );
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Invalid form token. Refresh the page and try again.');
    }

    if (!$studentId || $studentId < 1) {
        $error = 'Please select a valid student.';
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        $error = 'Username must be 3–50 characters and contain only letters, numbers, dots, underscores or hyphens.';
    } elseif (!is_string($password) || strlen($password) < 10) {
        $error = 'The password must be at least 10 characters long.';
    } else {
        $conn->begin_transaction();

        try {
            // Lock the student record while creating the account.
            $stmt = $conn->prepare(
                "SELECT student_id
                 FROM students
                 WHERE student_id = ? AND user_id IS NULL
                 FOR UPDATE"
            );
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $stmt->store_result();

            $available = $stmt->num_rows === 1;
            $stmt->close();

            if (!$available) {
                throw new RuntimeException(
                    'This student already has a login account, or the student record does not exist.'
                );
            }

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            if ($passwordHash === false) {
                throw new RuntimeException(
                    'The password could not be secured.'
                );
            }

            $role = 'student';

            // Create the student login account.
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
            $stmt->execute();

            $userId = (int)$conn->insert_id;
            $stmt->close();

            // Link the account to the existing student.
            $stmt = $conn->prepare(
                "UPDATE students
                 SET user_id = ?
                 WHERE student_id = ? AND user_id IS NULL"
            );
            $stmt->bind_param("ii", $userId, $studentId);
            $stmt->execute();

            if ($stmt->affected_rows !== 1) {
                $stmt->close();
                throw new RuntimeException(
                    'The account could not be linked to the student.'
                );
            }

            $stmt->close();
            $conn->commit();

            $success = 'Student login account created and linked successfully.';
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        } catch (Throwable $e) {
            $conn->rollback();

            if ($e instanceof mysqli_sql_exception &&
                (int)$e->getCode() === 1062) {
                $error = 'That username already exists. Please choose another.';
            } elseif ($e instanceof RuntimeException) {
                $error = $e->getMessage();
            } else {
                error_log('Student account creation failed: ' . $e->getMessage());
                $error = 'The account could not be created. Check the database configuration and try again.';
            }
        }
    }
}

// Show only students without login accounts.
$result = $conn->query(
    "SELECT student_id, admission_no, first_name, last_name
     FROM students
     WHERE user_id IS NULL
     ORDER BY last_name, first_name"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Student Login Account</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            color: #222;
        }

        label {
            display: block;
            margin-top: 16px;
        }

        input, select {
            display: block;
            width: 100%;
            max-width: 420px;
            box-sizing: border-box;
            padding: 10px;
            margin-top: 6px;
        }

        button {
            margin-top: 20px;
            padding: 11px 18px;
            cursor: pointer;
        }

        .error {
            color: #b00020;
        }

        .success {
            color: #176b32;
        }
    </style>
</head>
<body>
    <h2>Create Student Login Account</h2>

    <p>
        <a href="index.php">Back to Student Management</a> |
        <a href="../dashboard.php">Admin Dashboard</a>
    </p>

    <?php if ($error !== ''): ?>
        <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <p class="success"><?= h($success) ?></p>
    <?php endif; ?>

    <?php if ($result->num_rows === 0): ?>
        <p>
            No students without login accounts were found.
            Add a student first if you need to create another account.
        </p>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="csrf_token"
                   value="<?= h($_SESSION['csrf_token']) ?>">

            <label for="student_id">Select student</label>
            <select id="student_id" name="student_id" required>
                <option value="">-- Choose a student --</option>

                <?php while ($student = $result->fetch_assoc()): ?>
                    <option
                        value="<?= (int)$student['student_id'] ?>"
                        <?= (string)($_POST['student_id'] ?? '') ===
                            (string)$student['student_id']
                            ? 'selected' : '' ?>
                    >
                        <?= h(
                            $student['admission_no'] . ' - ' .
                            $student['first_name'] . ' ' .
                            $student['last_name']
                        ) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                minlength="3"
                maxlength="50"
                autocomplete="off"
                required
                value="<?= h($_POST['username'] ?? '') ?>"
            >

            <label for="password">Temporary password</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="10"
                autocomplete="new-password"
                required
            >

            <button type="submit">Create Student Account</button>
        </form>
    <?php endif; ?>
</body>
</html>

<?php
$conn->close();
?>
