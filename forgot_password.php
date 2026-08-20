<?php
require_once "config.php";
if (isset($_SESSION["user_id"])) { header("Location: index.php"); exit; }

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identifier = trim($_POST["identifier"] ?? "");
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            $newHash = password_hash($new_password, PASSWORD_DEFAULT);
            $up = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $up->bind_param("si", $newHash, $user["id"]);
            $up->execute();

            header("Location: login.php?msg=Password reset successful! You can now log in.");
            exit;
        } else {
            $error = "No user found with that Username or Email.";
        }
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Forgot Password - Property Management</title>
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
  <h1>Reset Password</h1>
  <p class="muted">Property Management Information System</p>

  <?php if($error): ?><div class="alert danger" style="background:#f8d7da; color:#721c24; padding:10px; margin-bottom:15px; border-radius:4px;"><?=htmlspecialchars($error)?></div><?php endif; ?>

  <form method="post">
    <label>Username or Email Address</label>
    <input name="identifier" required autofocus placeholder="Enter your username or email">
    
    <label>New Password</label>
    <div class="password-wrapper">
      <input type="password" id="new_password" name="new_password" required>
      <button type="button" class="toggle-password" onclick="togglePass('new_password', this)" title="Show/Hide Password">👁️</button>
    </div>
    
    <label>Confirm New Password</label>
    <div class="password-wrapper">
      <input type="password" id="reset_confirm_password" name="confirm_password" required>
      <button type="button" class="toggle-password" onclick="togglePass('reset_confirm_password', this)" title="Show/Hide Password">👁️</button>
    </div>

    <button class="btn primary full" style="margin-top:15px;">Reset Password</button>
  </form>

  <div style="text-align: center; margin-top: 15px; font-size: 14px;">
    Remembered your password? <a href="login.php">Back to Login</a>
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