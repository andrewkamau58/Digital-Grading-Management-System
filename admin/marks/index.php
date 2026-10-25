
<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole('admin');
require_once __DIR__ . '/../../config/database.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$sql = "SELECT e.enrolment_id, s.admission_no,
               s.first_name, s.last_name,
               c.course_code, c.course_name,
               e.academic_year, e.semester,
               m.cat_marks, m.exam_marks, m.total_marks, m.grade
        FROM enrolments e
        JOIN students s ON e.student_id = s.student_id
        JOIN courses c ON e.course_id = c.course_id
        LEFT JOIN marks m ON m.enrolment_id = e.enrolment_id
        ORDER BY s.last_name, s.first_name, c.course_code";

$result = $conn->query($sql);

if (!$result) {
    error_log('Marks listing failed: ' . $conn->error);
    exit('Unable to load marks.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Marks and Grades</title>
<style>
body{font-family:Arial;background:#f4f6f9;margin:30px}
.container{max-width:1300px;margin:auto;background:white;padding:25px;border-radius:8px}
table{width:100%;border-collapse:collapse;margin-top:20px}
th,td{border:1px solid #ddd;padding:10px;text-align:left}
th{background:#e9eef5}
.btn{display:inline-block;padding:8px 12px;background:#1769aa;color:white;text-decoration:none;border-radius:4px}
</style>
</head>
<body>
<div class="container">
<h1>Marks Entry and Grading</h1>

<p>
<a class="btn" href="../dashboard.php">Admin Dashboard</a>
<a class="btn" href="../enrolments/index.php">Manage Enrolments</a>
</p>

<table>
<thead>
<tr>
<th>Admission No.</th>
<th>Student</th>
<th>Course</th>
<th>Academic Year</th>
<th>Semester</th>
<th>CAT / 30</th>
<th>Exam / 70</th>
<th>Total / 100</th>
<th>Grade</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php if ($result->num_rows > 0): ?>
<?php while ($row = $result->fetch_assoc()): ?>
<tr>
<td><?= e($row['admission_no']) ?></td>
<td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td>
<td><?= e($row['course_code'] . ' - ' . $row['course_name']) ?></td>
<td><?= e($row['academic_year']) ?></td>
<td><?= e($row['semester']) ?></td>
<td><?= $row['cat_marks'] === null ? '—' : e($row['cat_marks']) ?></td>
<td><?= $row['exam_marks'] === null ? '—' : e($row['exam_marks']) ?></td>
<td><?= $row['total_marks'] === null ? '—' : e($row['total_marks']) ?></td>
<td><?= $row['grade'] === null ? 'Not graded' : e($row['grade']) ?></td>
<td>
<a class="btn" href="edit.php?id=<?= (int)$row['enrolment_id'] ?>">
<?= $row['cat_marks'] === null ? 'Enter Marks' : 'Edit Marks' ?>
</a>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="10">No enrolments found. Enrol students in courses first.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</body>
</html>
