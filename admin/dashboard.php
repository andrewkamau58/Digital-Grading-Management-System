
<?php
require_once __DIR__ . '/../includes/auth_check.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/style.css">
    <title>Administrator Dashboard</title>
    <style>
        .dashboard-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 22px;
  margin-top: 25px;
}
h1 {
    color: #172554;
    font-size: 32px;
    margin-bottom: 10px;
}

/* Welcome message */
h1 + p {
    color: #64748b;
    font-size: 16px;
}
.dashboard-card {
  margin: 0;
  background-color:rgba(168, 163, 163, 0.64);
  border-radius: 14px;
  padding: 28px 22px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.dashboard-card a {
  display: block;
    text-decoration: none;
    color: #1d4ed8;
    font-size: 17px;
    font-weight: 600;
}
.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.10);
    border-color: #93c5fd;
}

.dashboard-card a:hover {
    color: #1e40af;
}

.dashboard-card:focus-within {
    outline: 2px solid #2563eb;
    outline-offset: 3px;
}
    </style>
</head>
<body>
    <h1 class="dashboard-title">Administrator Dashboard</h1>

    <p class="welcome-message">
        Welcome,
        <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>
    </p>

    <h3>Digital Grading Management System</h3>

    <div class="dashboard-grid">
        <p class="dashboard-card"><a href="students/index.php">Manage Students</a></p>
        <p class="dashboard-card"><a href="lecturers/index.php">Manage Lecturers</a></p>
        <p class="dashboard-card"><a href="courses/index.php">Manage Courses</a></p>
        <p class="dashboard-card"><a href="users/index.php">Manage Users</a></p>
        <p class="dashboard-card"><a href="reports/index.php">View Reports</a></p>
        <p class="dashboard-card"><a href="enrolments/index.php">Manage Enrolments</a></p>
        <p class="dashboard-card"><a href="marks/index.php">Manage Marks and Grades</a></p>
        <p class="dashboard-card"><a href="lecturers/create_account.php">Create Lecturer Login Account</a></p>
        <p class="dashboard-card"><a href="students/create_account.php">Create Student Login Account</a></p>
        <p class="dashboard-card"><a href="../account/change_password.php">Change Password</a></p>
        <p class="dashboard-card"><a href="reports/index.php">Reports and Analytics</a></p>
    </div>
    <p class="dashboard-card"><a href="../auth/logout.php">Log Out</a></p>
</body>
</html>
