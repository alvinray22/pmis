<?php
require_once "config.php";
if (isset($_SESSION["user_id"])) { header("Location: index.php"); exit; }

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
        $chk->bind_param("ss", $username, $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = "Username or Email already taken.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hash);
            
            if ($stmt->execute()) {
                header("Location: login.php?msg=Registration successful! Please log in.");
                exit;
            } else {
                $error = "Error creating account. Please try again.";
            }
        }
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Register - Property Management</title>
<link rel="stylesheet" href="assets/style.css">
<style>
.password-wrapper { position: relative; width: 100%; display: block; }
.password-wrapper input { width: 100%; padding-right: 40px; box-sizing: border-box; }
.toggle-password {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer; font-size: 16px; color: #666;
  padding: 0; margin: 0; line-height: 1; outline: none;
}
</style>
</head><body class="login-body">
<div class="login-card">
  <h1>Create Account</h1>
  <p class="muted">Property Management Information System</p>

  <?php if($error): ?><div class="alert danger" style="background:#f8d7da; color:#721c24; padding:10px; margin-bottom:15px; border-radius:4px;"><?=htmlspecialchars($error)?></div><?php endif; ?>

  <form method="post">
    <label>Username</label>
    <input name="username" required autofocus>
    
    <label>Email Address</label>
    <input type="email" name="email" required>
    
    <label>Password</label>
    <div class="password-wrapper">
      <input type="password" id="reg_password" name="password" required>
      <button type="button" class="toggle-password" onclick="togglePass('reg_password', this)" title="Show/Hide Password">👁️</button>
    </div>
    
    <label>Confirm Password</label>
    <div class="password-wrapper">
      <input type="password" id="confirm_password" name="confirm_password" required>
      <button type="button" class="toggle-password" onclick="togglePass('confirm_password', this)" title="Show/Hide Password">👁️</button>
    </div>

    <button class="btn primary full" style="margin-top:15px;">Register</button>
  </form>

  <div style="text-align: center; margin-top: 15px; font-size: 14px;">
    Already have an account? <a href="login.php">Login here</a>
  </div>
</div>

<script>
function togglePass(inputId, btn) {
  const input = document.getElementById(inputId);
  if (input.type === "password") {
    input.type = "text";
    btn.textContent = "🙈";
  } else {
    input.type = "password";
    btn.textContent = "👁️";
  }
}
</script>
</body></html>