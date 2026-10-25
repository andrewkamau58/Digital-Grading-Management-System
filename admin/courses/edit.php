
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

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: index.php');
    exit;
}

$stmt = $conn->prepare(
    "SELECT course_code, course_name, lecturer_id
     FROM courses WHERE course_id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($course_code, $course_name, $lecturer_id);

if (!$stmt->fetch()) {
    $stmt->close();
    http_response_code(404);
    exit('Course not found.');
}
$stmt->close();

$lecturer_id = $lecturer_id === null ? '0' : (string)$lecturer_id;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request. Refresh the page and try again.';
    } else {
        $course_code = trim($_POST['course_code'] ?? '');
        $course_name = trim($_POST['course_name'] ?? '');
        $lecturer_id = trim($_POST['lecturer_id'] ?? '0');

        if ($course_code === '' || $course_name === '') {
            $error = 'Course code and course name are required.';
        } elseif (strlen($course_code) > 50 ||
                  strlen($course_name) > 150) {
            $error = 'Course code or course name is too long.';
        } elseif (!ctype_digit($lecturer_id)) {
            $error = 'Please select a valid lecturer.';
        } else {
            $lecturerId = (int)$lecturer_id;

            if ($lecturerId > 0) {
                $check = $conn->prepare(
                    "SELECT lecturer_id FROM lecturers WHERE lecturer_id = ?"
                );
                $check->bind_param('i', $lecturerId);
                $check->execute();
                $check->store_result();

                $validLecturer = $check->num_rows === 1;
                $check->close();

                if (!$validLecturer) {
                    $error = 'The selected lecturer does not exist.';
                }
            }

            if ($error === '') {
                $stmt = $conn->prepare(
                    "UPDATE courses
                     SET course_code = ?, course_name = ?,
                         lecturer_id = NULLIF(?, 0)
                     WHERE course_id = ?"
                );

                $stmt->bind_param(
                    'ssii',
                    $course_code,
                    $course_name,
                    $lecturerId,
                    $id
                );

                if ($stmt->execute()) {
                    $stmt->close();
                    header('Location: index.php');
                    exit;
                }

                $error = ($stmt->errno === 1062)
                    ? 'That course code is already in use.'
                    : 'Unable to update course. Please try again.';

                error_log('Course update failed: ' . $stmt->error);
                $stmt->close();
            }
        }
    }
}

$lecturers = $conn->query(
    "SELECT lecturer_id, staff_no, first_name, last_name
     FROM lecturers ORDER BY first_name, last_name"
);

if (!$lecturers) {
    error_log('Lecturer dropdown query failed: ' . $conn->error);
    exit('Unable to load lecturers. Please try again.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 30px; }
        .container { max-width: 600px; margin: auto; background: white; padding: 25px; border-radius: 8px; }
        label { display: block; margin-top: 15px; }
        input, select { width: 100%; padding: 10px; box-sizing: border-box; margin-top: 5px; }
        button, .btn { display: inline-block; margin-top: 20px; padding: 10px 15px; background: #1769aa; color: white; border: 0; text-decoration: none; border-radius: 4px; cursor: pointer; }
        .error { color: #b71c1c; }
    </style>
</head>
<body>
<div class="container">
    <h1>Edit Course</h1>

    <?php if ($error !== ''): ?>
        <p class="error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token"
               value="<?= e($_SESSION['csrf_token']) ?>">

        <label>Course Code *</label>
        <input type="text" name="course_code" maxlength="50"
               required value="<?= e($course_code) ?>">

        <label>Course Name *</label>
        <input type="text" name="course_name" maxlength="150"
               required value="<?= e($course_name) ?>">

        <label>Assign Lecturer</label>
        <select name="lecturer_id">
            <option value="0"
                <?= $lecturer_id === '0' ? 'selected' : '' ?>>
                -- Not assigned --
            </option>

            <?php while ($lecturer = $lecturers->fetch_assoc()): ?>
                <option
                    value="<?= (int)$lecturer['lecturer_id'] ?>"
                    <?= $lecturer_id === (string)$lecturer['lecturer_id']
                        ? 'selected' : '' ?>>
                    <?= e($lecturer['staff_no']) ?> -
                    <?= e($lecturer['first_name']) ?>
                    <?= e($lecturer['last_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Update Course</button>
        <a class="btn" href="index.php">Cancel</a>
    </form>
</div>
</body>
</html>
