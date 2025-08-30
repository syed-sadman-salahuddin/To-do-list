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
if (!$post_id) {
    setFlash('error', 'Invalid post id.');
    header('Location: index.php');
    exit;
}


$stmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    setFlash('error', 'Post not found.');
    header('Location: index.php');
    exit;
}
if ($row['user_id'] != $user['id']) {
    setFlash('error', 'You are not allowed to delete this post.');
    header('Location: index.php');
    exit;
}


$del = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
$del->bind_param("ii", $post_id, $user['id']);
if ($del->execute()) {
    setFlash('success', 'Post deleted.');
} else {
    setFlash('error', 'Failed to delete post.');
}
$del->close();

header('Location: index.php');
exit;
