
<?php
require_once __DIR__ . '/../includes/auth_check.php';
requireRole('lecturer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/style.css">
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
    <title>Lecturer Dashboard</title>
</head>
<body>
    <h1 class="dashboard-title">Lecturer Dashboard</h1>

    <p class="welcome-message">
        Welcome,
        <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>
    </p>
<div class="dashboard-grid">
    <p class="dashboard-card"><a href="#">My Assigned Courses</a></p>
    <p class="dashboard-card"><a href="#">Enter Student Marks</a></p>
    <p class="dashboard-card"><a href="#">View Course Results</a></p>
    <p class="dashboard-card"><a href="marks/index.php">Manage My Students' Marks</a></p>
    <p class="dashboard-card"><a href="../account/change_password.php">Change Password</a></p>
    <p class="dashboard-card"><a href="../auth/logout.php">Log Out</a></p>
</div>
    
</body>
</html>
