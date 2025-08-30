<?php
session_start();
require 'config.php';
require 'functions.php';

if (!isset($_SESSION['user'])) {
    setFlash('error', 'Please login to create a post.');
    header('Location: login.php');
    exit;
}
$user = $_SESSION['user'];
$user_id = intval($user['id'] ?? 0);
if ($user_id <= 0) {
    setFlash('error', 'Unable to determine user.');
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $priority = $_POST['priority'] ?? 'Medium';
    $deadline_raw = trim($_POST['deadline'] ?? '');

    
    $allowed_priorities = ['Low', 'Medium', 'High'];
    if (!in_array($priority, $allowed_priorities, true)) {
        $priority = 'Medium';
    }

    $wordCount = str_word_count($content);

    if (!$title || !$content) {
        setFlash('error', 'Please fill title and content.');
        header('Location: newpost.php');
        exit;
    }
    if ($wordCount > 100) {
        setFlash('error', 'Post exceeds the 100-word limit.');
        header('Location: newpost.php');
        exit;
    }

    
    $deadline = null;
    if ($deadline_raw !== '') {
        $deadline_normalized = str_replace('T', ' ', $deadline_raw);
        $ts = strtotime($deadline_normalized);
        if ($ts === false) {
            setFlash('error', 'Invalid deadline format.');
            header('Location: newpost.php');
            exit;
        }
        
        if ($ts < time()) {
            setFlash('error', 'Deadline must be a future date.');
            header('Location: newpost.php');
            exit;
        }
        $deadline = date('Y-m-d H:i:s', $ts);
    }

    $created_at = date('Y-m-d H:i:s');

    if ($deadline !== null) {
        $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content, created_at, deadline, priority) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            setFlash('error', 'Database error: ' . $conn->error);
            header('Location: newpost.php');
            exit;
        }
        $stmt->bind_param("isssss", $user_id, $title, $content, $created_at, $deadline, $priority);
    } else {
        $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content, created_at, priority) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            setFlash('error', 'Database error: ' . $conn->error);
            header('Location: newpost.php');
            exit;
        }
        $stmt->bind_param("issss", $user_id, $title, $content, $created_at, $priority);
    }

    if ($stmt->execute()) {
        $stmt->close();
        setFlash('success', 'Post published successfully.');
        header('Location: index.php');
        exit;
    } else {
        $err = $stmt->error ?: $conn->error;
        $stmt->close();
        setFlash('error', 'Failed to publish post. ' . $err);
        header('Location: newpost.php');
        exit;
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Create New Post</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    :root { --brand:#bfeccf; }
    body { background:#f7fff7; font-family: 'Segoe UI', sans-serif; }
    .card { border-radius: 15px; border: none; }
    h2 { font-weight: 700; color: #2d6a4f; }
    .note { font-size: 0.9rem; color: #555; margin-bottom: 1rem; }
    .btn-success { font-weight: 600; padding: 10px 30px; border-radius: 8px; }
    .field-label { font-weight:600; color:#2d6a4f; margin-bottom:6px; display:block; }
  </style>
</head>
<body>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <a href="index.php" class="btn btn-secondary mb-3">← Back</a>
      <div class="card shadow p-4">
        <h2 class="mb-2 text-center">Create New Post</h2>
        <p class="note text-center"><i class="bi bi-pencil-square"></i> Word limit: 100 words</p>

        <form method="POST" id="postForm" class="text-start">
          <div class="mb-3">
            <label class="field-label">Title</label>
            <input name="title" class="form-control" placeholder="Post title" required>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="field-label">Deadline (optional)</label>
              <input type="datetime-local" name="deadline" class="form-control"
                     min="<?= date('Y-m-d\TH:i') ?>" />
              <small class="text-muted">Must be a future date, or leave empty.</small>
            </div>

            <div class="col-md-6">
              <label class="field-label">Priority</label>
              <select name="priority" class="form-select" required>
                <option value="Medium" selected>Medium</option>
                <option value="High">High</option>
                <option value="Low">Low</option>
              </select>
            </div>
          </div>

          <div class="mb-3 mt-3">
            <label class="field-label">Content</label>
            <textarea name="content" id="content" rows="8" class="form-control" placeholder="Write here..." required></textarea>
          </div>

          <div class="text-center">
            <button type="submit" class="btn btn-success">
              <i class="bi bi-upload"></i> Publish
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="wordLimitModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Word Limit Exceeded</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Your post must not exceed <strong>100 words</strong>. Please shorten your content.</p>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('postForm').addEventListener('submit', function(e){
  const content = document.getElementById('content').value.trim();
  const words = content.split(/\s+/).filter(Boolean).length;

  if(words > 100){
    e.preventDefault();
    const modal = new bootstrap.Modal(document.getElementById('wordLimitModal'));
    modal.show();
  }
});
</script>

<?php showFlashModal(); ?>
</body>
</html>
