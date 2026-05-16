<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: register.html');
    exit();
}

$id = trim($_POST['username'] ?? '');
$pw = $_POST['password'] ?? '';

if (strlen($id) < 3 || strlen($id) > 50 || strlen($pw) < 4) {
    header('Location: register.html?err=validation');
    exit();
}

require __DIR__ . '/includes/db.php';

$hash = password_hash($pw, PASSWORD_BCRYPT);

$stmt = $mysqli->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
$stmt->bind_param('ss', $id, $hash);

try {
    $stmt->execute();
} catch (mysqli_sql_exception $e) {
    $stmt->close();
    $mysqli->close();
    header($e->getCode() === 1062
        ? 'Location: register.html?err=dup'
        : 'Location: register.html?err=validation');
    exit();
}

$stmt->close();
$mysqli->close();

header('Location: register.html?ok=1');
exit();
