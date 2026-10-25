
<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole('admin');
require_once __DIR__ . '/../../config/database.php';

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

$stmt = $conn->prepare(
    "SELECT c.course_code, c.course_name,
            l.first_name, l.last_name, l.staff_no
     FROM courses c
     LEFT JOIN lecturers l ON l.lecturer_id = c.lecturer_id
     WHERE c.course_code LIKE ?
        OR c.course_name LIKE ?
        OR l.first_name LIKE ?
        OR l.last_name LIKE ?
     ORDER BY c.course_code
     LIMIT 200"
);

$stmt->bind_param("ssss", $like, $like, $like, $like);
$stmt->execute();
$rows = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Search Courses</title>
<style>
body{font-family:Arial,sans-serif;margin:28px}
input{padding:10px;width:min(450px,70%)}
button{padding:10px}
table{border-collapse:collapse;width:100%;margin-top:20px}
th,td{border:1px solid #ddd;padding:10px;text-align:left}
th{background:#eee}
</style>
</head>
<body>
<h2>Search Courses</h2>
<p><a href="index.php">Course Management</a> |
<a href="../dashboard.php">Admin Dashboard</a></p>

<form method="get">
<label for="q">Course code, course name or lecturer</label><br>
<input id="q" name="q" value="<?= h($q) ?>" maxlength="100">
<button type="submit">Search</button>
</form>

<table>
<thead>
<tr><th>Code</th><th>Course Name</th><th>Lecturer</th></tr>
</thead>
<tbody>
<?php while ($r = $rows->fetch_assoc()): ?>
<tr>
<td><?= h($r['course_code']) ?></td>
<td><?= h($r['course_name']) ?></td>
<td>
<?= $r['staff_no'] === null
    ? 'Not assigned'
    : h($r['first_name'].' '.$r['last_name'].' ('.$r['staff_no'].')') ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>
