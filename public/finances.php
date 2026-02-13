<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin', 'registrar']);

if (isset($_GET['delete_fee'])) {
    $feeId = (int) $_GET['delete_fee'];
    $stmt = $pdo->prepare('DELETE FROM student_fees WHERE id = ?');
    $stmt->execute([$feeId]);
    flash('ok', 'Fee invoice deleted.');
    header('Location: finances.php');
    exit;
}

if (isset($_GET['delete_payment'])) {
    $paymentId = (int) $_GET['delete_payment'];
    $stmt = $pdo->prepare('SELECT student_fee_id FROM fee_payments WHERE id = ?');
    $stmt->execute([$paymentId]);
    $feeId = (int) ($stmt->fetchColumn() ?? 0);

    $stmt = $pdo->prepare('DELETE FROM fee_payments WHERE id = ?');
    $stmt->execute([$paymentId]);
    if ($feeId > 0) {
        recalc_fee_status($pdo, $feeId);
    }

    flash('ok', 'Payment deleted.');
    header('Location: finances.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_fee') {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $sessionYear = sanitize($_POST['session_year'] ?? '');
        $description = sanitize($_POST['description'] ?? 'Tuition Fee');
        $amount = (float) ($_POST['amount'] ?? 0);

        if ($studentId > 0 && $sessionYear !== '' && $amount > 0) {
            $stmt = $pdo->prepare('INSERT INTO student_fees (student_id, session_year, description, amount) VALUES (?, ?, ?, ?)');
            $stmt->execute([$studentId, $sessionYear, $description, $amount]);
            flash('ok', 'Fee invoice created.');
        }
    }

    if ($action === 'add_payment') {
        $feeId = (int) ($_POST['student_fee_id'] ?? 0);
        $amountPaid = (float) ($_POST['amount_paid'] ?? 0);
        $paymentDate = sanitize($_POST['payment_date'] ?? '');
        $paymentRef = sanitize($_POST['payment_ref'] ?? '');

        if ($feeId > 0 && $amountPaid > 0 && $paymentDate !== '') {
            $stmt = $pdo->prepare('INSERT INTO fee_payments (student_fee_id, amount_paid, payment_date, payment_ref, recorded_by) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$feeId, $amountPaid, $paymentDate, $paymentRef ?: null, $_SESSION['user']['id']]);
            recalc_fee_status($pdo, $feeId);
            flash('ok', 'Payment recorded.');
        }
    }

    header('Location: finances.php');
    exit;
}

$students = $pdo->query('SELECT id, matric_no, full_name FROM students ORDER BY full_name')->fetchAll();

$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$session = trim($_GET['session'] ?? '');

$sql = "SELECT sf.*, s.matric_no, s.full_name,
    COALESCE((SELECT SUM(fp.amount_paid) FROM fee_payments fp WHERE fp.student_fee_id = sf.id),0) AS paid_total
    FROM student_fees sf
    JOIN students s ON sf.student_id = s.id
    WHERE 1=1";
$params = [];
if ($q !== '') {
    $sql .= ' AND (s.matric_no LIKE ? OR s.full_name LIKE ? OR sf.description LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($session !== '') {
    $sql .= ' AND sf.session_year = ?';
    $params[] = $session;
}
if (in_array($status, ['Unpaid', 'Part Paid', 'Paid'], true)) {
    $sql .= ' AND sf.status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY sf.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fees = $stmt->fetchAll();

$feeOptions = $pdo->query("SELECT sf.id, sf.description, sf.amount, sf.session_year, s.matric_no, s.full_name,
    COALESCE((SELECT SUM(fp.amount_paid) FROM fee_payments fp WHERE fp.student_fee_id = sf.id),0) AS paid_total
    FROM student_fees sf
    JOIN students s ON sf.student_id = s.id
    WHERE sf.status <> 'Paid'
    ORDER BY sf.created_at DESC")->fetchAll();

$payments = $pdo->query("SELECT fp.id, fp.amount_paid, fp.payment_date, fp.payment_ref, s.matric_no, s.full_name, sf.description
    FROM fee_payments fp
    JOIN student_fees sf ON fp.student_fee_id = sf.id
    JOIN students s ON sf.student_id = s.id
    ORDER BY fp.created_at DESC LIMIT 100")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<?php if ($msg = flash('ok')): ?><div class="alert alert-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<form method="get" class="panel">
    <h3 style="margin-top:0;">Search/Filter Finance Records</h3>
    <div class="filter-grid">
        <div><label>Keyword</label><input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Matric, student, description"></div>
        <div><label>Session</label><input name="session" value="<?= htmlspecialchars($session) ?>" placeholder="2025/2026"></div>
        <div><label>Status</label><select name="status"><option value="">All</option><option <?= $status === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option><option <?= $status === 'Part Paid' ? 'selected' : '' ?>>Part Paid</option><option <?= $status === 'Paid' ? 'selected' : '' ?>>Paid</option></select></div>
        <div><button class="btn btn-soft" type="submit">Filter</button></div>
    </div>
</form>

<form method="post" class="panel">
    <h3 style="margin-top:0;">Create Student Fee Invoice</h3>
    <input type="hidden" name="action" value="add_fee">
    <div class="form-grid">
        <div><label>Student</label><select name="student_id" required><?php foreach ($students as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['matric_no'] . ' - ' . $s['full_name']) ?></option><?php endforeach; ?></select></div>
        <div><label>Session Year</label><input name="session_year" placeholder="2025/2026" required></div>
        <div><label>Description</label><input name="description" value="Tuition Fee" required></div>
        <div><label>Amount</label><input type="number" step="0.01" min="0" name="amount" required></div>
    </div>
    <div style="margin-top:12px;"><button class="btn btn-primary" type="submit">Create Invoice</button></div>
</form>

<form method="post" class="panel">
    <h3 style="margin-top:0;">Record Payment</h3>
    <input type="hidden" name="action" value="add_payment">
    <div class="form-grid">
        <div><label>Fee Invoice</label><select name="student_fee_id" required><?php foreach ($feeOptions as $f): ?><option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['matric_no'] . ' | ' . $f['description'] . ' | Bal: $' . number_format($f['amount'] - $f['paid_total'], 2)) ?></option><?php endforeach; ?></select></div>
        <div><label>Amount Paid</label><input type="number" step="0.01" min="0" name="amount_paid" required></div>
        <div><label>Payment Date</label><input type="date" name="payment_date" required></div>
        <div><label>Reference</label><input name="payment_ref"></div>
    </div>
    <div style="margin-top:12px;"><button class="btn btn-primary" type="submit">Save Payment</button></div>
</form>

<div class="card table-wrap" style="margin-bottom:14px;">
    <h3 style="margin-top:0;">Fee Invoices</h3>
    <table>
        <thead><tr><th>Student</th><th>Description</th><th>Session</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th><th>Action</th></tr></thead>
        <tbody><?php foreach ($fees as $f): $balance = (float)$f['amount'] - (float)$f['paid_total']; ?><tr><td><?= htmlspecialchars($f['matric_no'] . ' - ' . $f['full_name']) ?></td><td><?= htmlspecialchars($f['description']) ?></td><td><?= htmlspecialchars($f['session_year']) ?></td><td>$<?= number_format((float)$f['amount'], 2) ?></td><td>$<?= number_format((float)$f['paid_total'], 2) ?></td><td>$<?= number_format($balance, 2) ?></td><td><?= htmlspecialchars($f['status']) ?></td><td><a class="btn btn-sm btn-danger" onclick="return confirm('Delete this invoice?')" href="finances.php?delete_fee=<?= $f['id'] ?>">Delete</a></td></tr><?php endforeach; ?></tbody>
    </table>
</div>

<div class="card table-wrap">
    <h3 style="margin-top:0;">Recent Payments</h3>
    <table>
        <thead><tr><th>Date</th><th>Student</th><th>Description</th><th>Amount</th><th>Reference</th><th>Action</th></tr></thead>
        <tbody><?php foreach ($payments as $p): ?><tr><td><?= htmlspecialchars($p['payment_date']) ?></td><td><?= htmlspecialchars($p['matric_no'] . ' - ' . $p['full_name']) ?></td><td><?= htmlspecialchars($p['description']) ?></td><td>$<?= number_format((float)$p['amount_paid'], 2) ?></td><td><?= htmlspecialchars($p['payment_ref'] ?? '-') ?></td><td><a class="btn btn-sm btn-danger" onclick="return confirm('Delete this payment?')" href="finances.php?delete_payment=<?= $p['id'] ?>">Delete</a></td></tr><?php endforeach; ?></tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
