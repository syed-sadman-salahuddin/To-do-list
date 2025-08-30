<?php
session_start();
require 'config.php';
require 'functions.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];


$user_id = isset($user['id']) ? intval($user['id']) : 0;
if (!$user_id) {
    echo "Unable to determine logged-in user.";
    exit();
}

$perPage = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

$countRes = $conn->query("SELECT COUNT(*) as c FROM posts WHERE user_id = $user_id");
$total = 0;
if ($countRes) {
    $r = $countRes->fetch_assoc();
    $total = intval($r['c']);
}
$totalPages = max(1, ceil($total / $perPage));

$offset = intval($offset);
$perPage = intval($perPage);
$sql = "SELECT posts.*, users.name AS author_name 
        FROM posts 
        LEFT JOIN users ON posts.user_id = users.id 
        WHERE posts.user_id = $user_id
        ORDER BY 
          CASE posts.priority
            WHEN 'High' THEN 1
            WHEN 'Medium' THEN 2
            WHEN 'Low' THEN 3
            ELSE 4
          END,
          posts.created_at DESC
        LIMIT $offset, $perPage";

$result = $conn->query($sql);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>To-do list</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    :root {
      --brand: #bfeccf;
      --brand-dark: #2d7a3a;
      --bg: #f7fff7;
    }
    body { font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; background: var(--bg); }

    .site-header {
      background: linear-gradient(180deg, #dff7e4, var(--brand));
      height: 96px;
      display: flex;
      align-items: center;
      box-shadow: 0 3px 10px rgba(0,0,0,0.06);
      border-bottom-left-radius: 10px;
      border-bottom-right-radius: 10px;
      margin-bottom: 32px;
    }
    .site-header .brand {
      font-weight: 700;
      font-size: 28px;
      letter-spacing: 0.4px;
      color: #08320f;
    }
    .site-header .right {
      margin-left: auto;
      display:flex;
      align-items:center;
      gap:12px;
      margin-right:20px;
      color:#064114;
    }
    .site-header .right .greet { font-weight:600; }
    .site-header .right .btn-logout {
      border: 1px solid rgba(0,0,0,0.12);
      background: white;
      color: #063914;
      font-weight:600;
    }

   
    .container-main { max-width: 1100px; margin: 0 auto; }

    h1.page-title { font-size: 44px; font-weight:700; margin-bottom: 18px; color:#0b3b18; }

    
    .post-card {
      transition: transform .18s ease, box-shadow .18s ease;
      transform-origin: center top;
      position: relative;
    }
    .post-card:hover {
      transform: scale(1.02);
      box-shadow: 0 18px 40px rgba(16, 60, 16, 0.12);
      z-index: 5;
    }
    .post-card .post-title { font-size: 24px; font-weight:700; text-decoration: underline; color:#08320f; }
    .post-card .meta { font-size: 13px; color: #6b6b6b; margin-bottom: 12px; }
    .post-card .excerpt { color:#222; line-height:1.6; font-size: 16px; }

    .priority-badge {
      display:inline-block;
      padding:6px 10px;
      border-radius:8px;
      font-weight:700;
      font-size:13px;
      margin-right:8px;
    }
    .priority-high { background:#ff6b6b; color:#fff; }
    .priority-medium { background:#f0c74f; color:#111; }
    .priority-low { background:#8ccf9c; color:#0b3b18; }

    .btn-brand { background: var(--brand-dark); color: #fff; border: none; }
    .btn-brand:hover { background: #245c2e; }

    .post-actions { position: absolute; right: 18px; top: 18px; }


    .pagination { gap:6px; }
    .pagination .page-item .page-link { border-radius:6px; }

    .posts-wrap { margin-top: 18px; }
    .deadline-text { font-size:14px; color:#4b4b4b; margin-top:8px; display:block; }
    .overdue { border: 2px solid #ff6b6b; }
  </style>
</head>
<body>

  <header class="site-header">
    <div class="container container-main d-flex align-items-center">
      <div class="brand">To-do list</div>
      <div class="right">
        <div class="greet">Hello, <?=htmlspecialchars($user['name'])?></div>
        <a href="logout.php" class="btn btn-sm btn-logout">Logout</a>
      </div>
    </div>
  </header>

  <main class="container container-main">
    <div class="d-flex align-items-center justify-content-between">
      <div>
        <h1 class="page-title">Task</h1>
        
      </div>
      <div>
        <a href="newpost.php" class="btn btn-success btn-lg">Add your works</a>
      </div>
    </div>

    <div class="posts-wrap mt-4">
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): 
          $isAuthor = isset($user['id']) && $user['id'] == $row['user_id'];

        
          $isOverdue = false;
          if (!empty($row['deadline']) && $row['deadline'] !== '0000-00-00 00:00:00') {
              $d = DateTime::createFromFormat('Y-m-d H:i:s', $row['deadline']);
              if ($d && $d < new DateTime()) {
                  $isOverdue = true;
              }
          }

        
          $priorityClass = 'priority-medium';
          if (isset($row['priority'])) {
              if ($row['priority'] === 'High') $priorityClass = 'priority-high';
              elseif ($row['priority'] === 'Low') $priorityClass = 'priority-low';
              else $priorityClass = 'priority-medium';
          }
        ?>
          <div class="card mb-4 post-card position-relative <?= $isOverdue ? 'overdue' : '' ?>">
            <div class="card-body">
              <?php if ($isAuthor): ?>
                <div class="post-actions">
                  <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#editModal<?=$row['id']?>">Edit</button>
                  <form method="POST" action="delete_post.php" style="display:inline;" onsubmit="return confirm('Delete this post?');">
                    <input type="hidden" name="post_id" value="<?=intval($row['id'])?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </div>
              <?php endif; ?>

              <h3 class="post-title"><a href="view.php?id=<?=$row['id']?>" class="text-dark"><?=htmlspecialchars($row['title'])?></a></h3>


              <div style="margin-bottom:10px;">
                <span class="priority-badge <?= $priorityClass ?>">
                  <?= htmlspecialchars($row['priority'] ?? 'Medium') ?>
                </span>

                <?php if (!empty($row['deadline']) && $row['deadline'] !== '0000-00-00 00:00:00'): 
                    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $row['deadline']);
                    $deadline_display = $dt ? $dt->format('M j, Y \a\t g:ia') : htmlspecialchars($row['deadline']);
                ?>
                  <small class="deadline-text" style="color: <?= $isOverdue ? '#b00000' : '#4b4b4b' ?>;">
                    <strong>Deadline</strong>: <?= $deadline_display ?> <?= $isOverdue ? '• (overdue)' : '' ?>
                  </small>
                <?php endif; ?>
              </div>
          
              <p class="excerpt"><?=nl2br(htmlspecialchars($row['content']))?></p>
              <div class="meta">By <?= $row['author_name'] ? htmlspecialchars($row['author_name']) : 'Unknown' ?> • Posted on <?= $row['created_at'] ?></div>
            </div>
          </div>

         
          <?php if ($isAuthor): ?>
          <div class="modal fade" id="editModal<?=$row['id']?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <form method="POST" action="edit_post.php">
                  <div class="modal-header" style="background:var(--brand);">
                    <h5 class="modal-title">Edit Post</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="post_id" value="<?=intval($row['id'])?>">
                    <div class="mb-3">
                      <label class="form-label">Title</label>
                      <input type="text" name="title" class="form-control" required value="<?=htmlspecialchars($row['title'])?>">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Content</label>
                      <textarea name="content" rows="8" class="form-control" required><?=htmlspecialchars($row['content'])?></textarea>
                    </div>

                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label">Deadline</label>
                        <?php
                          $deadline_val = '';
                          if (!empty($row['deadline']) && $row['deadline'] !== '0000-00-00 00:00:00') {
                              $dt = DateTime::createFromFormat('Y-m-d H:i:s', $row['deadline']);
                              if ($dt) $deadline_val = $dt->format('Y-m-d\TH:i');
                          }
                        ?>
                        <input type="datetime-local" name="deadline" class="form-control" value="<?=htmlspecialchars($deadline_val)?>">
                        <small class="form-text text-muted">Leave empty to remove deadline.</small>
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select" required>
                          <option value="High" <?= (isset($row['priority']) && $row['priority'] === 'High') ? 'selected' : '' ?>>High</option>
                          <option value="Medium" <?= (!isset($row['priority']) || $row['priority'] === 'Medium') ? 'selected' : '' ?>>Medium</option>
                          <option value="Low" <?= (isset($row['priority']) && $row['priority'] === 'Low') ? 'selected' : '' ?>>Low</option>
                        </select>
                      </div>
                    </div>

                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand">Save changes</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <?php endif; ?>

        <?php endwhile; ?>
      <?php else: ?>
        <div class="alert alert-info">No posts yet.</div>
      <?php endif; ?>
    </div>


    <nav aria-label="Posts pagination" class="mt-4">
      <ul class="pagination justify-content-center">
        <li class="page-item <?=($page<=1)?'disabled':''?>">
          <a class="page-link" href="?page=<?=max(1, $page-1)?>">Previous</a>
        </li>

        <?php
        $start = max(1, $page - 3);
        $end = min($totalPages, $page + 3);
        if ($start > 1) {
            echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
            if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        for ($p = $start; $p <= $end; $p++) {
            $active = $p == $page ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='?page=$p'>$p</a></li>";
        }
        if ($end < $totalPages) {
            if ($end < $totalPages-1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            echo "<li class='page-item'><a class='page-link' href='?page=$totalPages'>$totalPages</a></li>";
        }
        ?>

        <li class="page-item <?=($page>=$totalPages)?'disabled':''?>">
          <a class="page-link" href="?page=<?=min($totalPages, $page+1)?>">Next</a>
        </li>
      </ul>
    </nav>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
   
      if (window.history && history.pushState) {
        history.pushState(null, null, window.location.href);
        window.addEventListener('popstate', function (event) {

          history.pushState(null, null, window.location.href);
        });
      }
  </script>

  <?php showFlashModal(); ?>
</body>
</html>
