<?php
session_start();
require 'config.php';
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if (!$email || !$name || !$dob || !$new_password) {
        setFlash('error', 'All fields are required.');
        header("Location: login.php");
        exit;
    }


    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND name = ? AND dob = ?");
    $stmt->bind_param("sss", $email, $name, $dob);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($user = $res->fetch_assoc()) {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $upd->bind_param("si", $hash, $user['id']);
        if ($upd->execute()) {
            setFlash('success', 'Password updated. Please login.');
        } else {
            setFlash('error', 'Password reset failed.');
        }
        $upd->close();
    } else {
        setFlash('error', 'No matching account found. Check details.');
    }
    header("Location: login.php");
    exit;
}
