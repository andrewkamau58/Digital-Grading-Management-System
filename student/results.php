
<?php
require_once __DIR__ . '/../includes/auth_check.php';
requireRole('student');

require_once __DIR__ . '/../config/database.php';

$userId = (int) $_SESSION['user_id'];

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Find the student record belonging to the logged-in account.
$sql = "
    SELECT student_id, admission_no, first_name, last_name
    FROM students
    WHERE user_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    http_response_code(403);
    exit(
        'Your login account is not linked to a student record. ' .
        'Please contact the administrator.'
    );
}

$studentId = (int) $student['student_id'];

// Retrieve only this student's enrolled courses and marks.
$sql = "
    SELECT
        c.course_code,
        c.course_name,
        e.academic_year,
        e.semester,
        m.cat_marks,
        m.exam_marks,
        m.total_marks,
        m.grade
    FROM enrolments e
    INNER JOIN courses c
        ON c.course_id = e.course_id
    LEFT JOIN marks m
        ON m.enrolment_id = e.enrolment_id
    WHERE e.student_id = ?
    ORDER BY e.academic_year DESC, c.course_code ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$results = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Academic Results</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            color: #222;
        }

        .profile {
            background: #f3f5f7;
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 11px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
        }

        tr:nth-child(even) {
            background: #fafafa;
        }

        .notice {
            padding: 14px;
            background: #fff5d6;
            border-radius: 6px;
        }

        .actions {
            margin: 18px 0;
        }

        @media (max-width: 800px) {
            .table-wrapper {
                overflow-x: auto;
            }

            table {
                min-width: 750px;
            }
        }
    </style>
</head>

<body>
    <h2>My Academic Results</h2>

    <div class="actions">
        <a href="dashboard.php">Back to Dashboard</a> |
        <a href="../auth/logout.php">Log out</a>
    </div>

    <div class="profile">
        <strong>Student:</strong>
        <?= h($student['first_name'] . ' ' . $student['last_name']) ?>
        <br>

        <strong>Admission Number:</strong>
        <?= h($student['admission_no']) ?>
    </div>

    <h3>Course Results</h3>

    <?php if ($results->num_rows === 0): ?>
        <p class="notice">
            You are not currently enrolled in any courses.
            Please contact the administrator if you believe this is incorrect.
        </p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Academic Year</th>
                        <th>Semester</th>
                        <th>CAT / 30</th>
                        <th>Exam / 70</th>
                        <th>Total / 100</th>
                        <th>Grade</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($row = $results->fetch_assoc()): ?>
                        <tr>
                            <td><?= h($row['course_code']) ?></td>
                            <td><?= h($row['course_name']) ?></td>
                            <td><?= h($row['academic_year']) ?></td>
                            <td><?= h($row['semester']) ?></td>

                            <td>
                                <?= $row['cat_marks'] === null
                                    ? 'Pending'
                                    : h($row['cat_marks']) ?>
                            </td>

                            <td>
                                <?= $row['exam_marks'] === null
                                    ? 'Pending'
                                    : h($row['exam_marks']) ?>
                            </td>

                            <td>
                                <?= $row['total_marks'] === null
                                    ? 'Pending'
                                    : h($row['total_marks']) ?>
                            </td>

                            <td>
                                <?= $row['grade'] === null
                                    ? 'Pending'
                                    : h($row['grade']) ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <p>
        <em>
            Results displayed here are retrieved from the grading system.
            Contact the administration if you notice an error.
        </em>
    </p>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
