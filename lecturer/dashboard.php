
<?php
require_once __DIR__ . '/../includes/auth_check.php';
requireRole('lecturer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/style.css">
    <title>Lecturer Dashboard</title>
</head>
<body>
    <h1>Lecturer Dashboard</h1>

    <p>
        Welcome,
        <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>
    </p>

    <p><a href="#">My Assigned Courses</a></p>
    <p><a href="#">Enter Student Marks</a></p>
    <p><a href="#">View Course Results</a></p>
    <p><a href="marks/index.php">Manage My Students' Marks</a></p>
    <p><a href="../account/change_password.php">Change Password</a></p>
    <p><a href="../auth/logout.php">Log Out</a></p>
</body>
</html>
