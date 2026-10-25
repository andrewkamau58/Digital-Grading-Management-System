
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

function calculateGrade(float $total): string {
    if ($total >= 70) return 'A';
    if ($total >= 60) return 'B';
    if ($total >= 50) return 'C';
    if ($total >= 40) return 'D';
    return 'E';
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: index.php');
    exit;
}

$stmt = $conn->prepare(
    "SELECT s.admission_no, s.first_name, s.last_name,
            c.course_code, c.course_name,
            e.academic_year, e.semester
     FROM enrolments e
     JOIN students s ON e.student_id = s.student_id
     JOIN courses c ON e.course_id = c.course_id
     WHERE e.enrolment_id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result(
    $admission_no, $first_name, $last_name,
    $course_code, $course_name, $academic_year, $semester
);

if (!$stmt->fetch()) {
    $stmt->close();
    http_response_code(404);
    exit('Enrolment not found. Enrol the student first.');
}
$stmt->close();

$cat_marks = '';
$exam_marks = '';
$error = '';

$stmt = $conn->prepare(
    "SELECT cat_marks, exam_marks
     FROM marks WHERE enrolment_id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($saved_cat, $saved_exam);

if ($stmt->fetch()) {
    $cat_marks = (string)$saved_cat;
    $exam_marks = (string)$saved_exam;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request. Refresh the page and try again.';
    } else {
        $catInput = trim($_POST['cat_marks'] ?? '');
        $examInput = trim($_POST['exam_marks'] ?? '');

        $cat_marks = $catInput;
        $exam_marks = $examInput;

        if ($catInput === '' || $examInput === '' ||
            !is_numeric($catInput) || !is_numeric($examInput)) {
            $error = 'Enter valid numeric marks for both components.';
        } else {
            $cat = (float)$catInput;
            $exam = (float)$examInput;

            if (!is_finite($cat) || !is_finite($exam) ||
                $cat < 0 || $cat > 30 || $exam < 0 || $exam > 70) {
                $error = 'CAT must be between 0 and 30; exam must be between 0 and 70.';
            } else {
                $total = round($cat + $exam, 2);
                $grade = calculateGrade($total);

                $stmt = $conn->prepare(
                    "INSERT INTO marks
                     (enrolment_id, cat_marks, exam_marks, total_marks, grade)
                     VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                         cat_marks = VALUES(cat_marks),
                         exam_marks = VALUES(exam_marks),
                         total_marks = VALUES(total_marks),
                         grade = VALUES(grade)"
                );

                $stmt->bind_param(
                    'iddds',
                    $id, $cat, $exam, $total, $grade
                );

                if ($stmt->execute()) {
                    $stmt->close();
                    header('Location: index.php');
                    exit;
                }

                error_log('Saving marks failed: ' . $stmt->error);
                $error = 'Unable to save marks. Please try again.';
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Enter Marks</title>
<style>
body{font-family:Arial;background:#f4f6f9;margin:30px}
.container{max-width:650px;margin:auto;background:white;padding:25px;border-radius:8px}
label{display:block;margin-top:15px}
input{width:100%;padding:10px;margin-top:5px;box-sizing:border-box}
button,.btn{display:inline-block;margin-top:20px;padding:10px 15px;background:#1769aa;color:white;border:0;border-radius:4px;text-decoration:none;cursor:pointer}
.error{color:#b71c1c}
.info{background:#eef4fa;padding:12px;border-radius:5px}
</style>
</head>
<body>
<div class="container">
<h1>Enter Student Marks</h1>

<div class="info">
<p><strong>Student:</strong> <?= e($admission_no . ' - ' . $first_name . ' ' . $last_name) ?></p>
<p><strong>Course:</strong> <?= e($course_code . ' - ' . $course_name) ?></p>
<p><strong>Academic period:</strong> <?= e($academic_year . ' / ' . $semester) ?></p>
</div>

<?php if ($error !== ''): ?>
<p class="error"><?= e($error) ?></p>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

<label>CAT Marks (out of 30) *</label>
<input type="number" name="cat_marks" min="0" max="30" step="0.01"
       required value="<?= e($cat_marks) ?>">

<label>Exam Marks (out of 70) *</label>
<input type="number" name="exam_marks" min="0" max="70" step="0.01"
       required value="<?= e($exam_marks) ?>">

<p>Total and grade are calculated automatically when you save.</p>

<button type="submit">Save Marks</button>
<a class="btn" href="index.php">Cancel</a>
</form>
</div>
</body>
</html>
