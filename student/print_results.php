
<?php
require_once __DIR__ . '/../includes/auth_check.php';
requireRole('student');
require_once __DIR__ . '/../config/database.php';

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$userId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT student_id, admission_no, first_name, last_name
     FROM students WHERE user_id=? LIMIT 1"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    http_response_code(403);
    exit('Your account is not linked to a student record.');
}

$studentId = (int)$student['student_id'];

$stmt = $conn->prepare(
    "SELECT c.course_code,c.course_name,e.academic_year,e.semester,
            m.cat_marks,m.exam_marks,m.total_marks,m.grade
     FROM enrolments e
     JOIN courses c ON c.course_id=e.course_id
     LEFT JOIN marks m ON m.enrolment_id=e.enrolment_id
     WHERE e.student_id=?
     ORDER BY e.academic_year DESC,c.course_code"
);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$rows = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Results</title>
<style>
body{font-family:Arial,sans-serif;margin:28px;color:#111}
header{text-align:center;border-bottom:2px solid #222;padding-bottom:12px}
.details{margin:22px 0;line-height:1.8}
.wrap{overflow-x:auto}
table{border-collapse:collapse;width:100%}
th,td{border:1px solid #777;padding:9px;text-align:left}
th{background:#eee}
@media print{.no-print{display:none}body{margin:10mm}tr{break-inside:avoid}}
</style>
</head>
<body>
<div class="no-print">
<button onclick="window.print()">Print / Save as PDF</button>
<a href="results.php">Back to Results</a>
</div>

<header>
<h2>Kiambu Institute of Business</h2>
<h3>Student Academic Results</h3>
</header>

<div class="details">
<strong>Student:</strong>
<?= h($student['first_name'].' '.$student['last_name']) ?><br>
<strong>Admission Number:</strong> <?= h($student['admission_no']) ?><br>
<strong>Generated:</strong> <?= h(date('d M Y')) ?>
</div>

<div class="wrap">
<table>
<thead><tr>
<th>Code</th><th>Course</th><th>Year</th><th>Semester</th>
<th>CAT / 30</th><th>Exam / 70</th><th>Total / 100</th><th>Grade</th>
</tr></thead>
<tbody>
<?php if ($rows->num_rows === 0): ?>
<tr><td colspan="8">No course enrolments found.</td></tr>
<?php else: ?>
<?php while ($r = $rows->fetch_assoc()): ?>
<tr>
<td><?= h($r['course_code']) ?></td>
<td><?= h($r['course_name']) ?></td>
<td><?= h($r['academic_year']) ?></td>
<td><?= h($r['semester']) ?></td>
<td><?= $r['cat_marks'] === null ? 'Pending' : h($r['cat_marks']) ?></td>
<td><?= $r['exam_marks'] === null ? 'Pending' : h($r['exam_marks']) ?></td>
<td><?= $r['total_marks'] === null ? 'Pending' : h($r['total_marks']) ?></td>
<td><?= $r['grade'] === null ? 'Pending' : h($r['grade']) ?></td>
</tr>
<?php endwhile; ?>
<?php endif; ?>
</tbody>
</table>
</div>
<p><small>System-generated results for personal reference. This is not an officially certified transcript.</small></p>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>
