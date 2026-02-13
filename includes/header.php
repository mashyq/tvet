<?php
require_once __DIR__ . '/auth.php';
$currentUser = $_SESSION['user'] ?? null;
$currentPage = basename($_SERVER['PHP_SELF']);
$role = $currentUser['role'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>College ARMS</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">College ARMS</div>
        <nav>
            <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a>

            <?php if (in_array($role, ['admin', 'registrar'], true)): ?>
                <a class="<?= $currentPage === 'departments.php' ? 'active' : '' ?>" href="departments.php">Departments</a>
                <a class="<?= $currentPage === 'programmes.php' ? 'active' : '' ?>" href="programmes.php">Programmes</a>
                <a class="<?= $currentPage === 'students.php' ? 'active' : '' ?>" href="students.php">Students</a>
                <a class="<?= $currentPage === 'courses.php' ? 'active' : '' ?>" href="courses.php">Courses</a>
                <a class="<?= $currentPage === 'enrollments.php' ? 'active' : '' ?>" href="enrollments.php">Enrollments</a>
                <a class="<?= $currentPage === 'finances.php' ? 'active' : '' ?>" href="finances.php">Finances</a>
            <?php endif; ?>

            <?php if (in_array($role, ['admin', 'registrar', 'lecturer'], true)): ?>
                <a class="<?= $currentPage === 'grades.php' ? 'active' : '' ?>" href="grades.php">Grades</a>
                <a class="<?= $currentPage === 'transcript.php' ? 'active' : '' ?>" href="transcript.php">Transcript</a>
            <?php endif; ?>

            <?php if ($role === 'lecturer'): ?>
                <a class="<?= $currentPage === 'lecturer.php' ? 'active' : '' ?>" href="lecturer.php">Lecturer Desk</a>
            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
                <a class="<?= $currentPage === 'users.php' ? 'active' : '' ?>" href="users.php">Users</a>
            <?php endif; ?>
        </nav>
    </aside>

    <main class="content">
        <header class="topbar">
            <div>
                <h1>Academic Record Management System</h1>
                <p>Welcome, <?= htmlspecialchars($currentUser['full_name'] ?? 'Guest') ?> (<?= htmlspecialchars(strtoupper($role)) ?>)</p>
            </div>
            <?php if ($currentUser): ?>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            <?php endif; ?>
        </header>
