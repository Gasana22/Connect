<?php
session_start();
require 'db.php';
require 'helpers.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$caption = '';
$category = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = sanitize_input($_POST['caption']);
    $category = sanitize_input($_POST['category'] ?? '');

    if (empty($category)) {
        $error = "Please select a category for your business video.";
    }

    // Proceed only if there's no category error
    if (empty($error)) {
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $video_path = '';
        $video_uploaded = false;

        // Case 1: Handle recorded video (base64)
        if (!empty($_POST['recordedData'])) {
            $data = $_POST['recordedData'];
            $file_ext = 'webm'; // Default

            if (preg_match('/^data:video\/(\w+);base64,/', $data, $matches)) {
                $file_ext = $matches[1];
                $data = substr($data, strpos($data, ',') + 1);
            }

            $data = base64_decode($data);
            $new_filename = uniqid('vid_', true) . '.' . $file_ext;
            $video_path = $upload_dir . $new_filename;

            if (file_put_contents($video_path, $data)) {
                $video_uploaded = true;
            } else {
                $error = "Failed to save recorded video.";
            }

        // Case 2: Handle uploaded video file
        } elseif (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $video = $_FILES['video'];
            $allowed_types = ['video/mp4', 'video/webm', 'video/quicktime'];

            if (!in_array($video['type'], $allowed_types)) {
                $error = "Only MP4, WebM, or MOV formats are allowed.";
            } elseif ($video['size'] > 50 * 1024 * 1024) {
                $error = "The video must be less than 50MB.";
            } else {
                $new_filename = uniqid('vid_', true) . '.' . pathinfo($video['name'], PATHINFO_EXTENSION);
                $video_path = $upload_dir . $new_filename;

                if (move_uploaded_file($video['tmp_name'], $video_path)) {
                    $video_uploaded = true;
                } else {
                    $error = "Failed to move uploaded video.";
                }
            }
        } else {
            $error = "Please upload a valid video or record one.";
        }

        // If video successfully uploaded
        if ($video_uploaded && empty($error)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO videos (user_id, caption, video_path, category) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $caption, $video_path, $category]);
                $video_id = $pdo->lastInsertId();

                // Extract and store hashtags
                if (preg_match_all('/#(\w+)/', $caption, $matches)) {
                    $hashtags = array_unique(array_map('strtolower', $matches[1]));
                    $tag_stmt = $pdo->prepare("INSERT INTO video_hashtags (video_id, tag) VALUES (?, ?)");
                    foreach ($hashtags as $tag) {
                        $tag_stmt->execute([$video_id, $tag]);
                    }
                }

                $pdo->commit();
                $success = "Video uploaded successfully!";
                $caption = '';
                $category = '';

            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Upload failed: " . $e->getMessage();
                if (file_exists($video_path)) {
                    unlink($video_path);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upload Video | Business Platform</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #4361ee;
      --primary-dark: #3a56d4;
      --secondary: #3f37c9;
      --success: #4cc9f0;
      --danger: #f72585;
      --light: #f8f9fa;
      --dark: #212529;
      --gray: #6c757d;
      --light-gray: #e9ecef;
      --border-radius: 12px;
      --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background-color: #f5f7ff;
      color: var(--dark);
      line-height: 1.6;
      padding: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      width: 100%;
    }

    /* Header */
    .header {
      background-color: white;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      padding: 20px 0;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .header-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo {
      font-size: 24px;
      font-weight: 700;
      color: var(--primary);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .logo i {
      font-size: 28px;
    }

    .nav-links {
      display: flex;
      gap: 25px;
    }

    .nav-link {
      color: var(--gray);
      text-decoration: none;
      font-weight: 500;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .nav-link:hover {
      color: var(--primary);
    }

    .nav-link.active {
      color: var(--primary);
      font-weight: 600;
    }

    /* Main content */
    .main {
      flex: 1;
      display: flex;
      align-items: center;
      padding: 40px 0;
    }

    .upload-card {
      background-color: white;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      padding: 40px;
      width: 100%;
      max-width: 700px;
      margin: 0 auto;
      transition: var(--transition);
    }

    .upload-card:hover {
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
    }

    .upload-header {
      text-align: center;
      margin-bottom: 30px;
    }

    .upload-title {
      font-size: 28px;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 10px;
    }

    .upload-subtitle {
      color: var(--gray);
      font-size: 16px;
    }

    /* Form elements */
    .form-group {
      margin-bottom: 25px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--dark);
      font-size: 15px;
    }

    .file-upload-wrapper {
      position: relative;
      margin-bottom: 5px;
    }

    .file-upload-label {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
      border: 2px dashed var(--light-gray);
      border-radius: var(--border-radius);
      background-color: #fafbff;
      cursor: pointer;
      transition: var(--transition);
      text-align: center;
    }

    .file-upload-label:hover {
      border-color: var(--primary);
      background-color: rgba(67, 97, 238, 0.03);
    }

    .file-upload-label i {
      font-size: 48px;
      color: var(--primary);
      margin-bottom: 15px;
    }

    .file-upload-label h3 {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 5px;
      color: var(--dark);
    }

    .file-upload-label p {
      color: var(--gray);
      font-size: 14px;
    }

    .file-upload-input {
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }

    .file-info {
      font-size: 14px;
      color: var(--gray);
      margin-top: 5px;
      display: none;
    }

    .form-control {
      width: 100%;
      padding: 14px 16px;
      border: 1px solid var(--light-gray);
      border-radius: var(--border-radius);
      font-size: 15px;
      transition: var(--transition);
      background-color: #fafbff;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }

    textarea.form-control {
      min-height: 120px;
      resize: vertical;
    }

    .form-hint {
      font-size: 13px;
      color: var(--gray);
      margin-top: 6px;
      display: block;
    }

    /* Button */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 14px 24px;
      background-color: var(--primary);
      color: white;
      border: none;
      border-radius: var(--border-radius);
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      width: 100%;
      gap: 8px;
    }

    .btn:hover {
      background-color: var(--primary-dark);
      transform: translateY(-2px);
    }

    .btn:active {
      transform: translateY(0);
    }

    .btn i {
      font-size: 18px;
    }

    /* Alerts */
    .alert {
      padding: 16px;
      border-radius: var(--border-radius);
      margin-bottom: 25px;
      font-size: 15px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .alert i {
      font-size: 20px;
    }

    .alert-error {
      background-color: #fff0f3;
      color: var(--danger);
      border-left: 4px solid var(--danger);
    }

    .alert-success {
      background-color: #f0fff4;
      color: #2e7d32;
      border-left: 4px solid #2e7d32;
    }

    /* Preview section */
    .preview-section {
      display: none;
      margin-top: 20px;
      text-align: center;
    }

    .preview-title {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 10px;
      color: var(--gray);
    }

    .video-preview {
      width: 100%;
      max-height: 300px;
      border-radius: var(--border-radius);
      background-color: #000;
    }

    /* Video source options */
.video-source-options {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 20px;
    background-color: white;
    color: var(--primary);
    border: 1px solid var(--primary);
    border-radius: var(--border-radius);
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    flex: 1;
    gap: 8px;
}

.btn-secondary:hover {
    background-color: rgba(67, 97, 238, 0.05);
}

.btn-secondary i {
    font-size: 16px;
}

/* Camera modal */
.camera-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.8);
    z-index: 1000;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.camera-container {
    background: white;
    border-radius: var(--border-radius);
    padding: 20px;
    width: 90%;
    max-width: 500px;
    text-align: center;
}

#cameraPreview {
    width: 100%;
    background: black;
    border-radius: 8px;
    margin-bottom: 15px;
}

.camera-controls {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.camera-btn {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--primary);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.camera-btn.stop {
    background: var(--danger);
}


    /* Responsive */
    @media (max-width: 768px) {
      .upload-card {
        padding: 30px 20px;
      }
      
      .upload-title {
        font-size: 24px;
      }
      
      .nav-links {
        display: none;
      }
    }

    @media (max-width: 480px) {
      .file-upload-label {
        padding: 30px 15px;
      }
      
      .file-upload-label i {
        font-size: 40px;
      }
      
      .file-upload-label h3 {
        font-size: 16px;
      }
    }
  </style>
</head>
<body>
  <header class="header">
    <div class="container header-content">
      <a href="#" class="logo">
        <i class="fas fa-play-circle"></i>
        <span>Connect</span>
      </a>
    </div>
  </header>

  <main class="main">
    <div class="container">
      <div class="upload-card">
        <?php if ($error): ?>
          <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($success) ?>
          </div>
        <?php endif; ?>
        
        <form id="uploadForm" method="POST" enctype="multipart/form-data">
          <div class="form-group">
            <label for="category" class="form-label">Business Category</label>
            <select name="category" id="category" class="form-control" required>
              <option value="">Select a category</option>
              <option value="Restaurant" <?= $category === 'Restaurant' ? 'selected' : '' ?>>Restaurant & Food</option>
              <option value="Retail" <?= $category === 'Retail' ? 'selected' : '' ?>>Retail & Shopping</option>
              <option value="Health" <?= $category === 'Health' ? 'selected' : '' ?>>Health & Wellness</option>
              <option value="Beauty" <?= $category === 'Beauty' ? 'selected' : '' ?>>Beauty & Salon</option>
              <option value="Tech" <?= $category === 'Tech' ? 'selected' : '' ?>>Technology</option>
              <option value="Finance" <?= $category === 'Finance' ? 'selected' : '' ?>>Finance & Banking</option>
              <option value="RealEstate" <?= $category === 'RealEstate' ? 'selected' : '' ?>>Real Estate</option>
              <option value="Education" <?= $category === 'Education' ? 'selected' : '' ?>>Education</option>
              <option value="Entertainment" <?= $category === 'Entertainment' ? 'selected' : '' ?>>Entertainment</option>
              <option value="Automotive" <?= $category === 'Automotive' ? 'selected' : '' ?>>Automotive</option>
              <option value="Professional" <?= $category === 'Professional' ? 'selected' : '' ?>>Professional Services</option>
              <option value="HomeServices" <?= $category === 'HomeServices' ? 'selected' : '' ?>>Home Services</option>
              <option value="Travel" <?= $category === 'Travel' ? 'selected' : '' ?>>Travel & Hospitality</option>
              <option value="Fitness" <?= $category === 'Fitness' ? 'selected' : '' ?>>Fitness & Sports</option>
              <option value="Art" <?= $category === 'Art' ? 'selected' : '' ?>>Art & Creative</option>
              <option value="Other" <?= $category === 'Other' ? 'selected' : '' ?>>Other</option>
            </select>
            <span class="form-hint">Select the category that best fits your business</span>
          </div>

          <div class="form-group">
            <label class="form-label">Video Source</label>
            <div class="video-source-options">
              <button type="button" id="recordBtn" class="btn-secondary">
                <i class="fas fa-camera"></i> Record Video
              </button>
              <button type="button" id="galleryBtn" class="btn-secondary">
                <i class="fas fa-photo-video"></i> Choose from Gallery
              </button>
              <input type="file" id="video" name="video" accept="video/*" style="display: none;">
            </div>
            <div id="fileInfo" class="file-info"></div>
          </div>

          <!-- Camera Modal -->
          <div class="camera-modal" id="cameraModal">
            <div class="camera-container">
              <h3>Record Your Video</h3>
              <video id="cameraPreview" autoplay muted></video>
              <div class="camera-controls">
                <button class="camera-btn" id="startRecording">
                  <i class="fas fa-circle"></i>
                </button>
                <button class="camera-btn stop" id="stopRecording" style="display:none;">
                  <i class="fas fa-stop"></i>
                </button>
                <button class="camera-btn" id="closeCamera">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Hidden video element for recording preview -->
          <video id="recordedVideo" style="display:none;" controls></video>
          <input type="hidden" name="recordedData" id="recordedData">

          <div class="preview-section" id="previewSection">
            <p class="preview-title">Video Preview</p>
            <video controls class="video-preview" id="videoPreview"></video>
          </div>

          <div class="form-group">
            <label for="caption" class="form-label">Caption</label>
            <textarea name="caption" id="caption" class="form-control" rows="4" placeholder="Describe your video using #hashtags" required><?= htmlspecialchars($caption) ?></textarea>
            <span class="form-hint">Example: "Launching our new eco-product! #Sustainability #Startup"</span>
          </div>

          <button type="submit" class="btn">
            <i class="fas fa-upload"></i>
            Upload Video
          </button>
        </form>
      </div>
    </div>
  </main>

  <script>
    // Variables
    let mediaRecorder;
    let recordedChunks = [];
    const recordedVideo = document.getElementById('recordedVideo');
    const recordedData = document.getElementById('recordedData');
    const cameraModal = document.getElementById('cameraModal');
    const cameraPreview = document.getElementById('cameraPreview');
    const startRecordingBtn = document.getElementById('startRecording');
    const stopRecordingBtn = document.getElementById('stopRecording');
    const closeCameraBtn = document.getElementById('closeCamera');
    const recordBtn = document.getElementById('recordBtn');
    const galleryBtn = document.getElementById('galleryBtn');
    const fileInput = document.getElementById('video');
    const fileInfo = document.getElementById('fileInfo');
    const previewSection = document.getElementById('previewSection');
    const videoPreview = document.getElementById('videoPreview');
    const maxSize = 50 * 1024 * 1024; // 50MB

    // Event Listeners
    recordBtn.addEventListener('click', openCamera);
    galleryBtn.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', handleFileSelect);
    startRecordingBtn.addEventListener('click', startRecording);
    stopRecordingBtn.addEventListener('click', stopRecording);
    closeCameraBtn.addEventListener('click', closeCamera);

    // Camera functions
    async function openCamera() {
      cameraModal.style.display = 'flex';
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
          video: true, 
          audio: true 
        });
        cameraPreview.srcObject = stream;
        
        mediaRecorder = new MediaRecorder(stream, {
          mimeType: 'video/webm'
        });
        
        mediaRecorder.ondataavailable = (e) => {
          if (e.data.size > 0) {
            recordedChunks.push(e.data);
          }
        };
        
        mediaRecorder.onstop = () => {
          const blob = new Blob(recordedChunks, { type: 'video/webm' });
          const videoURL = URL.createObjectURL(blob);
          recordedVideo.src = videoURL;
          
          // Convert blob to base64 for form submission
          const reader = new FileReader();
          reader.readAsDataURL(blob);
          reader.onloadend = () => {
            recordedData.value = reader.result;
          };
          
          // Show preview
          showVideoPreview(blob, "Recorded video");
        };
      } catch (err) {
        console.error("Error accessing camera: ", err);
        alert("Could not access camera. Please check permissions.");
      }
    }

    function startRecording() {
      recordedChunks = [];
      mediaRecorder.start();
      startRecordingBtn.style.display = 'none';
      stopRecordingBtn.style.display = 'block';
    }

    function stopRecording() {
      mediaRecorder.stop();
      startRecordingBtn.style.display = 'block';
      stopRecordingBtn.style.display = 'none';
    }

    function closeCamera() {
      if (cameraPreview.srcObject) {
        cameraPreview.srcObject.getTracks().forEach(track => track.stop());
      }
      cameraModal.style.display = 'none';
    }

    // File handling functions
    function handleFileSelect() {
      if (this.files.length) {
        const file = this.files[0];
        
        // Validate file
        if (file.size > maxSize) {
          alert('File exceeds the 50MB size limit.');
          this.value = '';
          return;
        }
        
        const validTypes = ['video/mp4', 'video/webm', 'video/quicktime'];
        if (!validTypes.includes(file.type)) {
          alert('Only MP4, WebM, or MOV formats are allowed.');
          this.value = '';
          return;
        }
        
        showVideoPreview(file, file.name);
      }
    }

    function showVideoPreview(file, fileName) {
      const videoURL = URL.createObjectURL(file);
      
      // Show file info
      fileInfo.style.display = 'block';
      fileInfo.innerHTML = `
        <i class="fas fa-file-video" style="color: #4361ee;"></i>
        Selected: <strong>${fileName}</strong> (${formatFileSize(file.size)})
      `;
      
      // Show video preview
      videoPreview.src = videoURL;
      previewSection.style.display = 'block';
    }

    function formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    // Form submission feedback
    const form = document.getElementById('uploadForm');
    form.addEventListener('submit', function() {
      const btn = this.querySelector('button[type="submit"]');
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
      btn.disabled = true;
    });
  </script>
</body>
</html>