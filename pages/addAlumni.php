<?php
// File: pages/addAlumni.php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/auth.php';

Auth::restrict('alumni.manage');
if (!Auth::isAdmin()) { header("Location: ../index.php"); exit; }

$pdo = Database::getPDO();
$msg = ""; $err = "";

// FETCH DROPDOWN DATA
try {
    $colleges = $pdo->query("SELECT * FROM colleges ORDER BY name ASC")->fetchAll();
    $courses = $pdo->query("SELECT * FROM courses ORDER BY name ASC")->fetchAll();
} catch (Exception $e) { $colleges = []; $courses = []; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id']);
    $surname = trim($_POST['surname']);
    $firstname = trim($_POST['firstname']);
    $college_id = $_POST['college_id'];
    $course_id = $_POST['course_id'];
    $year = $_POST['year_graduated'];

    try {
        // Check Duplicate
        $stmt = $pdo->prepare("SELECT id FROM alumni WHERE student_id = ?");
        $stmt->execute([$student_id]);
        if ($stmt->fetch()) {
            $err = "Student ID $student_id already exists.";
        } else {
            $pdo->beginTransaction();

            // 1. Insert into ALUMNI (No user_id yet)
            $stmt = $pdo->prepare("INSERT INTO alumni (student_id, surname, given_name, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$student_id, $surname, $firstname]);
            $alumni_id = $pdo->lastInsertId();

            // 2. Insert into ACADEMIC RECORDS
            $stmt = $pdo->prepare("INSERT INTO academic_records (alumni_id, college_id, course_id, student_number, year_graduated) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$alumni_id, $college_id, $course_id, $student_id, $year]);

            $pdo->commit();
            $msg = "Alumni <strong>$firstname $surname</strong> added successfully!";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $err = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Alumni</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
        body { background:#f4f6f9; }
        .form-box { max-width:600px; margin:40px auto; background:white; padding:30px; border-radius:8px; border-top:5px solid #007bff; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
        h1 { margin-top:0; color:#333; }
        .alert { padding:10px; border-radius:5px; margin-bottom:15px; }
        .success { background:#d4edda; color:#155724; }
        .error { background:#f8d7da; color:#721c24; }
        label { font-weight:bold; display:block; margin-bottom:5px; }
        input, select { width:100%; padding:10px; margin-bottom:15px; border:1px solid #ddd; border-radius:4px; }
        button { width:100%; padding:12px; background:#007bff; color:white; border:none; border-radius:5px; font-weight:bold; cursor:pointer; }
        button:hover { background:#0056b3; }
    </style>
</head>
<body>
    <div class="form-box">
        <a href="adminDashboard.php" style="text-decoration:none; color:#666; font-size:0.9em;">&larr; Back</a>
        <h1>Add New Alumni</h1>
        <p style="color:#666; font-size:0.9em; margin-bottom:20px;">Add a record to the master list so they can register.</p>

        <?php if($msg): ?><div class="alert success"><?= $msg ?></div><?php endif; ?>
        <?php if($err): ?><div class="alert error"><?= $err ?></div><?php endif; ?>

        <form method="POST">
            <label>Student ID</label>
            <input type="text" name="student_id" required placeholder="e.g. 2024-001">

            <label>First Name</label>
            <input type="text" name="firstname" required>

            <label>Last Name</label>
            <input type="text" name="surname" required>

            <label>College</label>
            <select name="college_id" id="college" required onchange="filterCourses()">
                <option value="">-- Select --</option>
                <?php foreach($colleges as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Course</label>
            <select name="course_id" id="course" required>
                <option value="">-- Select College First --</option>
                <?php foreach($courses as $co): ?>
                    <option value="<?= $co['id'] ?>" data-col="<?= $co['college_id'] ?>" style="display:none;">
                        <?= htmlspecialchars($co['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Year Graduated</label>
            <input type="number" name="year_graduated" value="<?= date('Y') ?>">

            <button type="submit">Add Record</button>
        </form>
    </div>

<script>
function filterCourses() {
    const colId = document.getElementById('college').value;
    const courseSelect = document.getElementById('course');
    const options = courseSelect.querySelectorAll('option[data-col]');
    
    courseSelect.value = "";
    options.forEach(opt => {
        if (opt.getAttribute('data-col') == colId) opt.style.display = 'block';
        else opt.style.display = 'none';
    });
}
</script>
</body>
</html>