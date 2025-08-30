<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
require 'config.php';
require 'functions.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        setFlash('error', 'Please fill in both email and password.');
        header('Location: login.php');
        exit;
    }

    $stmt = $conn->prepare("SELECT id,name,username,email,password_hash FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($user = $res->fetch_assoc()) {
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'username' => $user['username'],
                'email' => $user['email']
            ];
            setFlash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');
  
            echo "<script>location.replace('index.php');</script>";
            exit;
        } else {
            setFlash('error', 'Incorrect password.');
            header('Location: login.php');
            exit;
        }
    } else {
        setFlash('error', 'No account found with that email.');
        header('Location: login.php');
        exit;
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>To-do List</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{
      --brand:#bfeccf;
      --brand-dark:#2d7a3a;
      --card-radius:14px;
    }
    html,body { height:100%; }
    body {
      margin:0;
      font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: linear-gradient(180deg, #dff7e4, #bfeccf);
      display:flex;
      align-items:center;
      justify-content:center;
      padding:24px;
    }

    .login-wrapper {
      width:100%;
      max-width:460px;
      display:flex;
      flex-direction:column;
      align-items:center;
      gap:18px;
    }

    .login-card {
      width:100%;
      background:#fff;
      border-radius:var(--card-radius);
      padding:28px;
      box-shadow: 0 12px 30px rgba(9,30,9,0.06);
      text-align:center;
    }

    .login-card h3 {
      margin:0 0 12px 0;
      color:var(--brand-dark);
      font-weight:700;
      font-size:1.6rem;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:10px;
    }

    .input-group-icon {
      position:relative;
    }
    .input-icon {
      position:absolute;
      left:12px;
      top:50%;
      transform:translateY(-50%);
      color:var(--brand-dark);
      font-size:1.05rem;
      opacity:0.95;
      pointer-events:none;
    }
    .form-control {
      padding-left:42px;
      height:44px;
      border-radius:8px;
    }

    .btn-brand {
      background: var(--brand-dark);
      color:white;
      border:none;
      height:44px;
      border-radius:8px;
      font-weight:600;
      display:inline-flex;
      gap:8px;
      align-items:center;
      justify-content:center;
    }
    .btn-brand:hover { background:#234d29; color:white; }

    .helper-links {
      display:flex;
      flex-direction:column; 
      align-items:center;
      gap:6px;
      margin-top:6px;
    }
    .helper-links a { color:var(--brand-dark); text-decoration:none; font-weight:600; }
    .helper-links a:hover { text-decoration:underline; }

  
    @media (max-width:480px) {
      .login-card { padding:18px; }
      .form-control { height:40px; }
    }
  </style>
</head>
<body>
    
  <div class="login-wrapper">
    <h2 style="text-align:center; font-weight:700; color:#2d7a3a; margin-bottom:15px;">
    My To-do List
  </h2>
    <div class="login-card">
      <h3><i class="bi bi-box-arrow-in-right"></i> Log in</h3>

      <form method="POST" id="loginForm" autocomplete="off" novalidate>
        <div class="mb-3 input-group-icon">
          <i class="bi bi-envelope input-icon"></i>
          <input name="email" type="email" class="form-control" placeholder="Email" required autocomplete="username">
        </div>

        <div class="mb-3 input-group-icon">
          <i class="bi bi-lock input-icon"></i>
          <input name="password" type="password" class="form-control" placeholder="Password" required autocomplete="current-password">
        </div>

        <button class="btn btn-brand w-100 mb-2" type="submit"><i class="bi bi-box-arrow-in-right"></i> Login</button>
      </form>

      <div class="helper-links">
        <a href="register.php"><i class="bi bi-person-plus"></i> Create an account</a>
        <a href="#" data-bs-toggle="modal" data-bs-target="#forgotModal"><i class="bi bi-key"></i> Forgot Password?</a>
      </div>
    </div>
  </div>

  
  <div class="modal fade" id="forgotModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="forgot_password.php">
          <div class="modal-header" style="background:var(--brand);">
            <h5 class="modal-title"><i class="bi bi-key"></i> Reset Password</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3 input-group-icon">
              <i class="bi bi-envelope input-icon"></i>
              <input type="email" name="email" class="form-control" placeholder="Registered email" required>
            </div>
            <div class="mb-3 input-group-icon">
              <i class="bi bi-person input-icon"></i>
              <input type="text" name="name" class="form-control" placeholder="Full name (as registered)" required>
            </div>
            <div class="mb-3 input-group-icon">
              <i class="bi bi-calendar input-icon"></i>
              <input type="date" name="dob" class="form-control" placeholder="Date of birth" required>
            </div>
            <div class="mb-3 input-group-icon">
              <i class="bi bi-lock input-icon"></i>
              <input type="password" name="new_password" class="form-control" placeholder="New password" required>
            </div>
            <div class="form-text">Password must be at least 6 characters and contain letters & numbers.</div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-brand" type="submit"><i class="bi bi-check-circle"></i> Update Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php showFlashModal(); ?>
</body>
</html>
