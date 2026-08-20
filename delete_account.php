<?php
require_once "auth.php"; // Ensures user is logged in

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $confirm_password = $_POST["password"] ?? "";
    $user_id = $_SESSION["user_id"];

    // Retrieve user password from DB
    $stmt = $conn->prepare("SELECT password FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Verify password before deleting
    // Note: If account was created via Google OAuth (empty password), allow deletion without password check
    $is_password_valid = empty($user["password"]) || password_verify($confirm_password, $user["password"]);

    if ($is_password_valid) {
        // Delete user record from database
        $del = $conn->prepare("DELETE FROM users WHERE id=?");
        $del->bind_param("i", $user_id);
        
        if ($del->execute()) {
            // Clear session and logout
            session_unset();
            session_destroy();

            // Redirect to login with confirmation message
            header("Location: login.php?msg=" . urlencode("Your account has been permanently deleted."));
            exit;
        } else {
            $error = "An error occurred while deleting your account. Please try again.";
        }
    } else {
        $error = "Incorrect password. Account deletion canceled.";
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Delete Account - Property Management System</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .delete-card {
      max-width: 450px;
      margin: 60px auto;
      padding: 30px;
      background: #ffffff;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      border-top: 4px solid #dc3545;
    }
    .password-wrapper { position: relative; width: 100%; display: block; margin-bottom: 15px; }
    .password-wrapper input { width: 100%; padding-right: 40px; box-sizing: border-box; }
    .toggle-password {
      position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; font-size: 16px; color: #666;
    }
    .btn-danger {
      background-color: #dc3545; color: white; border: none; padding: 10px 15px;
      border-radius: 4px; font-weight: bold; cursor: pointer; width: 100%;
    }
    .btn-danger:hover { background-color: #c82333; }
    .btn-cancel {
      display: block; text-align: center; margin-top: 15px; color: #6c757d; text-decoration: none;
    }
    .btn-cancel:hover { text-decoration: underline; }
  </style>
</head>
<body class="login-body">


<main class="container">
  <div class="delete-card">
    <h2 style="color: #dc3545; margin-top: 0;">⚠️ Delete Account</h2>
    <p>Are you sure you want to permanently delete your account (<b><?=htmlspecialchars($_SESSION["username"])?></b>)?</p>
    <p style="color: #6c757d; font-size: 13px;">This action <strong>cannot be undone</strong>. Your credentials will be removed from the system.</p>

    <?php if($error): ?>
      <div class="alert danger" style="background:#f8d7da; color:#721c24; padding:10px; margin-bottom:15px; border-radius:4px;"><?=htmlspecialchars($error)?></div>
    <?php endif; ?>

    <form method="post" onsubmit="return confirm('Are you absolutely sure you want to delete your account?');">
      <label>Confirm Your Password</label>
      <div class="password-wrapper">
        <input type="password" id="password" name="password" placeholder="Enter your current password">
        <button type="button" class="toggle-password" onclick="togglePass('password', this)" title="Show/Hide Password">👁️</button>
      </div>

      <button type="submit" class="btn-danger">Permanently Delete Account</button>
    </form>

    <a href="index.php" class="btn-cancel">Cancel and return to Dashboard</a>
  </div>
</main>

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
</body>
</html>