
<?php
require_once __DIR__ . '/../includes/auth_check.php';
requireRole('student');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/style.css">
    <title>Student Dashboard</title>
</head>
<body>
    <h1>Student Dashboard</h1>

    <p>
        Welcome,
        <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>
    </p>

    <p><a href="results.php">My Academic Results</a></p>
    <p><a href="#">My Profile</a></p>
    <p><a href="results.php">View My Results</a></p>
    <p><a href="print_results.php">Print My Results</a></p>
    <p><a href="../account/change_password.php">Change Password</a></p>

    <p><a href="../auth/logout.php">Log Out</a></p>
</body>
</html>
