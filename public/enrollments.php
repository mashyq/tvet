<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin', 'registrar']);

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM enrollments WHERE id = ?');
    $stmt->execute([$id]);
    flash('ok', 'Enrollment deleted.');
    header('Location: enrollments.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $sessionYear = sanitize($_POST['session_year'] ?? '');
    $semester = sanitize($_POST['semester'] ?? 'First');

    if ($studentId > 0 && $courseId > 0 && $sessionYear !== '') {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE enrollments SET student_id = ?, course_id = ?, session_year = ?, semester = ? WHERE id = ?');
            $stmt->execute([$studentId, $courseId, $sessionYear, $semester, $id]);
            flash('ok', 'Enrollment updated.');
        } else {
            $stmt = $pdo->prepare('INSERT IGNORE INTO enrollments (student_id, course_id, session_year, semester) VALUES (?, ?, ?, ?)');
            $stmt->execute([$studentId, $courseId, $sessionYear, $semester]);
            flash('ok', 'Enrollment saved.');
        }
    }
    header('Location: enrollments.php');
    exit;
}

$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM enrollments WHERE id = ?');
    $stmt->execute([$editId]);
    $editRow = $stmt->fetch();
}

$students = $pdo->query('SELECT id, matric_no, full_name FROM students ORDER BY full_name')->fetchAll();
$courses = $pdo->query('SELECT id, code, title FROM courses ORDER BY code')->fetchAll();

$q = trim($_GET['q'] ?? '');
$sessionFilter = trim($_GET['session'] ?? '');
$sql = "SELECT e.id, s.matric_no, s.full_name, c.code, c.title, e.session_year, e.semester, e.student_id, e.course_id
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    JOIN courses c ON e.course_id = c.id
    WHERE 1=1";
$params = [];
if ($q !== '') {
    $sql .= ' AND (s.matric_no LIKE ? OR s.full_name LIKE ? OR c.code LIKE ? OR c.title LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($sessionFilter !== '') {
    $sql .= ' AND e.session_year = ?';
    $params[] = $sessionFilter;
}
$sql .= ' ORDER BY e.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<?php if ($msg = flash('ok')): ?><div class="alert alert-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<form method="get" class="panel">
    <h3 style="margin-top:0;">Search/Filter Enrollments</h3>
    <div class="filter-grid">
        <div><label>Keyword</label><input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Student or course"></div>
        <div><label>Session</label><input name="session" value="<?= htmlspecialchars($sessionFilter) ?>" placeholder="2025/2026"></div>
        <div><button class="btn btn-soft" type="submit">Filter</button></div>
        <div><a class="btn btn-soft" href="enrollments.php">Reset</a></div>
    </div>
</form>

<form method="post" class="panel">
    <h3 style="margin-top:0;"><?= $editRow ? 'Edit Enrollment' : 'Course Enrollment' ?></h3>
    <input type="hidden" name="id" value="<?= (int) ($editRow['id'] ?? 0) ?>">
    <div class="form-grid">
        <div><label>Student</label><select name="student_id" required><?php foreach ($students as $s): ?><option value="<?= $s['id'] ?>" <?= (int)($editRow['student_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['matric_no'] . ' - ' . $s['full_name']) ?></option><?php endforeach; ?></select></div>
        <div><label>Course</label><select name="course_id" required><?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>" <?= (int)($editRow['course_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['code'] . ' - ' . $c['title']) ?></option><?php endforeach; ?></select></div>
        <div><label>Session Year</label><input name="session_year" value="<?= htmlspecialchars($editRow['session_year'] ?? '') ?>" placeholder="2025/2026" required></div>
        <div><label>Semester</label><select name="semester"><option <?= ($editRow['semester'] ?? '') === 'First' ? 'selected' : '' ?>>First</option><option <?= ($editRow['semester'] ?? '') === 'Second' ? 'selected' : '' ?>>Second</option></select></div>
    </div>
    <div style="margin-top:12px;" class="table-actions">
        <button class="btn btn-primary" type="submit"><?= $editRow ? 'Update' : 'Save Enrollment' ?></button>
        <?php if ($editRow): ?><a class="btn btn-soft" href="enrollments.php">Cancel Edit</a><?php endif; ?>
    </div>
</form>

<div class="card table-wrap">
    <table>
        <thead><tr><th>Matric</th><th>Student</th><th>Course</th><th>Session</th><th>Semester</th><th>Action</th></tr></thead>
        <tbody><?php foreach ($enrollments as $e): ?><tr><td><?= htmlspecialchars($e['matric_no']) ?></td><td><?= htmlspecialchars($e['full_name']) ?></td><td><?= htmlspecialchars($e['code'] . ' - ' . $e['title']) ?></td><td><?= htmlspecialchars($e['session_year']) ?></td><td><?= htmlspecialchars($e['semester']) ?></td><td class="table-actions"><a class="btn btn-sm btn-soft" href="enrollments.php?edit=<?= $e['id'] ?>">Edit</a><a class="btn btn-sm btn-danger" onclick="return confirm('Delete this enrollment?')" href="enrollments.php?delete=<?= $e['id'] ?>">Delete</a></td></tr><?php endforeach; ?></tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
