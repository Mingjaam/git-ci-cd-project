<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return !empty($_SESSION['uid']) && !empty($_SESSION['uname']);
}

function requireLogin($redirect = 'login.html') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirect);
        exit();
    }
}
