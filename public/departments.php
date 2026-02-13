<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin', 'registrar']);

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    try {
        $stmt = $pdo->prepare('DELETE FROM departments WHERE id = ?');
        $stmt->execute([$id]);
        flash('ok', 'Department deleted.');
    } catch (PDOException $e) {
        flash('ok', 'Cannot delete department linked to other records.');
    }
    header('Location: departments.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $code = strtoupper(sanitize($_POST['code'] ?? ''));
    $name = sanitize($_POST['name'] ?? '');
    $hod = sanitize($_POST['hod_name'] ?? '');

    if ($code !== '' && $name !== '') {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE departments SET code = ?, name = ?, hod_name = ? WHERE id = ?');
            $stmt->execute([$code, $name, $hod ?: null, $id]);
            flash('ok', 'Department updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO departments (code, name, hod_name) VALUES (?, ?, ?)');
            $stmt->execute([$code, $name, $hod ?: null]);
            flash('ok', 'Department created successfully.');
        }
    }
    header('Location: departments.php');
    exit;
}

$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM departments WHERE id = ?');
    $stmt->execute([$editId]);
    $editRow = $stmt->fetch();
}

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $stmt = $pdo->prepare('SELECT * FROM departments WHERE code LIKE ? OR name LIKE ? ORDER BY name');
    $stmt->execute(["%$q%", "%$q%"]);
    $departments = $stmt->fetchAll();
} else {
    $departments = $pdo->query('SELECT * FROM departments ORDER BY name')->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>
<?php if ($msg = flash('ok')): ?><div class="alert alert-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<form method="get" class="panel">
    <h3 style="margin-top:0;">Search Departments</h3>
    <div class="filter-grid">
        <div><label>Keyword</label><input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Code or name"></div>
        <div><button class="btn btn-soft" type="submit">Filter</button></div>
        <div><a class="btn btn-soft" href="departments.php">Reset</a></div>
    </div>
</form>

<form method="post" class="panel">
    <h3 style="margin-top:0;"><?= $editRow ? 'Edit Department' : 'Add Department' ?></h3>
    <input type="hidden" name="id" value="<?= (int) ($editRow['id'] ?? 0) ?>">
    <div class="form-grid">
        <div><label>Code</label><input name="code" value="<?= htmlspecialchars($editRow['code'] ?? '') ?>" required></div>
        <div><label>Department Name</label><input name="name" value="<?= htmlspecialchars($editRow['name'] ?? '') ?>" required></div>
        <div><label>Head of Department</label><input name="hod_name" value="<?= htmlspecialchars($editRow['hod_name'] ?? '') ?>"></div>
    </div>
    <div style="margin-top:12px;" class="table-actions">
        <button class="btn btn-primary" type="submit"><?= $editRow ? 'Update' : 'Save Department' ?></button>
        <?php if ($editRow): ?><a class="btn btn-soft" href="departments.php">Cancel Edit</a><?php endif; ?>
    </div>
</form>

<div class="card table-wrap">
    <table>
        <thead><tr><th>Code</th><th>Name</th><th>HOD</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($departments as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['code']) ?></td>
                <td><?= htmlspecialchars($d['name']) ?></td>
                <td><?= htmlspecialchars($d['hod_name'] ?? '-') ?></td>
                <td class="table-actions">
                    <a class="btn btn-sm btn-soft" href="departments.php?edit=<?= $d['id'] ?>">Edit</a>
                    <a class="btn btn-sm btn-danger" onclick="return confirm('Delete this department?')" href="departments.php?delete=<?= $d['id'] ?>">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
