
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

$sql = "SELECT e.enrolment_id, s.admission_no,
               s.first_name, s.last_name,
               c.course_code, c.course_name,
               e.academic_year, e.semester
        FROM enrolments e
        JOIN students s ON e.student_id = s.student_id
        JOIN courses c ON e.course_id = c.course_id
        ORDER BY e.enrolment_id DESC";

$result = $conn->query($sql);

if (!$result) {
    error_log('Enrolment listing failed: ' . $conn->error);
    exit('Unable to load enrolments.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Student Enrolments</title>
<style>
body{font-family:Arial;background:#f4f6f9;margin:30px}
.container{max-width:1200px;margin:auto;background:white;padding:25px;border-radius:8px}
table{width:100%;border-collapse:collapse;margin-top:20px}
th,td{border:1px solid #ddd;padding:10px;text-align:left}
th{background:#e9eef5}
.btn{display:inline-block;padding:8px 12px;background:#1769aa;color:white;text-decoration:none;border:0;border-radius:4px;cursor:pointer}
.delete{background:#c62828}
</style>
</head>
<body>
<div class="container">
<h1>Student Enrolments</h1>

<p>
<a class="btn" href="create.php">+ Enrol Student</a>
<a class="btn" href="../dashboard.php">Admin Dashboard</a>
<a class="btn" href="../marks/index.php">Manage Marks</a>
</p>

<table>
<thead>
<tr>
<th>Admission No.</th>
<th>Student</th>
<th>Course</th>
<th>Academic Year</th>
<th>Semester</th>
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
<td>
<form method="POST" action="delete.php"
      onsubmit="return confirm('Delete this enrolment? Any linked marks will also be deleted.');">
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
<input type="hidden" name="enrolment_id" value="<?= (int)$row['enrolment_id'] ?>">
<button class="btn delete" type="submit">Delete</button>
</form>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="6">No enrolments have been created.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</body>
</html>
