<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$role = $_SESSION['user']['role'];
$departmentId = (int) ($_SESSION['user']['department_id'] ?? 0);

$stats = [
    'Departments' => $pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn(),
    'Programmes' => $pdo->query('SELECT COUNT(*) FROM programmes')->fetchColumn(),
    'Students' => $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
    'Courses' => $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
];

$finance = ['fees' => 0, 'paid' => 0, 'outstanding' => 0];
if (in_array($role, ['admin', 'registrar'], true)) {
    $finance['fees'] = (float) $pdo->query('SELECT COALESCE(SUM(amount),0) FROM student_fees')->fetchColumn();
    $finance['paid'] = (float) $pdo->query('SELECT COALESCE(SUM(amount_paid),0) FROM fee_payments')->fetchColumn();
    $finance['outstanding'] = $finance['fees'] - $finance['paid'];
}

if ($role === 'lecturer' && $departmentId > 0) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM courses WHERE department_id = ?');
    $stmt->execute([$departmentId]);
    $stats['My Dept Courses'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        WHERE c.department_id = ?");
    $stmt->execute([$departmentId]);
    $stats['My Dept Enrollments'] = $stmt->fetchColumn();
}

$recentGrades = $pdo->query("SELECT s.full_name, c.code, g.score, g.grade, e.session_year
    FROM grades g
    JOIN enrollments e ON g.enrollment_id = e.id
    JOIN students s ON e.student_id = s.id
    JOIN courses c ON e.course_id = c.id
    ORDER BY g.updated_at DESC LIMIT 8")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="grid grid-4" style="margin-bottom:16px;">
    <?php foreach ($stats as $label => $value): ?>
        <div class="card">
            <div class="muted"><?= htmlspecialchars($label) ?></div>
            <div class="stat"><?= (int) $value ?></div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (in_array($role, ['admin', 'registrar'], true)): ?>
<div class="grid grid-4" style="margin-bottom:16px;">
    <div class="card"><div class="muted">Total Fees</div><div class="stat">$<?= number_format($finance['fees'], 2) ?></div></div>
    <div class="card"><div class="muted">Total Paid</div><div class="stat">$<?= number_format($finance['paid'], 2) ?></div></div>
    <div class="card"><div class="muted">Outstanding</div><div class="stat">$<?= number_format($finance['outstanding'], 2) ?></div></div>
    <div class="card"><div class="muted">Finance Desk</div><a class="btn btn-soft" href="finances.php">Open Finances</a></div>
</div>
<?php endif; ?>

<div class="card">
    <h3 style="margin-top:0;">Recent Grade Updates</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Student</th><th>Course</th><th>Score</th><th>Grade</th><th>Session</th></tr></thead>
            <tbody>
            <?php foreach ($recentGrades as $g): ?>
                <tr>
                    <td><?= htmlspecialchars($g['full_name']) ?></td>
                    <td><?= htmlspecialchars($g['code']) ?></td>
                    <td><?= htmlspecialchars($g['score']) ?></td>
                    <td><?= htmlspecialchars($g['grade']) ?></td>
                    <td><?= htmlspecialchars($g['session_year']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
