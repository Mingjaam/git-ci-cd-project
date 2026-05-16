<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: login.html');
    exit();
}

$id = trim($_POST['username'] ?? '');
$pw = $_POST['password'] ?? '';

if ($id == '' || $pw == '') {
    header('Location: login.html?err=1');
    exit();
}

require __DIR__ . '/includes/db.php';

$stmt = $mysqli->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$mysqli->close();

if (!$user || !password_verify($pw, $user['password_hash'])) {
    header('Location: login.html?err=1');
    exit();
}

session_regenerate_id(true);
$_SESSION['uid']   = (int)$user['id'];
$_SESSION['uname'] = $user['username'];

header('Location: admin_users.php');
exit();
