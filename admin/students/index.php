
<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole('admin');

require_once __DIR__ . '/../../config/database.php';

$result = $conn->query(
    "SELECT student_id, admission_no, first_name, last_name,
            email, phone, course
     FROM students
     ORDER BY student_id DESC"
);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Students</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; color: #1e293b; }
        .top { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        a, button { padding: 9px 12px; border-radius: 5px; text-decoration: none; }
        .add { background: #1d4ed8; color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 22px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; }
        th { background: #e2e8f0; }
        .edit { color: #1d4ed8; }
        .delete { color: #b91c1c; border: 0; background: transparent; cursor: pointer; }
        .actions { display: flex; gap: 6px; align-items: center; }
    </style>
</head>
<body>
    <div class="top">
        <h1>Student Management</h1>
        <a class="add" href="create.php">+ Add Student</a>
    </div>

    <p><a href="../dashboard.php">← Back to Dashboard</a></p>
    <p><a href="search.php">Search Students</a></p>

    <table>
        <thead>
            <tr>
                <th>Admission No.</th>
                <th>Student Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Course</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($student = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= e($student['admission_no']) ?></td>
                    <td><?= e($student['first_name'] . ' ' . $student['last_name']) ?></td>
                    <td><?= e($student['email']) ?></td>
                    <td><?= e($student['phone']) ?></td>
                    <td><?= e($student['course']) ?></td>
                    <td>
                        <div class="actions">
                            <a class="edit"
                               href="edit.php?id=<?= (int)$student['student_id'] ?>">
                               Edit
                            </a>

                            <form method="POST" action="delete.php"
                                  onsubmit="return confirm('Delete this student record?');">
                                <input type="hidden" name="student_id"
                                       value="<?= (int)$student['student_id'] ?>">
                                <input type="hidden" name="csrf_token"
                                       value="<?= e($_SESSION['csrf_token']) ?>">
                                <button class="delete" type="submit">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No students registered yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
