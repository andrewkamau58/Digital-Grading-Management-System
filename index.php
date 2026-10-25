
<?php
session_start();

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: auth/login.php');
    exit;
}

switch ($_SESSION['role']) {
    case 'admin':
        header('Location: admin/dashboard.php');
        break;

    case 'lecturer':
        header('Location: lecturer/dashboard.php');
        break;

    case 'student':
        header('Location: student/dashboard.php');
        break;

    default:
        session_unset();
        session_destroy();
        header('Location: auth/login.php');
        break;
}

exit;
