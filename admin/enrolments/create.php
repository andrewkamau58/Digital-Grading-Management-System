
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

$student_id = '';
$course_id = '';
$academic_year = '';
$semester = '';
$error = '';

$students = $conn->query(
    "SELECT student_id, admission_no, first_name, last_name
     FROM students ORDER BY first_name, last_name"
);

$courses = $conn->query(
    "SELECT course_id, course_code, course_name
     FROM courses ORDER BY course_code"
);

if (!$students || !$courses) {
    error_log('Unable to load enrolment dropdowns: ' . $conn->error);
    exit('Unable to load students or courses.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request. Refresh the page and try again.';
    } else {
        $student_id = trim($_POST['student_id'] ?? '');
        $course_id = trim($_POST['course_id'] ?? '');
        $academic_year = trim($_POST['academic_year'] ?? '');
        $semester = trim($_POST['semester'] ?? '');

        if (!ctype_digit($student_id) || (int)$student_id < 1 ||
            !ctype_digit($course_id) || (int)$course_id < 1) {
            $error = 'Select a valid student and course.';
        } elseif ($academic_year === '' || strlen($academic_year) > 20) {
            $error = 'Enter a valid academic year.';
        } elseif (!in_array($semester, ['Semester 1', 'Semester 2', 'Semester 3'], true)) {
            $error = 'Select a valid semester.';
        } else {
            $sid = (int)$student_id;
            $cid = (int)$course_id;

            $stmt = $conn->prepare(
                "INSERT INTO enrolments
                 (student_id, course_id, academic_year, semester)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param('iiss', $sid, $cid, $academic_year, $semester);

            if ($stmt->execute()) {
                $stmt->close();
                header('Location: index.php');
                exit;
            }

            if ($stmt->errno === 1062) {
                $error = 'This student is already enrolled in that course for that year and semester.';
            } elseif ($stmt->errno === 1452) {
                $error = 'The selected student or course no longer exists.';
            } else {
                error_log('Enrolment creation failed: ' . $stmt->error);
                $error = 'Unable to create enrolment. Please try again.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Enrol Student</title>
<style>
body{font-family:Arial;background:#f4f6f9;margin:30px}
.container{max-width:650px;margin:auto;background:white;padding:25px;border-radius:8px}
label{display:block;margin-top:15px}
input,select{width:100%;padding:10px;margin-top:5px;box-sizing:border-box}
.btn,button{display:inline-block;margin-top:20px;padding:10px 15px;background:#1769aa;color:white;border:0;border-radius:4px;text-decoration:none;cursor:pointer}
.error{color:#b71c1c}
</style>
</head>
<body>
<div class="container">
<h1>Enrol Student</h1>

<?php if ($error !== ''): ?>
<p class="error"><?= e($error) ?></p>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

<label>Student *</label>
<select name="student_id" required>
<option value="">-- Select student --</option>
<?php while ($s = $students->fetch_assoc()): ?>
<option value="<?= (int)$s['student_id'] ?>"
<?= $student_id === (string)$s['student_id'] ? 'selected' : '' ?>>
<?= e($s['admission_no'] . ' - ' . $s['first_name'] . ' ' . $s['last_name']) ?>
</option>
<?php endwhile; ?>
</select>

<label>Course *</label>
<select name="course_id" required>
<option value="">-- Select course --</option>
<?php while ($c = $courses->fetch_assoc()): ?>
<option value="<?= (int)$c['course_id'] ?>"
<?= $course_id === (string)$c['course_id'] ? 'selected' : '' ?>>
<?= e($c['course_code'] . ' - ' . $c['course_name']) ?>
</option>
<?php endwhile; ?>
</select>

<label>Academic Year *</label>
<input type="text" name="academic_year" maxlength="20"
placeholder="e.g. 2026/2027" required value="<?= e($academic_year) ?>">

<label>Semester *</label>
<select name="semester" required>
<option value="">-- Select semester --</option>
<?php foreach (['Semester 1','Semester 2','Semester 3'] as $sem): ?>
<option value="<?= e($sem) ?>"
<?= $semester === $sem ? 'selected' : '' ?>>
<?= e($sem) ?>
</option>
<?php endforeach; ?>
</select>

<button type="submit">Save Enrolment</button>
<a class="btn" href="index.php">Cancel</a>
</form>
</div>
</body>
</html>
