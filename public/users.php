<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id !== (int) $_SESSION['user']['id']) {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
        flash('ok', 'User deleted.');
    } else {
        flash('ok', 'You cannot delete your own account.');
    }
    header('Location: users.php');
    exit;
}

if (isset($_GET['delete_assignment'])) {
    $id = (int) $_GET['delete_assignment'];
    $stmt = $pdo->prepare('DELETE FROM lecturer_courses WHERE id = ?');
    $stmt->execute([$id]);
    flash('ok', 'Lecturer course assignment removed.');
    header('Location: users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_user';

    if ($action === 'save_user') {
        $id = (int) ($_POST['id'] ?? 0);
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $role = sanitize($_POST['role'] ?? 'lecturer');
        $password = $_POST['password'] ?? '';
        $departmentId = (int) ($_POST['department_id'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($fullName !== '' && $email !== '') {
            if ($id > 0) {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, role = ?, department_id = ?, is_active = ?, password_hash = ? WHERE id = ?');
                    $stmt->execute([$fullName, $email, $role, $departmentId ?: null, $isActive, $hash, $id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, role = ?, department_id = ?, is_active = ? WHERE id = ?');
                    $stmt->execute([$fullName, $email, $role, $departmentId ?: null, $isActive, $id]);
                }
                flash('ok', 'User updated.');
            } elseif ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, department_id, is_active) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$fullName, $email, $hash, $role, $departmentId ?: null, $isActive]);
                flash('ok', 'User account created.');
            }
        }
    }

    if ($action === 'assign_course') {
        $lecturerId = (int) ($_POST['lecturer_id'] ?? 0);
        $courseId = (int) ($_POST['course_id'] ?? 0);
        if ($lecturerId > 0 && $courseId > 0) {
            $stmt = $pdo->prepare('INSERT IGNORE INTO lecturer_courses (lecturer_id, course_id) VALUES (?, ?)');
            $stmt->execute([$lecturerId, $courseId]);
            flash('ok', 'Course assigned to lecturer.');
        }
    }

    header('Location: users.php');
    exit;
}

$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$editId]);
    $editRow = $stmt->fetch();
}

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT u.*, d.name AS department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR u.role LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
$sql .= ' ORDER BY u.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$lecturers = $pdo->query("SELECT id, full_name FROM users WHERE role = 'lecturer' AND is_active = 1 ORDER BY full_name")->fetchAll();
$courses = $pdo->query('SELECT id, code, title FROM courses ORDER BY code')->fetchAll();
$assignments = $pdo->query("SELECT lc.id, u.full_name, c.code, c.title FROM lecturer_courses lc JOIN users u ON lc.lecturer_id = u.id JOIN courses c ON lc.course_id = c.id ORDER BY u.full_name, c.code")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<?php if ($msg = flash('ok')): ?><div class="alert alert-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<form method="get" class="panel">
    <h3 style="margin-top:0;">Search Users</h3>
    <div class="filter-grid">
        <div><label>Keyword</label><input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Name, email, role"></div>
        <div><button class="btn btn-soft" type="submit">Filter</button></div>
        <div><a class="btn btn-soft" href="users.php">Reset</a></div>
    </div>
</form>

<form method="post" class="panel">
    <h3 style="margin-top:0;"><?= $editRow ? 'Edit Stakeholder User' : 'Create Stakeholder User' ?></h3>
    <input type="hidden" name="action" value="save_user">
    <input type="hidden" name="id" value="<?= (int) ($editRow['id'] ?? 0) ?>">
    <div class="form-grid">
        <div><label>Full Name</label><input name="full_name" value="<?= htmlspecialchars($editRow['full_name'] ?? '') ?>" required></div>
        <div><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($editRow['email'] ?? '') ?>" required></div>
        <div><label>Role</label><select name="role"><option value="registrar" <?= ($editRow['role'] ?? '') === 'registrar' ? 'selected' : '' ?>>Registrar</option><option value="lecturer" <?= ($editRow['role'] ?? '') === 'lecturer' ? 'selected' : '' ?>>Lecturer</option><option value="admin" <?= ($editRow['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option></select></div>
        <div><label>Department</label><select name="department_id"><option value="">N/A</option><?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>" <?= (int)($editRow['department_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?></select></div>
        <div><label><?= $editRow ? 'New Password (optional)' : 'Temporary Password' ?></label><input type="password" name="password" <?= $editRow ? '' : 'required' ?>></div>
        <div><label>Status</label><div><input style="width:auto;" type="checkbox" name="is_active" <?= !isset($editRow['is_active']) || (int)$editRow['is_active'] === 1 ? 'checked' : '' ?>> Active</div></div>
    </div>
    <div style="margin-top:12px;" class="table-actions">
        <button class="btn btn-primary" type="submit"><?= $editRow ? 'Update User' : 'Create User' ?></button>
        <?php if ($editRow): ?><a class="btn btn-soft" href="users.php">Cancel Edit</a><?php endif; ?>
    </div>
</form>

<form method="post" class="panel">
    <h3 style="margin-top:0;">Assign Course to Lecturer</h3>
    <input type="hidden" name="action" value="assign_course">
    <div class="form-grid">
        <div><label>Lecturer</label><select name="lecturer_id" required><?php foreach ($lecturers as $l): ?><option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['full_name']) ?></option><?php endforeach; ?></select></div>
        <div><label>Course</label><select name="course_id" required><?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['code'] . ' - ' . $c['title']) ?></option><?php endforeach; ?></select></div>
    </div>
    <div style="margin-top:12px;"><button class="btn btn-primary" type="submit">Assign</button></div>
</form>

<div class="card table-wrap" style="margin-bottom:14px;">
    <h3 style="margin-top:0;">Users</h3>
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Status</th><th>Action</th></tr></thead>
        <tbody><?php foreach ($users as $u): ?><tr><td><?= htmlspecialchars($u['full_name']) ?></td><td><?= htmlspecialchars($u['email']) ?></td><td><?= htmlspecialchars($u['role']) ?></td><td><?= htmlspecialchars($u['department_name'] ?? '-') ?></td><td><?= $u['is_active'] ? 'Active' : 'Disabled' ?></td><td class="table-actions"><a class="btn btn-sm btn-soft" href="users.php?edit=<?= $u['id'] ?>">Edit</a><a class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')" href="users.php?delete=<?= $u['id'] ?>">Delete</a></td></tr><?php endforeach; ?></tbody>
    </table>
</div>

<div class="card table-wrap">
    <h3 style="margin-top:0;">Lecturer Course Assignments</h3>
    <table>
        <thead><tr><th>Lecturer</th><th>Course</th><th>Action</th></tr></thead>
        <tbody><?php foreach ($assignments as $a): ?><tr><td><?= htmlspecialchars($a['full_name']) ?></td><td><?= htmlspecialchars($a['code'] . ' - ' . $a['title']) ?></td><td><a class="btn btn-sm btn-danger" onclick="return confirm('Remove assignment?')" href="users.php?delete_assignment=<?= $a['id'] ?>">Remove</a></td></tr><?php endforeach; ?></tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
