<?php
session_start();
require 'db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Delete any existing tokens for this user
        $pdo->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?")->execute([$user['id']]);
        
        // Insert new token
        $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $token, $expires]);
        
        // Send email (in a real app, you would send an actual email)
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token;
        
        // For demo purposes, we'll just show the link
        $message = "Password reset link has been sent to your email. For demo: <a href='$resetLink'>$resetLink</a>";
    } else {
        $error = "No account found with that email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Forgot Password - Connect</title>
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

  input[type="email"] {
    padding: 12px 15px;
    margin-bottom: 20px;
    border: 2px solid #0052cc;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s;
  }

  input[type="email"]:focus {
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
    <h1>Reset Your Password</h1>
    <p>Enter your email address and we'll send you a link to reset your password.</p>
  </div>

  <div class="form-card">
    <h2>Forgot Password</h2>

    <?php if ($message): ?>
      <div class="message"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
      <div class="message error"><?= $error ?></div>
    <?php endif; ?>

    <form action="forgot-password.php" method="POST" autocomplete="off">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" placeholder="you@example.com" required />

      <button type="submit">Send Reset Link</button>
    </form>

    <div class="back-to-login">
      Remember your password? <a href="login.php">Log In</a>
    </div>
  </div>
</div>
</body>
</html>