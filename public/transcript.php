<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin', 'registrar', 'lecturer']);

$students = $pdo->query('SELECT id, matric_no, full_name FROM students ORDER BY full_name')->fetchAll();
$studentId = (int) ($_GET['student_id'] ?? 0);
$results = [];
$gpa = null;
$student = null;

if ($studentId > 0) {
    $studentStmt = $pdo->prepare('SELECT s.*, p.name AS programme_name FROM students s JOIN programmes p ON s.programme_id = p.id WHERE s.id = ?');
    $studentStmt->execute([$studentId]);
    $student = $studentStmt->fetch();

    $stmt = $pdo->prepare("SELECT c.code, c.title, c.credit_unit, e.session_year, e.semester, g.score, g.grade, g.grade_point
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN grades g ON g.enrollment_id = e.id
        WHERE e.student_id = ?
        ORDER BY e.session_year, e.semester, c.code");
    $stmt->execute([$studentId]);
    $results = $stmt->fetchAll();

    $totalUnits = 0;
    $totalPoints = 0;
    foreach ($results as $r) {
        if ($r['grade_point'] !== null) {
            $totalUnits += (int) $r['credit_unit'];
            $totalPoints += ((float) $r['grade_point']) * ((int) $r['credit_unit']);
        }
    }
    $gpa = $totalUnits > 0 ? round($totalPoints / $totalUnits, 2) : null;
}

include __DIR__ . '/../includes/header.php';
?>

<form method="get" class="panel">
    <h3 style="margin-top:0;">Generate Transcript</h3>
    <div class="filter-grid">
        <div><label>Student</label><select name="student_id" required><option value="">Select</option><?php foreach ($students as $s): ?><option value="<?= $s['id'] ?>" <?= $studentId === (int)$s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['matric_no'] . ' - ' . $s['full_name']) ?></option><?php endforeach; ?></select></div>
        <div><button class="btn btn-primary" type="submit">View Transcript</button></div>
        <?php if ($studentId > 0): ?><div><a class="btn btn-soft" target="_blank" href="transcript_print.php?student_id=<?= $studentId ?>">Printable PDF</a></div><?php endif; ?>
    </div>
</form>

<?php if ($student): ?>
<div class="card" style="margin-bottom:12px;">
    <h3 style="margin:0;">Student Transcript</h3>
    <p class="muted" style="margin:6px 0 0;"><strong><?= htmlspecialchars($student['full_name']) ?></strong> | <?= htmlspecialchars($student['matric_no']) ?> | <?= htmlspecialchars($student['programme_name']) ?></p>
</div>

<div class="card table-wrap">
    <table>
        <thead><tr><th>Session</th><th>Semester</th><th>Course</th><th>Title</th><th>Unit</th><th>Score</th><th>Grade</th></tr></thead>
        <tbody><?php foreach ($results as $r): ?><tr><td><?= htmlspecialchars($r['session_year']) ?></td><td><?= htmlspecialchars($r['semester']) ?></td><td><?= htmlspecialchars($r['code']) ?></td><td><?= htmlspecialchars($r['title']) ?></td><td><?= htmlspecialchars($r['credit_unit']) ?></td><td><?= htmlspecialchars($r['score'] ?? '-') ?></td><td><?= htmlspecialchars($r['grade'] ?? '-') ?></td></tr><?php endforeach; ?></tbody>
    </table>
</div>
<?php if ($gpa !== null): ?><div class="alert alert-ok" style="margin-top:10px;">Current GPA: <strong><?= htmlspecialchars((string)$gpa) ?></strong></div><?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
