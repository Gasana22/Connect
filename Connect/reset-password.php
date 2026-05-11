<?php
session_start();
require 'db.php';

$error = '';
$success = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check if token exists and is valid
    $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData) {
        $error = "Invalid or expired token. Please request a new password reset link.";
    }
} else {
    $error = "No token provided.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && isset($_POST['token'])) {
    $token = $_POST['token'];
    $password = $_POST['password'];
    
    // Verify token again
    $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tokenData) {
        // Update password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashedPassword, $tokenData['user_id']]);
        
        // Mark token as used
        $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?")->execute([$token]);
        
        $success = "Your password has been updated successfully! You can now <a href='login.php'>log in</a> with your new password.";
    } else {
        $error = "Invalid or expired token. Please request a new password reset link.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Reset Password - Connect</title>
<style>
  /* Reusing your existing styles with some modifications */
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  body, html {
    height: 100%;
    background: #e6f0ff;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .container {
    display: flex;
    max-width: 900px;
    width: 90%;
    background: white;
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    border-radius: 10px;
    overflow: hidden;
  }

  /* Left intro card */
  .intro-card {
    background: #0052cc;
    color: white;
    flex: 1;
    padding: 40px 30px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .intro-card h1 {
    font-size: 2.5rem;
    margin-bottom: 20px;
  }

  .intro-card p {
    font-size: 1.1rem;
    line-height: 1.5;
    opacity: 0.85;
  }

  /* Right form card */
  .form-card {
    flex: 1;
    padding: 40px 30px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .form-card h2 {
    margin-bottom: 25px;
    color: #0052cc;
    font-weight: 700;
    font-size: 1.8rem;
  }

  form {
    display: flex;
    flex-direction: column;
  }

  label {
    margin-bottom: 5px;
    font-weight: 600;
    color: #003d99;
  }

  input[type="password"] {
    padding: 12px 15px;
    margin-bottom: 20px;
    border: 2px solid #0052cc;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s;
  }

  input[type="password"]:focus {
    border-color: #003d99;
    outline: none;
  }

  button[type="submit"] {
    background: #0052cc;
    color: white;
    font-weight: 700;
    padding: 14px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1.1rem;
    transition: background 0.3s;
    margin-bottom: 15px;
  }

  button[type="submit"]:hover {
    background: #003d99;
  }

  .message {
    margin-top: 20px;
    padding: 10px;
    border-radius: 5px;
    background: #e6f7ff;
    color: #0052cc;
  }

  .error {
    background: #ffebee;
    color: #c62828;
  }

  .success {
    background: #e8f5e9;
    color: #2e7d32;
  }

  .back-to-login {
    text-align: center;
    font-size: 1rem;
    color: #0052cc;
    margin-top: 20px;
  }

  .back-to-login a {
    color: #003d99;
    font-weight: 700;
    text-decoration: none;
    margin-left: 5px;
  }

  .back-to-login a:hover {
    text-decoration: underline;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .container {
      flex-direction: column;
      max-width: 400px;
      border-radius: 10px;
    }
    .intro-card {
      padding: 30px 20px;
      text-align: center;
    }
    .form-card {
      padding: 30px 20px;
    }
  }
</style>
</head>
<body>

<div class="container">
  <div class="intro-card">
    <h1>Set a New Password</h1>
    <p>Choose a strong password that you haven't used before.</p>
  </div>

  <div class="form-card">
    <h2>Reset Password</h2>

    <?php if ($error): ?>
      <div class="message error"><?= $error ?></div>
      <div class="back-to-login">
        <a href="forgot-password.php">Request a new reset link</a>
      </div>
    <?php elseif ($success): ?>
      <div class="message success"><?= $success ?></div>
    <?php else: ?>
      <form action="reset-password.php" method="POST" autocomplete="off">
        <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">
        
        <label for="password">New Password</label>
        <input id="password" name="password" type="password" placeholder="••••••••" required minlength="8" />
        
        <label for="confirm_password">Confirm Password</label>
        <input id="confirm_password" name="confirm_password" type="password" placeholder="••••••••" required minlength="8" />

        <button type="submit">Reset Password</button>
      </form>
    <?php endif; ?>

    <div class="back-to-login">
      Remember your password? <a href="login.php">Log In</a>
    </div>
  </div>
</div>

<script>
  // Simple password confirmation check
  document.querySelector('form')?.addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
      e.preventDefault();
      alert('Passwords do not match!');
    }
  });
</script>
</body>
</html>