<?php
require_once __DIR__ . '/includes/auth.php';
requireAuth();

$userId = currentUserId();
$user = currentUser();
$error = '';
$success = '';
$caption = '';
$category = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $caption = sanitize_input($_POST['caption'] ?? '');
    $category = sanitize_input($_POST['category'] ?? '');

    if ($category === '') {
        $error = 'Please select a category.';
    }

    $videoPath = null;

    if (!$error) {
        if (!empty($_POST['recordedData'])) {
            // Recorded-in-browser video, sent as a base64 data: URL.
            $data = $_POST['recordedData'];
            $ext = 'webm';
            if (preg_match('/^data:video\/(\w+);base64,/', $data, $m)) {
                $ext = $m[1];
                $data = substr($data, strpos($data, ',') + 1);
            }
            $decoded = base64_decode($data, true);
            if ($decoded === false || strlen($decoded) > UPLOAD_MAX_VIDEO_BYTES) {
                $error = 'Recorded video is invalid or too large.';
            } else {
                $filename = randomFilename($ext);
                if (file_put_contents(UPLOAD_DIR_VIDEOS . $filename, $decoded) !== false) {
                    $videoPath = UPLOAD_URL_VIDEOS . $filename;
                } else {
                    $error = 'Failed to save recorded video.';
                }
            }
        } elseif (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $result = validateVideoUpload($_FILES['video']);
            if (!$result['ok']) {
                $error = $result['error'];
            } else {
                $filename = randomFilename($result['ext']);
                if (move_uploaded_file($_FILES['video']['tmp_name'], UPLOAD_DIR_VIDEOS . $filename)) {
                    $videoPath = UPLOAD_URL_VIDEOS . $filename;
                } else {
                    $error = 'Failed to save uploaded video.';
                }
            }
        } else {
            $error = 'Please choose a video or record one.';
        }
    }

    if (!$error && $videoPath) {
        $pdo = getPDO();
        $pdo->beginTransaction();
        try {
            $hashtags = extractHashtags($caption);
            $stmt = $pdo->prepare('INSERT INTO videos (user_id, caption, video_path, tags, category) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$userId, $caption, $videoPath, implode(',', $hashtags), $category]);
            $videoId = (int) $pdo->lastInsertId();

            if ($hashtags) {
                $tagStmt = $pdo->prepare('INSERT INTO video_hashtags (video_id, tag) VALUES (?, ?)');
                foreach ($hashtags as $tag) {
                    $tagStmt->execute([$videoId, $tag]);
                }
            }

            $pdo->commit();
            header('Location: watch.php?id=' . $videoId);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Upload failed: ' . $e->getMessage());
            $error = 'Upload failed. Please try again.';
            @unlink(__DIR__ . '/' . $videoPath);
        }
    }
}

