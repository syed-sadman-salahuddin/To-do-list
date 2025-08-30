<?php
session_start();
if (isset($_SESSION['user'])) {
  echo "<script>location.replace('index.php');</script>";
  exit;
}
require 'config.php';
require 'functions.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);

    $errors = [];
    if (!$name || !$username || !$email || !$gender || !$dob || !$password || !$confirm) {
        $errors[] = "All fields are required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (strlen($password) < 6 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must be at least 6 characters and contain letters and numbers.";
    }
    if ($password !== $confirm) {
        $errors[] = "Password and Confirm Password do not match.";
    }
    if (strtotime($dob) === false || strtotime($dob) > time()) {
        $errors[] = "Invalid Date of Birth or DOB is in the future.";
    }
    if (!$terms) {
        $errors[] = "You must accept Terms and Conditions.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email or Username already taken.";
        }
        $stmt->close();
    }

    if (!empty($errors)) {
        setFlash('error', implode("<br>", $errors));
        header("Location: register.php");
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $conn->prepare("INSERT INTO users (name, username, email, gender, dob, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
    $ins->bind_param("ssssss", $name, $username, $email, $gender, $dob, $hash);

    if ($ins->execute()) {
        $_SESSION['user'] = ['id'=>$ins->insert_id, 'name'=>$name, 'username'=>$username, 'email'=>$email];
        setFlash('success', 'Registration successful. Welcome, ' . htmlspecialchars($name) . '!');
        header("Location: index.php");
        exit;
    } else {
        setFlash('error', 'Registration failed. Please try again.');
        header("Location: register.php");
        exit;
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Register - To-do list</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root{ --brand-dark:#2d7a3a; --card-radius:14px; }
    body {
      margin:0; min-height:100vh;
      font-family: "Inter", sans-serif;
      background: linear-gradient(180deg, #dff7e4, #bfeccf);
      display:flex; align-items:center; justify-content:center; padding:24px;
    }
    .card-register {
      width:100%; max-width:820px;
      border-radius:var(--card-radius);
      padding:22px;
      box-shadow: 0 14px 40px rgba(9,30,9,0.06);
      background:#fff;
      text-align:center;
    }
    .card-register h2 { 
      color:var(--brand-dark); 
      font-weight:700; 
      font-size:1.6rem; 
      display:flex; 
      justify-content:center;
      align-items:center; 
      gap:10px; 
    }
    .card-register p.helper { 
      margin-bottom:20px; 
      color:#555; 
    }
    .input-icon { 
      position:absolute; 
      left:12px; 
      top:50%; 
      transform:translateY(-50%); 
      color:var(--brand-dark); 
      font-size:1.05rem; 
      pointer-events:none; 
    }
    .input-group-icon { position:relative; }
    .form-control, .form-select { 
      padding-left:42px; 
      height:44px; 
      border-radius:8px; 
    }
    .btn-brand { 
      background:var(--brand-dark); 
      color:#fff; 
      border:none; 
      border-radius:8px; 
      font-weight:600; 
      height:44px; 
    }
    .btn-brand:hover { background:#234d29; }
    .form-check-label a { cursor:pointer; color:var(--brand-dark); font-weight:600; }
    .form-check-label a:hover { text-decoration:underline; }
  </style>
</head>
<body>
  <div class="card-register">
    <h2><i class="bi bi-person-plus"></i> Create an account</h2>
    <p class="helper">Fill in the details to create your account.</p>

    <form method="POST" novalidate>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Full name</label>
          <div class="input-group-icon">
            <i class="bi bi-person input-icon"></i>
            <input name="name" class="form-control" required placeholder="Your full name">
          </div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Username</label>
          <div class="input-group-icon">
            <i class="bi bi-at input-icon"></i>
            <input name="username" class="form-control" required placeholder="Choose a username">
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Email</label>
        <div class="input-group-icon">
          <i class="bi bi-envelope input-icon"></i>
          <input name="email" type="email" class="form-control" required placeholder="you@example.com">
        </div>
      </div>

      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">Gender</label>
          <div class="input-group-icon">
            <i class="bi bi-gender-ambiguous input-icon"></i>
            <select name="gender" class="form-select" required>
              <option value="">Select</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
        <div class="col-md-8 mb-3">
          <label class="form-label">Date of Birth</label>
          <div class="input-group-icon">
            <i class="bi bi-calendar input-icon"></i>
            <input name="dob" type="date" class="form-control" required max="<?=date('Y-m-d')?>">
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Password</label>
          <div class="input-group-icon">
            <i class="bi bi-lock input-icon"></i>
            <input name="password" type="password" class="form-control" required placeholder="Min 6 chars, letters & numbers">
          </div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Confirm Password</label>
          <div class="input-group-icon">
            <i class="bi bi-lock-fill input-icon"></i>
            <input name="confirm_password" type="password" class="form-control" required placeholder="Re-type password">
          </div>
        </div>
      </div>

      <div class="mb-3 form-check text-start">
        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
        <label class="form-check-label" for="terms">
          I accept the <a data-bs-toggle="modal" data-bs-target="#termsModal">Terms & Conditions</a>
        </label>
      </div>

      <button type="submit" class="btn btn-brand w-100 mb-2">Register</button>
      <div class="text-center"><a href="login.php">Already have an account? Log in</a></div>
    </form>
  </div>


  <div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header" style="background:#bfeccf;">
          <h5 class="modal-title"><i class="bi bi-file-text"></i> Terms & Conditions</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Here you can put your website's terms and conditions. For example:</p>
          <ul>
            <li>Respectful use of this site is required.</li>
            <li>Do not share your login credentials.</li>
            <li>Content must comply with our guidelines.</li>
          </ul>
          <p>By creating an account, you agree to follow these terms.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php showFlashModal(); ?>
</body>
</html>
