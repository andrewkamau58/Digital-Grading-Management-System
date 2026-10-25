
<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole('lecturer');

require_once __DIR__ . '/../../config/database.php';

$userId = (int) $_SESSION['user_id'];

$sql = "
    SELECT
        e.enrolment_id,
        s.admission_no,
        s.first_name,
        s.last_name,
        c.course_code,
        c.course_name,
        e.academic_year,
        e.semester,
        m.cat_marks,
        m.exam_marks,
        m.total_marks,
        m.grade
    FROM lecturers l
    INNER JOIN courses c
        ON c.lecturer_id = l.lecturer_id
    INNER JOIN enrolments e
        ON e.course_id = c.course_id
    INNER JOIN students s
        ON s.student_id = e.student_id
    LEFT JOIN marks m
        ON m.enrolment_id = e.enrolment_id
    WHERE l.user_id = ?
    ORDER BY c.course_code, s.last_name, s.first_name
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Students' Marks</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f2f2f2; }
        a { color: #0759a5; }
        .notice { padding: 12px; background: #f4f4f4; }
    </style>
</head>
<body>
    <h2>My Students' Marks</h2>
    <p><a href="../dashboard.php">Back to Dashboard</a> |
       <a href="../../auth/logout.php">Log out</a></p>

    <?php if ($result->num_rows === 0): ?>
        <p class="notice">
            No students found for your assigned courses.
            Confirm that your lecturer account is linked and your courses
            have been assigned to you.
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Admission No.</th>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Year / Semester</th>
                    <th>CAT</th>
                    <th>Exam</th>
                    <th>Total</th>
                    <th>Grade</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= h($row['admission_no']) ?></td>
                    <td><?= h($row['first_name'] . ' ' . $row['last_name']) ?></td>
                    <td><?= h($row['course_code'] . ' - ' . $row['course_name']) ?></td>
                    <td><?= h($row['academic_year'] . ' / ' . $row['semester']) ?></td>
                    <td><?= h($row['cat_marks'] ?? '—') ?></td>
                    <td><?= h($row['exam_marks'] ?? '—') ?></td>
                    <td><?= h($row['total_marks'] ?? '—') ?></td>
                    <td><?= h($row['grade'] ?? '—') ?></td>
                    <td>
                        <a href="edit.php?enrolment_id=<?= (int)$row['enrolment_id'] ?>">
                            Enter / Edit
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