$pageTitle = 'Upload';
$activeNav = 'upload';
include __DIR__ . '/includes/layout_header.php';
?>
<div class="card card-pad" style="max-width: 640px; margin: 0 auto;">
  <h1 style="margin-bottom: 0.5rem;">Upload a video</h1>
  <p class="text-muted" style="margin-bottom: 1.5rem;"><?= $user['account_type'] === 'business' ? 'Show off a product, a promotion, or your business in action.' : 'Share what you\'re up to.' ?></p>

  <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= e($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= e($success) ?></div><?php endif; ?>

  <form id="uploadForm" method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>

    <div class="form-group">
      <label class="form-label" for="category">Category</label>
      <select class="form-control" id="category" name="category" required>
        <option value="">Select a category</option>
        <?php foreach (BUSINESS_CATEGORIES as $slug => $label): ?>
          <option value="<?= e($slug) ?>" <?= $category === $slug ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label">Video source</label>
      <div class="flex gap-2">
        <button type="button" id="recordBtn" class="btn btn-outline"><i class="fas fa-camera"></i> Record</button>
        <button type="button" id="galleryBtn" class="btn btn-outline"><i class="fas fa-photo-film"></i> Choose file</button>
        <input type="file" id="video" name="video" accept="video/*" style="display:none;">
      </div>
      <div id="fileInfo" class="form-hint" style="display:none;"></div>
    </div>

    <div class="camera-modal" id="cameraModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:1000; align-items:center; justify-content:center;">
      <div class="card card-pad" style="max-width:480px; width:90%; text-align:center;">
        <h3 style="margin-bottom:1rem;">Record your video</h3>
        <video id="cameraPreview" autoplay muted style="width:100%; background:#000; border-radius: var(--radius-sm); margin-bottom:1rem;"></video>
        <div class="flex gap-2" style="justify-content:center;">
          <button type="button" class="btn btn-primary" id="startRecording"><i class="fas fa-circle"></i></button>
          <button type="button" class="btn btn-danger" id="stopRecording" style="display:none;"><i class="fas fa-stop"></i></button>
          <button type="button" class="btn btn-secondary" id="closeCamera"><i class="fas fa-xmark"></i></button>
        </div>
      </div>
    </div>

    <video id="recordedVideo" style="display:none;" controls></video>
    <input type="hidden" name="recordedData" id="recordedData">

    <div id="previewSection" style="display:none;" class="form-group">
      <label class="form-label">Preview</label>
      <video controls id="videoPreview" style="width:100%; max-height:300px; background:#000; border-radius: var(--radius-sm);"></video>
    </div>

    <div class="form-group">
      <label class="form-label" for="caption">Caption</label>
      <textarea class="form-control" id="caption" name="caption" maxlength="500" placeholder="Describe your video with #hashtags"><?= e($caption) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-upload"></i> Upload</button>
  </form>
</div>

<script>
let mediaRecorder, recordedChunks = [];
const recordedVideo = document.getElementById('recordedVideo');
const recordedData = document.getElementById('recordedData');
const cameraModal = document.getElementById('cameraModal');
const cameraPreview = document.getElementById('cameraPreview');
const fileInput = document.getElementById('video');
const fileInfo = document.getElementById('fileInfo');
const previewSection = document.getElementById('previewSection');
const videoPreview = document.getElementById('videoPreview');
const maxSize = <?= UPLOAD_MAX_VIDEO_BYTES ?>;

document.getElementById('recordBtn').addEventListener('click', openCamera);
document.getElementById('galleryBtn').addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', handleFileSelect);
document.getElementById('startRecording').addEventListener('click', startRecording);
document.getElementById('stopRecording').addEventListener('click', stopRecording);
document.getElementById('closeCamera').addEventListener('click', closeCamera);

async function openCamera() {
  cameraModal.style.display = 'flex';
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
    cameraPreview.srcObject = stream;
    mediaRecorder = new MediaRecorder(stream, { mimeType: 'video/webm' });
    mediaRecorder.ondataavailable = (e) => e.data.size > 0 && recordedChunks.push(e.data);
    mediaRecorder.onstop = () => {
      const blob = new Blob(recordedChunks, { type: 'video/webm' });
      recordedVideo.src = URL.createObjectURL(blob);
      const reader = new FileReader();
      reader.onloadend = () => { recordedData.value = reader.result; };
      reader.readAsDataURL(blob);
      showPreview(blob, 'Recorded video');
      closeCamera();
    };
  } catch (err) {
    toast('Could not access camera. Check permissions.', 'error');
  }
}
function startRecording() {
  recordedChunks = [];
  mediaRecorder.start();
  document.getElementById('startRecording').style.display = 'none';
  document.getElementById('stopRecording').style.display = 'inline-flex';
}
function stopRecording() {
  mediaRecorder.stop();
  document.getElementById('startRecording').style.display = 'inline-flex';
  document.getElementById('stopRecording').style.display = 'none';
}
function closeCamera() {
  if (cameraPreview.srcObject) cameraPreview.srcObject.getTracks().forEach((t) => t.stop());
  cameraModal.style.display = 'none';
}
function handleFileSelect() {
  if (!this.files.length) return;
  const file = this.files[0];
  if (file.size > maxSize) { toast('File exceeds the size limit.', 'error'); this.value = ''; return; }
  showPreview(file, file.name);
}
function showPreview(file, name) {
  fileInfo.style.display = 'block';
  fileInfo.textContent = 'Selected: ' + name;
  videoPreview.src = URL.createObjectURL(file);
  previewSection.style.display = 'block';
}
document.getElementById('uploadForm').addEventListener('submit', function () {
  const btn = this.querySelector('button[type="submit"]');
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
  btn.disabled = true;
});
</script>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
