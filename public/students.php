<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin', 'registrar']);

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
    $stmt->execute([$id]);
    flash('ok', 'Student deleted.');
    header('Location: students.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $matric = strtoupper(sanitize($_POST['matric_no'] ?? ''));
    $fullName = sanitize($_POST['full_name'] ?? '');
    $gender = sanitize($_POST['gender'] ?? 'Male');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $programmeId = (int) ($_POST['programme_id'] ?? 0);
    $level = sanitize($_POST['level'] ?? '100');
    $admissionYear = (int) ($_POST['admission_year'] ?? date('Y'));
    $status = sanitize($_POST['status'] ?? 'Active');

    if ($matric !== '' && $fullName !== '' && $programmeId > 0) {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE students SET matric_no = ?, full_name = ?, gender = ?, email = ?, phone = ?, programme_id = ?, level = ?, admission_year = ?, status = ? WHERE id = ?');
            $stmt->execute([$matric, $fullName, $gender, $email ?: null, $phone ?: null, $programmeId, $level, $admissionYear, $status, $id]);
            flash('ok', 'Student updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO students (matric_no, full_name, gender, email, phone, programme_id, level, admission_year, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$matric, $fullName, $gender, $email ?: null, $phone ?: null, $programmeId, $level, $admissionYear, $status]);
            flash('ok', 'Student added.');
        }
    }
    header('Location: students.php');
    exit;
}

$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$editId]);
    $editRow = $stmt->fetch();
}

$programmes = $pdo->query('SELECT id, name FROM programmes ORDER BY name')->fetchAll();
$q = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$sql = 'SELECT s.*, p.name AS programme_name FROM students s JOIN programmes p ON s.programme_id = p.id WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (s.matric_no LIKE ? OR s.full_name LIKE ? OR p.name LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if (in_array($statusFilter, ['Active', 'Graduated', 'Suspended'], true)) {
    $sql .= ' AND s.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY s.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<?php if ($msg = flash('ok')): ?><div class="alert alert-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<form method="get" class="panel">
    <h3 style="margin-top:0;">Search/Filter Students</h3>
    <div class="filter-grid">
        <div><label>Keyword</label><input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Matric, name, programme"></div>
        <div><label>Status</label><select name="status"><option value="">All</option><option <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active</option><option <?= $statusFilter === 'Graduated' ? 'selected' : '' ?>>Graduated</option><option <?= $statusFilter === 'Suspended' ? 'selected' : '' ?>>Suspended</option></select></div>
        <div><button class="btn btn-soft" type="submit">Filter</button></div>
        <div><a class="btn btn-soft" href="students.php">Reset</a></div>
    </div>
</form>

<form method="post" class="panel">
    <h3 style="margin-top:0;"><?= $editRow ? 'Edit Student' : 'Register Student' ?></h3>
    <input type="hidden" name="id" value="<?= (int) ($editRow['id'] ?? 0) ?>">
    <div class="form-grid">
        <div><label>Matric No</label><input name="matric_no" value="<?= htmlspecialchars($editRow['matric_no'] ?? '') ?>" required></div>
        <div><label>Full Name</label><input name="full_name" value="<?= htmlspecialchars($editRow['full_name'] ?? '') ?>" required></div>
        <div><label>Gender</label><select name="gender"><option <?= ($editRow['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option><option <?= ($editRow['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option></select></div>
        <div><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($editRow['email'] ?? '') ?>"></div>
        <div><label>Phone</label><input name="phone" value="<?= htmlspecialchars($editRow['phone'] ?? '') ?>"></div>
        <div><label>Programme</label><select name="programme_id" required><?php foreach ($programmes as $p): ?><option value="<?= $p['id'] ?>" <?= (int)($editRow['programme_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></div>
        <div><label>Level</label><input name="level" value="<?= htmlspecialchars($editRow['level'] ?? '100') ?>" required></div>
        <div><label>Admission Year</label><input type="number" name="admission_year" value="<?= htmlspecialchars((string)($editRow['admission_year'] ?? date('Y'))) ?>" required></div>
        <div><label>Status</label><select name="status"><option <?= ($editRow['status'] ?? 'Active') === 'Active' ? 'selected' : '' ?>>Active</option><option <?= ($editRow['status'] ?? '') === 'Graduated' ? 'selected' : '' ?>>Graduated</option><option <?= ($editRow['status'] ?? '') === 'Suspended' ? 'selected' : '' ?>>Suspended</option></select></div>
    </div>
    <div style="margin-top:12px;" class="table-actions">
        <button class="btn btn-primary" type="submit"><?= $editRow ? 'Update' : 'Save Student' ?></button>
        <?php if ($editRow): ?><a class="btn btn-soft" href="students.php">Cancel Edit</a><?php endif; ?>
    </div>
</form>

<div class="card table-wrap">
    <table>
        <thead><tr><th>Matric</th><th>Name</th><th>Programme</th><th>Level</th><th>Status</th><th>Action</th></tr></thead>
        <tbody><?php foreach ($students as $s): ?><tr><td><?= htmlspecialchars($s['matric_no']) ?></td><td><?= htmlspecialchars($s['full_name']) ?></td><td><?= htmlspecialchars($s['programme_name']) ?></td><td><?= htmlspecialchars($s['level']) ?></td><td><?= htmlspecialchars($s['status']) ?></td><td class="table-actions"><a class="btn btn-sm btn-soft" href="students.php?edit=<?= $s['id'] ?>">Edit</a><a class="btn btn-sm btn-danger" onclick="return confirm('Delete this student?')" href="students.php?delete=<?= $s['id'] ?>">Delete</a></td></tr><?php endforeach; ?></tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
