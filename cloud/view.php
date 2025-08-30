<?php
require 'config.php';
require 'functions.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT posts.*, users.name AS author_name FROM posts LEFT JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
if (!$post) {
    setFlash('error', 'Post not found.');
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?=htmlspecialchars($post['title'])?> - My Blog</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>:root{--brand:#bfeccf;} body{background:#f7fff7;}</style>
</head>
<body>
<div class="container py-4">
  <a href="index.php" class="btn btn-secondary mb-3">← Back</a>
  <div class="card p-4 shadow">
    <h2><?=htmlspecialchars($post['title'])?></h2>
    <p class="text-muted">By <?= $post['author_name'] ? htmlspecialchars($post['author_name']) : 'Unknown' ?> • <?=$post['created_at']?></p>
    <div><?=nl2br(htmlspecialchars($post['content']))?></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php showFlashModal(); ?>
</body>
</html>
