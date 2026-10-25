
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function requireRole(string $requiredRole): void
{
    if (
        !isset($_SESSION['user_id'], $_SESSION['role']) ||
        $_SESSION['role'] !== $requiredRole
    ) {
        header('Location: ../auth/login.php');
        exit;
    }
}
