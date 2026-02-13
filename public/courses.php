<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin', 'registrar']);

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    try {
        $stmt = $pdo->prepare('DELETE FROM courses WHERE id = ?');
        $stmt->execute([$id]);
        flash('ok', 'Course deleted.');
    } catch (PDOException $e) {
        flash('ok', 'Cannot delete course linked to enrollment.');
    }
    header('Location: courses.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $code = strtoupper(sanitize($_POST['code'] ?? ''));
    $title = sanitize($_POST['title'] ?? '');
    $credit = (int) ($_POST['credit_unit'] ?? 2);
    $semester = sanitize($_POST['semester'] ?? 'First');
    $level = sanitize($_POST['level'] ?? '100');

    if ($departmentId > 0 && $code !== '' && $title !== '') {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE courses SET department_id = ?, code = ?, title = ?, credit_unit = ?, semester = ?, level = ? WHERE id = ?');
            $stmt->execute([$departmentId, $code, $title, $credit, $semester, $level, $id]);
            flash('ok', 'Course updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO courses (department_id, code, title, credit_unit, semester, level) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$departmentId, $code, $title, $credit, $semester, $level]);
            flash('ok', 'Course saved.');
        }
    }
    header('Location: courses.php');
    exit;
}

$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM courses WHERE id = ?');
    $stmt->execute([$editId]);
    $editRow = $stmt->fetch();
}

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$q = trim($_GET['q'] ?? '');
$sem = trim($_GET['semester'] ?? '');
$sql = 'SELECT c.*, d.name AS department_name FROM courses c JOIN departments d ON c.department_id = d.id WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (c.code LIKE ? OR c.title LIKE ? OR d.name LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if (in_array($sem, ['First', 'Second'], true)) {
    $sql .= ' AND c.semester = ?';
    $params[] = $sem;
}
$sql .= ' ORDER BY c.code';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<?php if ($msg = flash('ok')): ?><div class="alert alert-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<form method="get" class="panel">
    <h3 style="margin-top:0;">Search/Filter Courses</h3>
    <div class="filter-grid">
        <div><label>Keyword</label><input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Code, title, department"></div>
        <div><label>Semester</label><select name="semester"><option value="">All</option><option <?= $sem === 'First' ? 'selected' : '' ?>>First</option><option <?= $sem === 'Second' ? 'selected' : '' ?>>Second</option></select></div>
        <div><button class="btn btn-soft" type="submit">Filter</button></div>
        <div><a class="btn btn-soft" href="courses.php">Reset</a></div>
    </div>
</form>

<form method="post" class="panel">
    <h3 style="margin-top:0;"><?= $editRow ? 'Edit Course' : 'Add Course' ?></h3>
    <input type="hidden" name="id" value="<?= (int) ($editRow['id'] ?? 0) ?>">
    <div class="form-grid">
        <div><label>Department</label><select name="department_id" required><?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>" <?= (int)($editRow['department_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?></select></div>
        <div><label>Code</label><input name="code" value="<?= htmlspecialchars($editRow['code'] ?? '') ?>" required></div>
        <div><label>Title</label><input name="title" value="<?= htmlspecialchars($editRow['title'] ?? '') ?>" required></div>
        <div><label>Credit Unit</label><input type="number" min="1" max="6" name="credit_unit" value="<?= htmlspecialchars((string)($editRow['credit_unit'] ?? 2)) ?>" required></div>
        <div><label>Semester</label><select name="semester"><option <?= ($editRow['semester'] ?? '') === 'First' ? 'selected' : '' ?>>First</option><option <?= ($editRow['semester'] ?? '') === 'Second' ? 'selected' : '' ?>>Second</option></select></div>
        <div><label>Level</label><input name="level" value="<?= htmlspecialchars($editRow['level'] ?? '100') ?>" required></div>
    </div>
    <div style="margin-top:12px;" class="table-actions">
        <button class="btn btn-primary" type="submit"><?= $editRow ? 'Update' : 'Save Course' ?></button>
        <?php if ($editRow): ?><a class="btn btn-soft" href="courses.php">Cancel Edit</a><?php endif; ?>
    </div>
</form>

<div class="card table-wrap">
    <table>
        <thead><tr><th>Code</th><th>Title</th><th>Department</th><th>Unit</th><th>Semester</th><th>Action</th></tr></thead>
        <tbody><?php foreach ($courses as $c): ?><tr><td><?= htmlspecialchars($c['code']) ?></td><td><?= htmlspecialchars($c['title']) ?></td><td><?= htmlspecialchars($c['department_name']) ?></td><td><?= htmlspecialchars($c['credit_unit']) ?></td><td><?= htmlspecialchars($c['semester']) ?></td><td class="table-actions"><a class="btn btn-sm btn-soft" href="courses.php?edit=<?= $c['id'] ?>">Edit</a><a class="btn btn-sm btn-danger" onclick="return confirm('Delete this course?')" href="courses.php?delete=<?= $c['id'] ?>">Delete</a></td></tr><?php endforeach; ?></tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
