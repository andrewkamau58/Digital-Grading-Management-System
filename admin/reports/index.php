
<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole('admin');
require_once __DIR__ . '/../../config/database.php';

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$summary = $conn->query(
    "SELECT
      (SELECT COUNT(*) FROM students) AS students_count,
      (SELECT COUNT(*) FROM lecturers) AS lecturers_count,
      (SELECT COUNT(*) FROM courses) AS courses_count,
      (SELECT COUNT(*) FROM enrolments) AS enrolments_count,
      (SELECT COUNT(*) FROM marks) AS marks_count,
      (SELECT ROUND(AVG(total_marks),2)
       FROM marks WHERE total_marks IS NOT NULL) AS overall_average"
)->fetch_assoc();

$courses = $conn->query(
    "SELECT c.course_code, c.course_name,
       COUNT(DISTINCT e.enrolment_id) AS enrolled,
       COUNT(DISTINCT m.mark_id) AS marks_recorded,
       ROUND(AVG(m.total_marks),2) AS course_average,
       SUM(CASE WHEN m.grade='A' THEN 1 ELSE 0 END) AS grade_a,
       SUM(CASE WHEN m.grade='B' THEN 1 ELSE 0 END) AS grade_b,
       SUM(CASE WHEN m.grade='C' THEN 1 ELSE 0 END) AS grade_c,
       SUM(CASE WHEN m.grade='D' THEN 1 ELSE 0 END) AS grade_d,
       SUM(CASE WHEN m.grade='E' THEN 1 ELSE 0 END) AS grade_e
     FROM courses c
     LEFT JOIN enrolments e ON e.course_id=c.course_id
     LEFT JOIN marks m ON m.enrolment_id=e.enrolment_id
     GROUP BY c.course_id,c.course_code,c.course_name
     ORDER BY c.course_code"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Grading Reports</title>
<style>
body{font-family:Arial,sans-serif;margin:28px;color:#222}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:12px;margin:20px 0}
.card{border:1px solid #ddd;border-radius:8px;padding:15px;background:#fafafa}
.value{font-size:1.6rem;font-weight:bold;margin-top:8px}
.wrap{overflow-x:auto}
table{border-collapse:collapse;width:100%;margin-top:18px}
th,td{border:1px solid #ddd;padding:9px;text-align:left}
th{background:#eee}
@media print{.no-print{display:none}body{margin:8mm}}
</style>
</head>
<body>
<div class="no-print">
<h2>System Reports and Analytics</h2>
<p><a href="../dashboard.php">Admin Dashboard</a></p>
<button onclick="window.print()">Print Report</button>
</div>

<div class="cards">
<?php
$labels = [
    'Students'=>'students_count',
    'Lecturers'=>'lecturers_count',
    'Courses'=>'courses_count',
    'Enrolments'=>'enrolments_count',
    'Marks Recorded'=>'marks_count'
];
foreach ($labels as $label=>$key):
?>
<div class="card"><?= h($label) ?>
<div class="value"><?= (int)$summary[$key] ?></div></div>
<?php endforeach; ?>

<div class="card">Overall Average
<div class="value">
<?= $summary['overall_average'] === null
    ? '—' : h($summary['overall_average']) ?>
</div></div>
</div>

<h3>Course Performance</h3>
<div class="wrap">
<table>
<thead><tr>
<th>Code</th><th>Course</th><th>Enrolled</th><th>Marks Recorded</th>
<th>Average</th><th>A</th><th>B</th><th>C</th><th>D</th><th>E</th>
</tr></thead>
<tbody>
<?php while ($r = $courses->fetch_assoc()): ?>
<tr>
<td><?= h($r['course_code']) ?></td>
<td><?= h($r['course_name']) ?></td>
<td><?= (int)$r['enrolled'] ?></td>
<td><?= (int)$r['marks_recorded'] ?></td>
<td><?= $r['course_average'] === null ? '—' : h($r['course_average']) ?></td>
<td><?= (int)$r['grade_a'] ?></td>
<td><?= (int)$r['grade_b'] ?></td>
<td><?= (int)$r['grade_c'] ?></td>
<td><?= (int)$r['grade_d'] ?></td>
<td><?= (int)$r['grade_e'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<p><small>Confirm the official grading scale before using these statistics for formal decisions.</small></p>
</body>
</html>
<?php $conn->close(); ?>
