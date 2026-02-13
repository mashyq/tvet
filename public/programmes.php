<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin', 'registrar']);

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    try {
        $stmt = $pdo->prepare('DELETE FROM programmes WHERE id = ?');
        $stmt->execute([$id]);
        flash('ok', 'Programme deleted.');
    } catch (PDOException $e) {
        flash('ok', 'Cannot delete programme linked to records.');
    }
    header('Location: programmes.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $code = strtoupper(sanitize($_POST['code'] ?? ''));
    $name = sanitize($_POST['name'] ?? '');
    $duration = (int) ($_POST['duration_years'] ?? 4);

    if ($departmentId > 0 && $code !== '' && $name !== '') {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE programmes SET department_id = ?, code = ?, name = ?, duration_years = ? WHERE id = ?');
            $stmt->execute([$departmentId, $code, $name, $duration, $id]);
            flash('ok', 'Programme updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO programmes (department_id, code, name, duration_years) VALUES (?, ?, ?, ?)');
            $stmt->execute([$departmentId, $code, $name, $duration]);
            flash('ok', 'Programme saved.');
        }
    }
    header('Location: programmes.php');
    exit;
}

$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM programmes WHERE id = ?');
    $stmt->execute([$editId]);
    $editRow = $stmt->fetch();
}

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $stmt = $pdo->prepare("SELECT p.*, d.name AS department_name FROM programmes p JOIN departments d ON p.department_id = d.id WHERE p.code LIKE ? OR p.name LIKE ? OR d.name LIKE ? ORDER BY p.name");
    $stmt->execute(["%$q%", "%$q%", "%$q%"]);
    $programmes = $stmt->fetchAll();
} else {
    $programmes = $pdo->query('SELECT p.*, d.name AS department_name FROM programmes p JOIN departments d ON p.department_id = d.id ORDER BY p.name')->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>
<?php if ($msg = flash('ok')): ?><div class="alert alert-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<form method="get" class="panel">
    <h3 style="margin-top:0;">Search Programmes</h3>
    <div class="filter-grid">
        <div><label>Keyword</label><input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Code, programme, department"></div>
        <div><button class="btn btn-soft" type="submit">Filter</button></div>
        <div><a class="btn btn-soft" href="programmes.php">Reset</a></div>
    </div>
</form>

<form method="post" class="panel">
    <h3 style="margin-top:0;"><?= $editRow ? 'Edit Programme' : 'Add Programme' ?></h3>
    <input type="hidden" name="id" value="<?= (int) ($editRow['id'] ?? 0) ?>">
    <div class="form-grid">
        <div><label>Department</label><select name="department_id" required><?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>" <?= (int)($editRow['department_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?></select></div>
        <div><label>Code</label><input name="code" value="<?= htmlspecialchars($editRow['code'] ?? '') ?>" required></div>
        <div><label>Name</label><input name="name" value="<?= htmlspecialchars($editRow['name'] ?? '') ?>" required></div>
        <div><label>Duration (Years)</label><input type="number" min="1" max="8" name="duration_years" value="<?= htmlspecialchars((string)($editRow['duration_years'] ?? 4)) ?>" required></div>
    </div>
    <div style="margin-top:12px;" class="table-actions">
        <button class="btn btn-primary" type="submit"><?= $editRow ? 'Update' : 'Save Programme' ?></button>
        <?php if ($editRow): ?><a class="btn btn-soft" href="programmes.php">Cancel Edit</a><?php endif; ?>
    </div>
</form>

<div class="card table-wrap">
    <table>
        <thead><tr><th>Code</th><th>Name</th><th>Department</th><th>Duration</th><th>Action</th></tr></thead>
        <tbody><?php foreach ($programmes as $p): ?><tr><td><?= htmlspecialchars($p['code']) ?></td><td><?= htmlspecialchars($p['name']) ?></td><td><?= htmlspecialchars($p['department_name']) ?></td><td><?= htmlspecialchars($p['duration_years']) ?> years</td><td class="table-actions"><a class="btn btn-sm btn-soft" href="programmes.php?edit=<?= $p['id'] ?>">Edit</a><a class="btn btn-sm btn-danger" onclick="return confirm('Delete this programme?')" href="programmes.php?delete=<?= $p['id'] ?>">Delete</a></td></tr><?php endforeach; ?></tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
