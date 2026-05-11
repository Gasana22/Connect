<?php
session_start();
require 'db.php'; // This should connect to your database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Login success: store user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Redirect to index
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>

<!-- Optional: Display error -->
<?php if (isset($error)): ?>
<script>
    alert("<?= $error ?>");
    window.location.href = 'login.php'; // redirect back to login form
</script>
<?php endif; ?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Login - Connect</title>
<style>
  /* Reset and base */
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

  /* Right login card */
  .login-card {
    flex: 1;
    padding: 40px 30px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .login-card h2 {
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

  input[type="email"],
  input[type="password"] {
    padding: 12px 15px;
    margin-bottom: 20px;
    border: 2px solid #0052cc;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s;
  }

  input[type="email"]:focus,
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

  /* Google login button */
  .google-login {
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 2px solid #0052cc;
    color: #0052cc;
    font-weight: 700;
    padding: 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.3s, color 0.3s;
    margin-bottom: 20px;
  }

  .google-login:hover {
    background: #0052cc;
    color: white;
  }

  .google-login img {
    height: 20px;
    margin-right: 10px;
  }

  /* Sign up link */
  .signup {
    text-align: center;
    font-size: 1rem;
    color: #0052cc;
  }

  .signup a {
    color: #003d99;
    font-weight: 700;
    text-decoration: none;
    margin-left: 5px;
  }

  .signup a:hover {
    text-decoration: underline;
  }
  .forgot-password {
  text-align: center;
  margin-top: 10px;
}

.forgot-password a {
  color: #0052cc;
  text-decoration: none;
  font-size: 0.9rem;
}

.forgot-password a:hover {
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
    .login-card {
      padding: 30px 20px;
    }
  }
</style>
</head>
<body>

<div class="container">
  <div class="intro-card">
    <h1>Welcome to Connect</h1>
    <p>Discover exclusive content from creators you love. Join the community and explore endless possibilities with your favorite creators.</p>
  </div>

  <div class="login-card">
    <h2>Log in to Your Account</h2>

    <button class="google-login" onclick="alert('Google login clicked')">
      <img src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg" alt="Google logo" />
      Log in with Google
    </button>

    <form action="login.php" method="POST" autocomplete="off">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" placeholder="you@example.com" required />

      <label for="password">Password</label>
      <input id="password" name="password" type="password" placeholder="••••••••" required />

      <button type="submit">Log In</button>
    </form>

    <div class="signup">
      Don't have an account? <a href="siginup.php">Sign Up</a>
    </div>
    <div class="forgot-password">
  <a href="forgot-password.php">Forgot password?</a>
</div>
  </div>
</div>
</body>
</html>
