<?php
require 'db.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $bio = trim($_POST['bio']);

    // Username validation
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $message = "Username must be 3-20 characters and can only contain letters, numbers, and underscores.";
    } else {
        // Update username and bio
        $stmt = $pdo->prepare("UPDATE users SET username = ?, bio = ? WHERE id = ?");
        $stmt->execute([$username, $bio, $user_id]);

        $_SESSION['username'] = $username;
        $message = "Profile updated successfully!";
    }

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $valid_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array(strtolower($ext), $valid_exts)) {
            $targetPath = "assets/profile_pics/{$user_id}.jpg";
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath);
        } else {
            $message .= " Invalid file type for profile picture.";
        }
    }
}

// Fetch current profile data
$stmt = $pdo->prepare("SELECT username, bio FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

function getProfilePicture($user_id) {
    $path = "assets/profile_pics/" . $user_id . ".jpg";
    return file_exists($path) ? $path : "assets/profile_pics/default.png";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .edit-container {
            max-width: 500px;
            margin: 50px auto;
            background: #f9f9f9;
            padding: 25px;
            border-radius: 10px;
        }

        .edit-container h2 {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            font-weight: bold;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        input[type="file"] {
            margin-top: 10px;
        }

        .profile-preview {
            margin-top: 10px;
        }

        .message {
            color: green;
            margin-bottom: 10px;
        }

        button {
            background: #007BFF;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<div class="edit-container">
    <h2>Edit Profile</h2>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
        </div>

        <div class="form-group">
            <label>Bio:</label>
            <textarea name="bio" rows="4"><?= htmlspecialchars($user['bio']) ?></textarea>
        </div>

      <div class="form-group">
    <label>Profile Picture:</label><br>
    <input type="file" id="profileInput" accept="image/*">
    <input type="hidden" name="cropped_image" id="croppedImage">
    <div class="profile-preview">
        <img id="preview" src="<?= getProfilePicture($user_id) ?>" alt="Preview" style="max-width: 100%; margin-top: 10px;">
    </div>
</div>
        <button type="submit">Save Changes</button>
    </form>
</div>

<script>
let cropper;
const input = document.getElementById('profileInput');
const preview = document.getElementById('preview');
const croppedImageInput = document.getElementById('croppedImage');

input.addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        preview.src = e.target.result;
        if (cropper) cropper.destroy();

        cropper = new Cropper(preview, {
            aspectRatio: 1,
            viewMode: 1,
            movable: true,
            zoomable: true,
            rotatable: false,
            crop(event) {
                const canvas = cropper.getCroppedCanvas({
                    width: 300,
                    height: 300,
                });

                croppedImageInput.value = canvas.toDataURL('image/jpeg');
            }
        });
    };
    reader.readAsDataURL(file);
});
</script>


</body>
</html>
