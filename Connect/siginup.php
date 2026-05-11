<?php
session_start();
require 'db.php'; // Your PDO connection

// Generate CSRF token if not present
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF token validity
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid request.";
    } else {
        // Sanitize and validate inputs
        $username = trim(filter_var($_POST['username'] ?? '', FILTER_SANITIZE_STRING));
        $email = trim(filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL));
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate inputs
        if (!$username) {
            $errors[] = "Username is required";
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = "Username must be between 3 and 50 characters";
        }

        if (!$email) {
            $errors[] = "A valid email is required";
        }

        if (!$password) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }

        // If no validation errors, check if user/email exists
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
            $stmt->execute(['username' => $username, 'email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Username or email already taken";
            } else {
                // Hash the password securely
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
                if ($stmt->execute(['username' => $username, 'email' => $email, 'password' => $password_hash])) {
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['username'] = $username;
                    // Regenerate session ID to prevent fixation
                    session_regenerate_id(true);
                    header('Location: index.php');
                    exit;
                } else {
                    $errors[] = "Failed to register. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Register</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background: #f0f4f8;
    padding: 20px;
  }
  form {
    background: white;
    max-width: 400px;
    margin: auto;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 8px rgba(0,0,0,0.1);
  }
  input {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 4px;
  }
  input:focus {
    border-color: #1a73e8;
    outline: none;
  }
  button {
    background: #1a73e8;
    color: white;
    border: none;
    padding: 12px;
    width: 100%;
    cursor: pointer;
    font-size: 16px;
    border-radius: 4px;
  }
  button:hover {
    background: #155ab6;
  }
  .errors {
    background: #fdd;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
    color: #900;
  }
  h2 {
    text-align: center;
    color: #1a73e8;
  }
  p {
    text-align: center;
  }
  a {
    color: #1a73e8;
    text-decoration: none;
  }
  a:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>
<h2>Create an Account</h2>

<?php if ($errors): ?>
  <div class="errors">
    <ul>
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="POST" action="">
  <input type="text" name="username" placeholder="Username" required minlength="3" maxlength="50" value="<?= htmlspecialchars($username) ?>" />
  <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($email) ?>" />
  <input type="password" name="password" placeholder="Password" required minlength="8" />
  <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="8" />
  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>" />
  <button type="submit">Register</button>
</form>
<p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>
