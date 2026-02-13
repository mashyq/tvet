<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'registrar', 'lecturer']);

$studentId = (int) ($_GET['student_id'] ?? 0);
if ($studentId <= 0) {
    exit('Invalid student selection.');
}

$studentStmt = $pdo->prepare('SELECT s.*, p.name AS programme_name FROM students s JOIN programmes p ON s.programme_id = p.id WHERE s.id = ?');
$studentStmt->execute([$studentId]);
$student = $studentStmt->fetch();
if (!$student) {
    exit('Student not found.');
}

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
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transcript Print</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="content">
    <div class="no-print" style="margin-bottom:10px;"><button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button></div>
    <div class="card">
        <h2 style="margin-top:0;">Official Academic Transcript</h2>
        <p><strong><?= htmlspecialchars($student['full_name']) ?></strong> | <?= htmlspecialchars($student['matric_no']) ?> | <?= htmlspecialchars($student['programme_name']) ?></p>
    </div>
    <div class="card table-wrap" style="margin-top:12px;">
        <table>
            <thead><tr><th>Session</th><th>Semester</th><th>Course</th><th>Title</th><th>Unit</th><th>Score</th><th>Grade</th></tr></thead>
            <tbody><?php foreach ($results as $r): ?><tr><td><?= htmlspecialchars($r['session_year']) ?></td><td><?= htmlspecialchars($r['semester']) ?></td><td><?= htmlspecialchars($r['code']) ?></td><td><?= htmlspecialchars($r['title']) ?></td><td><?= htmlspecialchars($r['credit_unit']) ?></td><td><?= htmlspecialchars($r['score'] ?? '-') ?></td><td><?= htmlspecialchars($r['grade'] ?? '-') ?></td></tr><?php endforeach; ?></tbody>
        </table>
    </div>
    <?php if ($gpa !== null): ?><p><strong>GPA: <?= htmlspecialchars((string)$gpa) ?></strong></p><?php endif; ?>
</div>
</body>
</html>
