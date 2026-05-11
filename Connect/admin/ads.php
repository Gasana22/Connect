<?php
require 'auth.php';
requireAdminAuth();

// Rest of your existing ads.php code... 

// Database connection with error handling
try {
    $pdo = new PDO('mysql:host=localhost;dbname=connect', 'username', 'password', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die('Database connection error');
}

// Handle form submissions with CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf($_POST['csrf_token']);
    
    // Input validation function
    function sanitizeInput($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    if (isset($_POST['delete_ad'])) {
        // Validate ad ID
        $ad_id = filter_input(INPUT_POST, 'ad_id', FILTER_VALIDATE_INT);
        if (!$ad_id) {
            $_SESSION['error'] = "Invalid ad ID";
            header("Location: ads.php");
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM ads WHERE id = ?");
            $stmt->execute([$ad_id]);
            $_SESSION['message'] = "Ad deleted successfully";
        } catch (PDOException $e) {
            error_log("Delete ad failed: " . $e->getMessage());
            $_SESSION['error'] = "Failed to delete ad";
        }
    } else {
        // Validate all inputs
        $data = [
            'title' => sanitizeInput($_POST['title']),
            'description' => sanitizeInput($_POST['description']),
            'target_url' => filter_var($_POST['target_url'], FILTER_VALIDATE_URL),
            'sponsor' => sanitizeInput($_POST['sponsor']),
            'duration' => filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 5, 'max_range' => 60]
            ]),
            'start_date' => DateTime::createFromFormat('Y-m-d\TH:i', $_POST['start_date'])->format('Y-m-d H:i:s'),
            'end_date' => DateTime::createFromFormat('Y-m-d\TH:i', $_POST['end_date'])->format('Y-m-d H:i:s'),
            'max_impressions' => filter_input(INPUT_POST, 'max_impressions', FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 0]
            ]),
            'budget' => filter_input(INPUT_POST, 'budget', FILTER_VALIDATE_FLOAT, [
                'options' => ['min_range' => 0]
            ]),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // Validate required fields
        if (empty($data['title']) || empty($data['target_url']) || empty($data['sponsor'])) {
            $_SESSION['error'] = "Required fields are missing";
            header("Location: ads.php");
            exit;
        }
        
        // Handle file upload securely
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = '../uploads/ads/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Validate file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fileInfo, $_FILES['image']['tmp_name']);
            
            if (!in_array($mime, $allowedTypes)) {
                $_SESSION['error'] = "Only JPG, PNG, and GIF images are allowed";
                header("Location: ads.php");
                exit;
            }
            
            // Validate file size (max 2MB)
            if ($_FILES['image']['size'] > 2097152) {
                $_SESSION['error'] = "Image must be less than 2MB";
                header("Location: ads.php");
                exit;
            }
            
            // Generate secure filename
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $data['image_url'] = '/uploads/ads/' . $fileName;
                
                // Remove old image if updating
                if (!empty($_POST['ad_id'])) {
                    $oldImage = $pdo->prepare("SELECT image_url FROM ads WHERE id = ?");
                    $oldImage->execute([$_POST['ad_id']]);
                    $oldImagePath = $oldImage->fetchColumn();
                    if ($oldImagePath && file_exists('../' . $oldImagePath)) {
                        unlink('../' . $oldImagePath);
                    }
                }
            } else {
                $_SESSION['error'] = "Failed to upload image";
                header("Location: ads.php");
                exit;
            }
        }
        
        try {
            if (!empty($_POST['ad_id'])) {
                // Update existing ad
                $data['id'] = filter_input(INPUT_POST, 'ad_id', FILTER_VALIDATE_INT);
                if (!$data['id']) {
                    throw new Exception("Invalid ad ID");
                }
                
                $stmt = $pdo->prepare("UPDATE ads SET 
                    title = :title,
                    description = :description,
                    image_url = COALESCE(:image_url, image_url),
                    target_url = :target_url,
                    sponsor = :sponsor,
                    duration = :duration,
                    start_date = :start_date,
                    end_date = :end_date,
                    max_impressions = :max_impressions,
                    budget = :budget,
                    is_active = :is_active,
                    updated_at = NOW()
                    WHERE id = :id");
                $stmt->execute($data);
                $_SESSION['message'] = "Ad updated successfully";
            } else {
                // Create new ad - ensure image is provided
                if (empty($data['image_url'])) {
                    $_SESSION['error'] = "Image is required for new ads";
                    header("Location: ads.php");
                    exit;
                }
                
                $stmt = $pdo->prepare("INSERT INTO ads (
                    title, description, image_url, target_url, sponsor, duration,
                    start_date, end_date, max_impressions, budget, is_active
                ) VALUES (
                    :title, :description, :image_url, :target_url, :sponsor, :duration,
                    :start_date, :end_date, :max_impressions, :budget, :is_active
                )");
                $stmt->execute($data);
                $_SESSION['message'] = "Ad created successfully";
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $_SESSION['error'] = "Database operation failed";
        }
    }
    header("Location: ads.php");
    exit;
}
// Secure file upload configuration
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_MIME_TYPES', [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif'
]);

// File upload validation function
function validateUploadedFile($file) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload error: " . $file['error']);
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception("File too large. Maximum size is " . (MAX_FILE_SIZE / 1024 / 1024) . "MB");
    }
    
    // Verify MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!array_key_exists($mime, ALLOWED_MIME_TYPES)) {
        throw new Exception("Invalid file type. Allowed types: " . implode(', ', array_values(ALLOWED_MIME_TYPES)));
    }
    
    // Verify extension matches MIME type
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== ALLOWED_MIME_TYPES[$mime]) {
        throw new Exception("File extension doesn't match MIME type");
    }
    
    return true;
}

// Rest of your admin panel code...