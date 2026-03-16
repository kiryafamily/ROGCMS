<?php
// upload-logo.php - Upload School Logo
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Get current logo
$stmt = $pdo->query("SELECT logo_path FROM school_info WHERE id = 1");
$school = $stmt->fetch();
$current_logo = $school['logo_path'] ?? '';

// Handle logo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['school_logo'])) {
    $target_dir = "uploads/logo/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($_FILES['school_logo']['name']);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is actual image
    $check = getimagesize($_FILES['school_logo']['tmp_name']);
    if ($check === false) {
        $error = "File is not an image.";
    }
    // Check file size (max 2MB)
    elseif ($_FILES['school_logo']['size'] > 2000000) {
        $error = "File is too large. Max size is 2MB.";
    }
    // Allow certain file formats
    elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
    }
    else {
        // Delete old logo if exists
        if (!empty($current_logo) && file_exists($current_logo)) {
            unlink($current_logo);
        }
        
        if (move_uploaded_file($_FILES['school_logo']['tmp_name'], $target_file)) {
            // Update database
            $stmt = $pdo->prepare("UPDATE school_info SET logo_path = ? WHERE id = 1");
            if ($stmt->execute([$target_file])) {
                $message = "School logo uploaded successfully!";
                $current_logo = $target_file;
            } else {
                $error = "Database update failed.";
            }
        } else {
            $error = "Error uploading file.";
        }
    }
}

// Handle logo removal
if (isset($_POST['remove_logo'])) {
    if (!empty($current_logo) && file_exists($current_logo)) {
        unlink($current_logo);
    }
    $stmt = $pdo->prepare("UPDATE school_info SET logo_path = NULL WHERE id = 1");
    $stmt->execute();
    $message = "Logo removed successfully!";
    $current_logo = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload School Logo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #4B1C3C 0%, #36152B 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .upload-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        h1 {
            color: #4B1C3C;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        h1 i {
            color: #FFB800;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            border-left: 3px solid #FFB800;
            padding-left: 15px;
        }

        .current-logo {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f4f8;
            border-radius: 10px;
            border: 2px dashed #FFB800;
        }

        .logo-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 15px;
            overflow: hidden;
            border: 4px solid #FFB800;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            background: white;
        }

        .logo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .logo-preview-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #4B1C3C, #5C234A);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #FFB800;
        }

        .logo-preview-placeholder i {
            font-size: 3rem;
            margin-bottom: 5px;
        }

        .logo-preview-placeholder span {
            font-size: 0.8rem;
        }

        .message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .success {
            background: #E8F5E9;
            color: #2E7D32;
            border-left: 4px solid #4CAF50;
        }

        .error {
            background: #FFEBEE;
            color: #C62828;
            border-left: 4px solid #f44336;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4B1C3C;
            font-weight: 600;
        }

        .form-group label i {
            color: #FFB800;
            margin-right: 5px;
        }

        .file-input-wrapper {
            position: relative;
            border: 2px dashed #e0e0e0;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-input-wrapper:hover {
            border-color: #FFB800;
            background: #f8f4f8;
        }

        .file-input-wrapper input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-input-wrapper i {
            font-size: 3rem;
            color: #4B1C3C;
            margin-bottom: 10px;
        }

        .file-input-wrapper p {
            color: #666;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: #4B1C3C;
            color: white;
        }

        .btn-primary:hover {
            background: #2F1224;
        }

        .btn-primary i {
            color: #FFB800;
        }

        .btn-warning {
            background: #FFB800;
            color: #4B1C3C;
        }

        .btn-warning:hover {
            background: #D99B00;
        }

        .btn-secondary {
            background: #f0e8f0;
            color: #4B1C3C;
        }

        .btn-secondary:hover {
            background: #e0d0e0;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .preview-text {
            font-size: 0.9rem;
            color: #999;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <h1><i class="fas fa-image"></i> School Logo Upload</h1>
        <div class="subtitle">Upload your school logo (will appear on report cards)</div>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="current-logo">
            <div class="logo-preview">
                <?php if ($current_logo && file_exists($current_logo)): ?>
                    <img src="<?php echo $current_logo; ?>" alt="School Logo">
                <?php else: ?>
                    <div class="logo-preview-placeholder">
                        <i class="fas fa-school"></i>
                        <span>SCHOOL LOGO</span>
                    </div>
                <?php endif; ?>
            </div>
            <p style="color: #4B1C3C; font-weight: 600;">Current School Logo</p>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label><i class="fas fa-cloud-upload-alt"></i> Choose New Logo</label>
                <div class="file-input-wrapper">
                    <i class="fas fa-camera"></i>
                    <p>Click or drag to upload</p>
                    <small style="color: #999;">PNG, JPG, GIF (Max 2MB)</small>
                    <input type="file" name="school_logo" accept="image/*" required>
                </div>
            </div>
            
            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload Logo
                </button>
                <?php if ($current_logo): ?>
                <button type="submit" name="remove_logo" class="btn btn-warning" onclick="return confirm('Remove current logo?')">
                    <i class="fas fa-trash"></i> Remove Logo
                </button>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </form>
        
        <div class="preview-text">
            <i class="fas fa-info-circle" style="color: #FFB800;"></i>
            Logo will appear on all report cards and official documents.
        </div>
    </div>
</body>
</html>