
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

$sql = "SELECT c.course_id, c.course_code, c.course_name,
               l.first_name, l.last_name
        FROM courses c
        LEFT JOIN lecturers l ON c.lecturer_id = l.lecturer_id
        ORDER BY c.course_id DESC";

$result = $conn->query($sql);

if (!$result) {
    error_log('Course listing failed: ' . $conn->error);
    exit('Unable to load courses. Please try again.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses</title>

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
            padding: 12px;
            text-align: left;
        }

        th {
            background: #e9eef5;
        }

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

        .delete {
            background: #c62828;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .actions form {
            margin: 0;
        }
    </style>
</head>

<body>
<div class="container">

    <h1>Course Management</h1>

    <p>
        <a class="btn" href="create.php">+ Add Course</a>
        <a class="btn" href="../dashboard.php">Back to Dashboard</a>
        <a class="btn" href="search.php">Search Courses</a>
    </p>

    <table>
        <thead>
        <tr>
            <th>Course Code</th>
            <th>Course Name</th>
            <th>Assigned Lecturer</th>
            <th>Actions</th>
        </tr>
        </thead>

        <tbody>
        <?php if ($result->num_rows > 0): ?>

            <?php while ($course = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= e($course['course_code']) ?></td>

                    <td><?= e($course['course_name']) ?></td>

                    <td>
                        <?php if ($course['first_name'] !== null): ?>
                            <?= e($course['first_name']) ?>
                            <?= e($course['last_name']) ?>
                        <?php else: ?>
                            Not assigned
                        <?php endif; ?>
                    </td>

                    <td>
                        <div class="actions">

                            <a class="btn"
                               href="edit.php?id=<?= (int)$course['course_id'] ?>">
                                Edit
                            </a>

                            <form action="delete.php" method="POST"
                                  onsubmit="return confirm('Delete this course? Enrolments and marks linked to it may also be deleted.');">

                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?= e($_SESSION['csrf_token']) ?>">

                                <input type="hidden"
                                       name="course_id"
                                       value="<?= (int)$course['course_id'] ?>">

                                <button type="submit" class="btn delete">
                                    Delete
                                </button>
                            </form>

                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="4">No courses have been registered yet.</td>
            </tr>

        <?php endif; ?>
        </tbody>
    </table>

</div>
</body>
</html>
