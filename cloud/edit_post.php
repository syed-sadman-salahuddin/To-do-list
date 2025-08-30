<?php
session_start();
require 'config.php';
require 'functions.php';

if (!isset($_SESSION['user'])) {
    setFlash('error', 'Please login first.');
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$post_id = intval($_POST['post_id'] ?? 0);
$title   = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$priority = $_POST['priority'] ?? 'Medium';
$deadline_raw = trim($_POST['deadline'] ?? '');

if (!$post_id || !$title || !$content) {
    setFlash('error', 'All fields are required.');
    header('Location: index.php');
    exit;
}


$wordCount = str_word_count(strip_tags($content));
if ($wordCount > 100) {
    setFlash('error', "Your post has $wordCount words. Limit is 100.");
    header("Location: index.php");
    exit;
}


$allowed_priorities = ['Low', 'Medium', 'High'];
if (!in_array($priority, $allowed_priorities, true)) {
    $priority = 'Medium';
}


$deadline = null;
if ($deadline_raw !== '') {
    $deadline_normalized = str_replace('T', ' ', $deadline_raw);
    $ts = strtotime($deadline_normalized);
    if ($ts === false) {
        setFlash('error', 'Invalid deadline format.');
        header('Location: index.php');
        exit;
    }
    $deadline = date('Y-m-d H:i:s', $ts);
}


$stmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$res = $stmt->get_result();
$found = $res->fetch_assoc();
$stmt->close();

if (!$found) {
    setFlash('error', 'Post not found.');
    header('Location: index.php');
    exit;
}

if ($found['user_id'] != $user['id']) {
    setFlash('error', 'You are not allowed to edit this post.');
    header('Location: index.php');
    exit;
}


if ($deadline !== null) {
    $upd = $conn->prepare("UPDATE posts SET title = ?, content = ?, priority = ?, deadline = ? WHERE id = ? AND user_id = ?");
    $upd->bind_param("ssssii", $title, $content, $priority, $deadline, $post_id, $user['id']);
} else {
    $upd = $conn->prepare("UPDATE posts SET title = ?, content = ?, priority = ?, deadline = NULL WHERE id = ? AND user_id = ?");
    $upd->bind_param("sssii", $title, $content, $priority, $post_id, $user['id']);
}

if ($upd->execute()) {
    setFlash('success', 'Post updated successfully.');
} else {
    setFlash('error', 'Failed to update post.');
}
$upd->close();

header('Location: index.php');
exit;
