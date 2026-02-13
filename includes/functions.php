<?php
function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $message;
}

function score_to_grade(float $score): array
{
    if ($score >= 70) return ['A', 5.0, 'Excellent'];
    if ($score >= 60) return ['B', 4.0, 'Very Good'];
    if ($score >= 50) return ['C', 3.0, 'Good'];
    if ($score >= 45) return ['D', 2.0, 'Fair'];
    if ($score >= 40) return ['E', 1.0, 'Pass'];
    return ['F', 0.0, 'Fail'];
}

function recalc_fee_status(PDO $pdo, int $feeId): void
{
    $stmt = $pdo->prepare('SELECT amount FROM student_fees WHERE id = ?');
    $stmt->execute([$feeId]);
    $fee = $stmt->fetch();
    if (!$fee) {
        return;
    }

    $payStmt = $pdo->prepare('SELECT COALESCE(SUM(amount_paid),0) AS paid FROM fee_payments WHERE student_fee_id = ?');
    $payStmt->execute([$feeId]);
    $paid = (float) $payStmt->fetchColumn();
    $amount = (float) $fee['amount'];

    $status = 'Unpaid';
    if ($paid > 0 && $paid < $amount) {
        $status = 'Part Paid';
    } elseif ($paid >= $amount) {
        $status = 'Paid';
    }

    $update = $pdo->prepare('UPDATE student_fees SET status = ? WHERE id = ?');
    $update->execute([$status, $feeId]);
}
?>
