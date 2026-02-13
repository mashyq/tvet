<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin', 'registrar', 'lecturer']);

$role = $_SESSION['user']['role'];
$userId = (int) $_SESSION['user']['id'];

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM grades WHERE id = ?');
    $stmt->execute([$id]);
    flash('ok', 'Grade deleted.');
    header('Location: grades.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollmentId = (int) ($_POST['enrollment_id'] ?? 0);
    $score = (float) ($_POST['score'] ?? -1);

    if ($enrollmentId > 0 && $score >= 0 && $score <= 100) {
        [$grade, $point, $remark] = score_to_grade($score);

        $existing = $pdo->prepare('SELECT id FROM grades WHERE enrollment_id = ?');
        $existing->execute([$enrollmentId]);

        if ($existing->fetch()) {
            $stmt = $pdo->prepare('UPDATE grades SET score = ?, grade = ?, grade_point = ?, remark = ?, updated_by = ? WHERE enrollment_id = ?');
            $stmt->execute([$score, $grade, $point, $remark, $userId, $enrollmentId]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO grades (enrollment_id, score, grade, grade_point, remark, updated_by) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$enrollmentId, $score, $grade, $point, $remark, $userId]);
        }
        flash('ok', 'Grade submitted.');
    }
    header('Location: grades.php');
    exit;
}

$enrollmentQuery = "SELECT e.id, s.matric_no, s.full_name, c.code, e.session_year, e.semester
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    JOIN courses c ON e.course_id = c.id";
$enrollmentParams = [];
if ($role === 'lecturer') {
    $enrollmentQuery .= ' JOIN lecturer_courses lc ON lc.course_id = c.id AND lc.lecturer_id = ?';
    $enrollmentParams[] = $userId;
}
$enrollmentQuery .= ' ORDER BY e.created_at DESC';
$stmt = $pdo->prepare($enrollmentQuery);
$stmt->execute($enrollmentParams);
$enrollments = $stmt->fetchAll();

$q = trim($_GET['q'] ?? '');
$sessionFilter = trim($_GET['session'] ?? '');
$gradeSql = "SELECT g.id, e.id AS enrollment_id, s.matric_no, s.full_name, c.code, g.score, g.grade, g.remark, e.session_year
    FROM grades g
    JOIN enrollments e ON g.enrollment_id = e.id
    JOIN students s ON e.student_id = s.id
    JOIN courses c ON e.course_id = c.id
    WHERE 1=1";
$gradeParams = [];
if ($role === 'lecturer') {
    $gradeSql .= ' AND EXISTS (SELECT 1 FROM lecturer_courses lc WHERE lc.course_id = c.id AND lc.lecturer_id = ?)';
    $gradeParams[] = $userId;
}
if ($q !== '') {
    $gradeSql .= ' AND (s.matric_no LIKE ? OR s.full_name LIKE ? OR c.code LIKE ?)';
    $gradeParams[] = "%$q%";
    $gradeParams[] = "%$q%";
    $gradeParams[] = "%$q%";
}
if ($sessionFilter !== '') {
    $gradeSql .= ' AND e.session_year = ?';
    $gradeParams[] = $sessionFilter;
}
$gradeSql .= ' ORDER BY g.updated_at DESC';
$stmt = $pdo->prepare($gradeSql);
$stmt->execute($gradeParams);
$gradeRows = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<?php if ($msg = flash('ok')): ?><div class="alert alert-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<form method="get" class="panel">
    <h3 style="margin-top:0;">Search/Filter Grades</h3>
    <div class="filter-grid">
        <div><label>Keyword</label><input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Matric, student, course"></div>
        <div><label>Session</label><input name="session" value="<?= htmlspecialchars($sessionFilter) ?>" placeholder="2025/2026"></div>
        <div><button class="btn btn-soft" type="submit">Filter</button></div>
        <div><a class="btn btn-soft" href="grades.php">Reset</a></div>
    </div>
</form>

<form method="post" class="panel">
    <h3 style="margin-top:0;">Record/Update Grade</h3>
    <div class="form-grid">
        <div><label>Enrollment</label><select name="enrollment_id" required><?php foreach ($enrollments as $e): ?><option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['matric_no'] . ' | ' . $e['code'] . ' | ' . $e['session_year'] . ' ' . $e['semester']) ?></option><?php endforeach; ?></select></div>
        <div><label>Score (0-100)</label><input type="number" step="0.01" min="0" max="100" name="score" required></div>
    </div>
    <div style="margin-top:12px;"><button class="btn btn-primary" type="submit">Save Grade</button></div>
</form>

<div class="card table-wrap">
    <table>
        <thead><tr><th>Matric</th><th>Student</th><th>Course</th><th>Score</th><th>Grade</th><th>Remark</th><th>Session</th><th>Action</th></tr></thead>
        <tbody><?php foreach ($gradeRows as $g): ?><tr><td><?= htmlspecialchars($g['matric_no']) ?></td><td><?= htmlspecialchars($g['full_name']) ?></td><td><?= htmlspecialchars($g['code']) ?></td><td><?= htmlspecialchars($g['score']) ?></td><td><?= htmlspecialchars($g['grade']) ?></td><td><?= htmlspecialchars($g['remark']) ?></td><td><?= htmlspecialchars($g['session_year']) ?></td><td><a class="btn btn-sm btn-danger" onclick="return confirm('Delete this grade?')" href="grades.php?delete=<?= $g['id'] ?>">Delete</a></td></tr><?php endforeach; ?></tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
