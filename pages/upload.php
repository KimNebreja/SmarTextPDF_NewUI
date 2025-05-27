<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['pdf_file'])) {
        $file = $_FILES['pdf_file'];
        $custom_name = sanitizeInput($_POST['custom_name'] ?? '');
        
        // Validate file
        if ($file['error'] === UPLOAD_ERR_OK) {
            $file_type = mime_content_type($file['tmp_name']);
            if ($file_type !== 'application/pdf') {
                $error = 'Only PDF files are allowed';
            } else {
                try {
                    $db = getDBConnection();
                    
                    // Generate unique filename
                    $original_filename = $file['name'];
                    $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '.' . $file_extension;
                    $upload_path = '../uploads/' . $new_filename;
                    
                    // Create uploads directory if it doesn't exist
                    if (!file_exists('../uploads')) {
                        mkdir('../uploads', 0777, true);
                    }
                    
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        // Save file info to database
                        $stmt = $db->prepare("INSERT INTO uploads (user_id, original_filename, custom_name, file_path, file_size, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                        $stmt->execute([
                            $_SESSION['user_id'],
                            $original_filename,
                            $custom_name,
                            $upload_path,
                            $file['size']
                        ]);
                        
                        $success = 'File uploaded successfully! Processing will begin shortly.';
                    } else {
                        $error = 'Failed to save the file';
                    }
                } catch (PDOException $e) {
                    error_log("Upload Error: " . $e->getMessage());
                    $error = 'Upload failed. Please try again later.';
                }
            }
        } else {
            $error = 'Error uploading file. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmarTextPDF - Upload</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="logo">
                <img src="../assets/SmarText PDF_sidebar-logo.svg" alt="SmarTextPDF Logo" class="sidebar-logo">
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li class="active"><a href="upload.php" aria-current="page">Upload PDF</a></li>
                <li><a href="comparison.php">Compare PDFs</a></li>
                <li><a href="#" onclick="logout()" class="logout-link">Logout</a></li>
            </ul>
        </nav>
        
        <main class="main-content">
            <header class="top-bar">
                <div class="header-left">
                    <h1>Upload PDF</h1>
                </div>
                <div class="user-info">
                    <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </header>

            <div class="upload-container">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="upload-card">
                    <div class="upload-header">
                        <img src="../assets/SmarText PDF_main-logo.svg" alt="SmarTextPDF Logo" class="main-logo">
                    </div>

                    <form method="POST" action="" enctype="multipart/form-data" class="upload-form" id="uploadForm">
                        <div class="form-group">
                            <label for="custom_name">Custom Name (Optional)</label>
                            <input type="text" id="custom_name" name="custom_name" 
                                   placeholder="Enter a custom name for your file">
                        </div>

                        <div class="upload-area" id="dropZone">
                            <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" required 
                                   class="file-input" onchange="handleFileSelect(event)">
                            <p class="upload-text">Click to Upload or Drag and drop your PDF here</p>
                            <div class="upload-icon">
                                <img src="../assets/SmarText PDF_folder-icon.svg" alt="Upload Icon" class="folder-icon">
                            </div>
                            <div class="upload-btn-container">
                                <button type="button" class="btn-secondary upload-btn" onclick="document.getElementById('pdf_file').click()">
                                    <img src="../assets/SmarText PDF_upload-btn.svg" alt="Upload Button" class="upload-btn-icon">
                                </button>
                            </div>
                            <p class="file-info" id="fileInfo"></p>
                        </div>

                        <button type="submit" class="btn-primary btn-block" id="uploadBtn" disabled>
                            Upload and Process
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../scripts/auth.js"></script>
    <script>
        // File upload handling
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('pdf_file');
        const fileInfo = document.getElementById('fileInfo');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadForm = document.getElementById('uploadForm');

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Highlight drop zone when dragging over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        // Handle dropped files
        dropZone.addEventListener('drop', handleDrop, false);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight(e) {
            dropZone.classList.add('highlight');
        }

        function unhighlight(e) {
            dropZone.classList.remove('highlight');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        function handleFileSelect(e) {
            const files = e.target.files;
            handleFiles(files);
        }

        function handleFiles(files) {
            if (files.length > 0) {
                const file = files[0];
                if (file.type === 'application/pdf') {
                    fileInfo.textContent = `Selected file: ${file.name} (${formatFileSize(file.size)})`;
                    uploadBtn.disabled = false;
                } else {
                    fileInfo.textContent = 'Please select a PDF file';
                    uploadBtn.disabled = true;
                }
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Form submission handling
        uploadForm.addEventListener('submit', function(e) {
            const file = fileInput.files[0];
            if (!file) {
                e.preventDefault();
                alert('Please select a file to upload');
                return;
            }
            
            if (file.type !== 'application/pdf') {
                e.preventDefault();
                alert('Only PDF files are allowed');
                return;
            }
        });

        // Logout function
        function logout() {
            fetch('../api/logout.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '../index.php';
                    }
                })
                .catch(error => console.error('Error logging out:', error));
        }
    </script>
</body>
</html> 