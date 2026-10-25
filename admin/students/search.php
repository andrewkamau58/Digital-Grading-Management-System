
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
    "SELECT admission_no, first_name, last_name, email, course
     FROM students
     WHERE admission_no LIKE ?
        OR first_name LIKE ?
        OR last_name LIKE ?
        OR email LIKE ?
        OR course LIKE ?
     ORDER BY last_name, first_name
     LIMIT 200"
);

$stmt->bind_param(
    "sssss", $like, $like, $like, $like, $like
);
$stmt->execute();
$rows = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Search Students</title>
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
<h2>Search Students</h2>
<p><a href="index.php">Student Management</a> |
<a href="../dashboard.php">Admin Dashboard</a></p>

<form method="get">
<label for="q">Admission number, name, email or course</label><br>
<input id="q" name="q" value="<?= h($q) ?>" maxlength="100">
<button type="submit">Search</button>
</form>

<table>
<thead>
<tr>
<th>Admission No.</th><th>Student</th><th>Email</th><th>Course</th>
</tr>
</thead>
<tbody>
<?php while ($r = $rows->fetch_assoc()): ?>
<tr>
<td><?= h($r['admission_no']) ?></td>
<td><?= h($r['first_name'].' '.$r['last_name']) ?></td>
<td><?= h($r['email']) ?></td>
<td><?= h($r['course']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>
