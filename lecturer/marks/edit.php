
<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole('lecturer');

require_once __DIR__ . '/../../config/database.php';

$userId = (int) $_SESSION['user_id'];
$enrolmentId = filter_input(INPUT_GET, 'enrolment_id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrolmentId = filter_input(
        INPUT_POST,
        'enrolment_id',
        FILTER_VALIDATE_INT
    );
}

if (!$enrolmentId || $enrolmentId < 1) {
    http_response_code(400);
    exit('Invalid enrolment ID.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

/*
 * Verify that this enrolment belongs to a course assigned
 * to the currently logged-in lecturer.
 */
function lecturerOwnsEnrolment($conn, $userId, $enrolmentId) {
    $sql = "
        SELECT e.enrolment_id
        FROM enrolments e
        INNER JOIN courses c ON c.course_id = e.course_id
        INNER JOIN lecturers l ON l.lecturer_id = c.lecturer_id
        WHERE e.enrolment_id = ?
          AND l.user_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $enrolmentId, $userId);
    $stmt->execute();
    $stmt->store_result();

    $allowed = $stmt->num_rows === 1;
    $stmt->close();

    return $allowed;
}

if (!lecturerOwnsEnrolment($conn, $userId, $enrolmentId)) {
    http_response_code(403);
    exit('Access denied. This course is not assigned to your account.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Invalid form token. Refresh the page and try again.');
    }

    $catRaw = $_POST['cat_marks'] ?? '';
    $examRaw = $_POST['exam_marks'] ?? '';

    if (!is_numeric($catRaw) || !is_numeric($examRaw)) {
        $error = 'Enter valid numeric marks for both assessments.';
    } else {
        $cat = (float)$catRaw;
        $exam = (float)$examRaw;

        if (!is_finite($cat) || !is_finite($exam) ||
            $cat < 0 || $cat > 30 ||
            $exam < 0 || $exam > 70) {
            $error = 'CAT must be 0–30 and exam must be 0–70.';
        } else {
            $total = round($cat + $exam, 2);

            if ($total >= 70) {
                $grade = 'A';
            } elseif ($total >= 60) {
                $grade = 'B';
            } elseif ($total >= 50) {
                $grade = 'C';
            } elseif ($total >= 40) {
                $grade = 'D';
            } else {
                $grade = 'E';
            }

            /*
             * Recheck ownership immediately before saving.
             */
            if (!lecturerOwnsEnrolment(
                $conn,
                $userId,
                $enrolmentId
            )) {
                http_response_code(403);
                exit('Access denied.');
            }

            $sql = "
                INSERT INTO marks
                    (enrolment_id, cat_marks, exam_marks,
                     total_marks, grade)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    cat_marks = VALUES(cat_marks),
                    exam_marks = VALUES(exam_marks),
                    total_marks = VALUES(total_marks),
                    grade = VALUES(grade)
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "iddds",
                $enrolmentId,
                $cat,
                $exam,
                $total,
                $grade
            );

            if ($stmt->execute()) {
                $stmt->close();
                header('Location: index.php?saved=1');
                exit;
            }

            $error = 'Could not save marks. Check the database configuration.';
            $stmt->close();
        }
    }
}

/* Load student details and existing marks. */
$sql = "
    SELECT
        s.admission_no,
        s.first_name,
        s.last_name,
        c.course_code,
        c.course_name,
        m.cat_marks,
        m.exam_marks
    FROM enrolments e
    INNER JOIN students s ON s.student_id = e.student_id
    INNER JOIN courses c ON c.course_id = e.course_id
    INNER JOIN lecturers l ON l.lecturer_id = c.lecturer_id
    LEFT JOIN marks m ON m.enrolment_id = e.enrolment_id
    WHERE e.enrolment_id = ?
      AND l.user_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $enrolmentId, $userId);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record) {
    http_response_code(404);
    exit('Enrolment not found.');
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enter Marks</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        label { display: block; margin-top: 15px; }
        input { padding: 9px; width: 100%; max-width: 350px; }
        button { margin-top: 20px; padding: 10px 18px; cursor: pointer; }
        .error { color: #b00020; }
    </style>
</head>
<body>
    <h2>Enter / Edit Marks</h2>
    <p>
        <a href="index.php">Back to My Students' Marks</a>
    </p>

    <p>
        <strong>Student:</strong>
        <?= h($record['first_name'] . ' ' . $record['last_name']) ?><br>
        <strong>Admission No.:</strong> <?= h($record['admission_no']) ?><br>
        <strong>Course:</strong>
        <?= h($record['course_code'] . ' - ' . $record['course_name']) ?>
    </p>

    <?php if ($error !== ''): ?>
        <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="enrolment_id"
               value="<?= (int)$enrolmentId ?>">
        <input type="hidden" name="csrf_token"
               value="<?= h($_SESSION['csrf_token']) ?>">

        <label for="cat_marks">CAT marks (0–30)</label>
        <input type="number" id="cat_marks" name="cat_marks"
               min="0" max="30" step="0.01" required
               value="<?= h($_POST['cat_marks'] ?? $record['cat_marks'] ?? '') ?>">

        <label for="exam_marks">Exam marks (0–70)</label>
        <input type="number" id="exam_marks" name="exam_marks"
               min="0" max="70" step="0.01" required
               value="<?= h($_POST['exam_marks'] ?? $record['exam_marks'] ?? '') ?>">

        <button type="submit">Save Marks</button>
    </form>
</body>
</html>
<?php $conn->close(); ?>
