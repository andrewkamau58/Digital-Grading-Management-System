
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

$sql = "SELECT lecturer_id, staff_no, first_name, last_name,
               email, phone
        FROM lecturers
        ORDER BY lecturer_id DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Unable to load lecturers. Please check the database.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lecturers</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background: #f4f6f9;
        }
        .container {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th { background: #e9eef5; }
        .btn {
            display: inline-block;
            padding: 8px 12px;
            background: #1769aa;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete { background: #c62828; }
        .actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .actions form { margin: 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>Lecturer Management</h1>

    <p>
        <a class="btn" href="create.php">+ Register Lecturer</a>
        <a class="btn" href="../dashboard.php">Back to Dashboard</a>
    </p>

    <table>
        <thead>
        <tr>
            <th>Staff Number</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($lecturer = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= e($lecturer['staff_no']) ?></td>
                    <td>
                        <?= e($lecturer['first_name']) ?>
                        <?= e($lecturer['last_name']) ?>
                    </td>
                    <td><?= e($lecturer['email']) ?></td>
                    <td><?= e($lecturer['phone']) ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn"
                               href="edit.php?id=<?= (int)$lecturer['lecturer_id'] ?>">
                                Edit
                            </a>

                            <form action="delete.php" method="POST"
                                  onsubmit="return confirm('Delete this lecturer?');">
                                <input type="hidden" name="csrf_token"
                                       value="<?= e($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="lecturer_id"
                                       value="<?= (int)$lecturer['lecturer_id'] ?>">
                                <button class="btn delete" type="submit">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">No lecturers registered yet.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
