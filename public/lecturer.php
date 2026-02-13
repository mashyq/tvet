<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['lecturer']);

$userId = (int) $_SESSION['user']['id'];
$q = trim($_GET['q'] ?? '');
$session = trim($_GET['session'] ?? '');

$sql = "SELECT e.id AS enrollment_id, s.matric_no, s.full_name, c.code, c.title, e.session_year, e.semester,
    g.score, g.grade
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN students s ON e.student_id = s.id
    JOIN lecturer_courses lc ON lc.course_id = c.id
    LEFT JOIN grades g ON g.enrollment_id = e.id
    WHERE lc.lecturer_id = ?";
$params = [$userId];
if ($q !== '') {
    $sql .= ' AND (s.matric_no LIKE ? OR s.full_name LIKE ? OR c.code LIKE ? OR c.title LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($session !== '') {
    $sql .= ' AND e.session_year = ?';
    $params[] = $session;
}
$sql .= ' ORDER BY e.session_year DESC, c.code';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$assignedCourses = $pdo->prepare("SELECT c.code, c.title FROM lecturer_courses lc JOIN courses c ON lc.course_id = c.id WHERE lc.lecturer_id = ? ORDER BY c.code");
$assignedCourses->execute([$userId]);
$courses = $assignedCourses->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="card" style="margin-bottom:14px;">
    <h3 style="margin-top:0;">Lecturer Functions</h3>
    <p class="muted">1. View assigned courses. 2. Enter/update student grades. 3. Track submitted and pending results by session.</p>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Assigned Courses</th></tr></thead>
            <tbody><?php foreach ($courses as $c): ?><tr><td><?= htmlspecialchars($c['code'] . ' - ' . $c['title']) ?></td></tr><?php endforeach; ?></tbody>
        </table>
    </div>
</div>

<form method="get" class="panel">
    <h3 style="margin-top:0;">Search/Filter Assigned Enrollments</h3>
    <div class="filter-grid">
        <div><label>Keyword</label><input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Matric, student, course"></div>
        <div><label>Session</label><input name="session" value="<?= htmlspecialchars($session) ?>" placeholder="2025/2026"></div>
        <div><button class="btn btn-soft" type="submit">Filter</button></div>
        <div><a class="btn btn-soft" href="lecturer.php">Reset</a></div>
    </div>
</form>

<div class="card table-wrap">
    <table>
        <thead><tr><th>Matric</th><th>Student</th><th>Course</th><th>Session</th><th>Semester</th><th>Score</th><th>Grade</th><th>Action</th></tr></thead>
        <tbody><?php foreach ($rows as $r): ?><tr><td><?= htmlspecialchars($r['matric_no']) ?></td><td><?= htmlspecialchars($r['full_name']) ?></td><td><?= htmlspecialchars($r['code'] . ' - ' . $r['title']) ?></td><td><?= htmlspecialchars($r['session_year']) ?></td><td><?= htmlspecialchars($r['semester']) ?></td><td><?= htmlspecialchars($r['score'] ?? '-') ?></td><td><?= htmlspecialchars($r['grade'] ?? '-') ?></td><td><a class="btn btn-sm btn-primary" href="grades.php">Enter Grade</a></td></tr><?php endforeach; ?></tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
