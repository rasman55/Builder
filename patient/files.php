<?php
session_start();
require_once '../config/database.php';
require_once '../common/file_upload.php';

// Check if user is logged in
if (!isset($_SESSION['patient_logged_in']) || $_SESSION['patient_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$patient_ssn = $_SESSION['patient_ssn'];
$uploader = new FileUploader();

// Get patient files
$category_filter = isset($_GET['category']) ? DatabaseHelper::sanitizeInput($_GET['category']) : null;
$files = $uploader->getPatientFiles($patient_ssn, $category_filter);

// Get file categories with counts
$categories_sql = "SELECT category, COUNT(*) as count FROM File_attachments WHERE patient_ssn = ? AND status = 'Active' GROUP BY category ORDER BY category";
$categories = $db->select($categories_sql, [$patient_ssn]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Files - Patient Portal</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/portal.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="portal-body">
    <!-- Navigation -->
    <nav class="portal-nav">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="dashboard.php">
                    <i class="fas fa-hospital"></i>
                    <span>Goba Hospital - Patient Portal</span>
                </a>
            </div>
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-dashboard"></i> Dashboard
                </a>
                <a href="records.php" class="nav-link">
                    <i class="fas fa-clipboard-list"></i> Records
                </a>
                <a href="files.php" class="nav-link active">
                    <i class="fas fa-folder"></i> Files
                </a>
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="payments.php" class="nav-link">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <h1><i class="fas fa-folder"></i> My Medical Files</h1>
            <p>Upload and manage your medical documents, images, and reports</p>
        </div>

        <!-- File Upload Section -->
        <div class="content-card">
            <h2><i class="fas fa-cloud-upload-alt"></i> Upload New File</h2>
            <p class="upload-info">
                <i class="fas fa-info-circle"></i>
                Accepted formats: JPG, PNG, PDF, DOC, DOCX, TXT, MP3, WAV. Maximum size: 10MB
            </p>
            
            <form id="uploadForm" class="upload-form" enctype="multipart/form-data">
                <div class="upload-grid">
                    <div class="form-group">
                        <label for="file">Choose File *</label>
                        <input type="file" id="file" name="file" required 
                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.csv,.mp3,.wav,.ogg">
                        <div class="file-info">
                            <span class="file-name"></span>
                            <span class="file-size"></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">File Category *</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="Medical_Report">Medical Report</option>
                            <option value="Lab_Result">Lab Result</option>
                            <option value="Imaging">Medical Image/X-ray</option>
                            <option value="Prescription">Prescription</option>
                            <option value="Insurance">Insurance Document</option>
                            <option value="Identification">ID Document</option>
                            <option value="Audio_Record">Audio Recording</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3" 
                                  placeholder="Optional description of the file..."></textarea>
                    </div>
                </div>
                
                <div class="upload-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload File
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
                
                <input type="hidden" name="action" value="upload">
                <input type="hidden" name="patient_ssn" value="<?php echo htmlspecialchars($patient_ssn); ?>">
            </form>
            
            <!-- Upload Progress -->
            <div id="uploadProgress" class="upload-progress" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <span class="progress-text">Uploading...</span>
            </div>
        </div>

        <!-- File Categories Filter -->
        <div class="content-card">
            <h2><i class="fas fa-filter"></i> Filter by Category</h2>
            <div class="category-filters">
                <a href="files.php" class="category-filter <?php echo !$category_filter ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i>
                    <span>All Files</span>
                    <span class="count"><?php echo count($files); ?></span>
                </a>
                
                <?php foreach ($categories as $cat): ?>
                    <a href="files.php?category=<?php echo urlencode($cat['category']); ?>" 
                       class="category-filter <?php echo $category_filter === $cat['category'] ? 'active' : ''; ?>">
                        <i class="fas fa-<?php 
                            echo $cat['category'] === 'Medical_Report' ? 'file-medical' :
                                ($cat['category'] === 'Lab_Result' ? 'flask' :
                                ($cat['category'] === 'Imaging' ? 'image' :
                                ($cat['category'] === 'Prescription' ? 'prescription' :
                                ($cat['category'] === 'Insurance' ? 'shield-alt' :
                                ($cat['category'] === 'Identification' ? 'id-card' :
                                ($cat['category'] === 'Audio_Record' ? 'microphone' : 'file'))))));
                        ?>"></i>
                        <span><?php echo str_replace('_', ' ', $cat['category']); ?></span>
                        <span class="count"><?php echo $cat['count']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Files List -->
        <div class="content-card">
            <h2><i class="fas fa-list"></i> My Files 
                <?php if ($category_filter): ?>
                    - <?php echo str_replace('_', ' ', $category_filter); ?>
                <?php endif; ?>
                (<?php echo count($files); ?> files)
            </h2>
            
            <?php if (empty($files)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No files found</h3>
                    <p>Upload your first medical document to get started.</p>
                </div>
            <?php else: ?>
                <div class="files-grid">
                    <?php foreach ($files as $file): ?>
                        <div class="file-card" data-file-id="<?php echo $file['file_id']; ?>">
                            <div class="file-icon">
                                <i class="fas fa-<?php 
                                    echo strpos($file['mime_type'], 'image') !== false ? 'image' :
                                        (strpos($file['mime_type'], 'pdf') !== false ? 'file-pdf' :
                                        (strpos($file['mime_type'], 'audio') !== false ? 'music' :
                                        (strpos($file['mime_type'], 'word') !== false ? 'file-word' : 'file')));
                                ?>"></i>
                            </div>
                            
                            <div class="file-info">
                                <h4 class="file-title"><?php echo htmlspecialchars($file['original_file_name']); ?></h4>
                                <p class="file-description"><?php echo htmlspecialchars($file['description'] ?: 'No description'); ?></p>
                                
                                <div class="file-meta">
                                    <span class="file-category">
                                        <i class="fas fa-tag"></i>
                                        <?php echo str_replace('_', ' ', $file['category']); ?>
                                    </span>
                                    <span class="file-size">
                                        <i class="fas fa-hdd"></i>
                                        <?php echo formatFileSize($file['file_size_bytes']); ?>
                                    </span>
                                    <span class="file-date">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('M d, Y', strtotime($file['created_at'])); ?>
                                    </span>
                                    <span class="file-uploader">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($file['uploaded_by_name']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="file-actions">
                                <?php if (strpos($file['mime_type'], 'image') !== false): ?>
                                    <button class="btn-action" onclick="previewImage(<?php echo $file['file_id']; ?>)" title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <a href="../common/file_upload.php?download=<?php echo $file['file_id']; ?>" 
                                   class="btn-action" title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                                
                                <?php if ($file['uploaded_by_type'] === 'Patient'): ?>
                                    <button class="btn-action btn-danger" 
                                            onclick="deleteFile(<?php echo $file['file_id']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="imageModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeImageModal()">&times;</span>
            <img id="previewImage" src="" alt="File Preview">
        </div>
    </div>

    <script>
        // File upload handling
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const progressEl = document.getElementById('uploadProgress');
            const progressFill = progressEl.querySelector('.progress-fill');
            const progressText = progressEl.querySelector('.progress-text');
            
            // Show progress
            progressEl.style.display = 'block';
            
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressFill.style.width = percentComplete + '%';
                    progressText.textContent = `Uploading... ${Math.round(percentComplete)}%`;
                }
            });
            
            xhr.addEventListener('load', function() {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        showNotification('File uploaded successfully!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification('Upload failed: ' + response.message, 'error');
                    }
                } catch (e) {
                    showNotification('Upload failed: Invalid response', 'error');
                }
                
                progressEl.style.display = 'none';
            });
            
            xhr.addEventListener('error', function() {
                showNotification('Upload failed: Network error', 'error');
                progressEl.style.display = 'none';
            });
            
            xhr.open('POST', '../common/file_upload.php');
            xhr.send(formData);
        });
        
        // File input change handler
        document.getElementById('file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileInfo = document.querySelector('.file-info');
            
            if (file) {
                document.querySelector('.file-name').textContent = file.name;
                document.querySelector('.file-size').textContent = formatFileSize(file.size);
                fileInfo.style.display = 'block';
            } else {
                fileInfo.style.display = 'none';
            }
        });
        
        // Delete file function
        function deleteFile(fileId) {
            if (!confirm('Are you sure you want to delete this file? This action cannot be undone.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('file_id', fileId);
            
            fetch('../common/file_upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('File deleted successfully!', 'success');
                    // Remove file card from DOM
                    const fileCard = document.querySelector(`[data-file-id="${fileId}"]`);
                    if (fileCard) {
                        fileCard.remove();
                    }
                } else {
                    showNotification('Delete failed: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Delete failed: Network error', 'error');
            });
        }
        
        // Preview image function
        function previewImage(fileId) {
            const modal = document.getElementById('imageModal');
            const img = document.getElementById('previewImage');
            
            img.src = `../common/file_upload.php?download=${fileId}`;
            modal.style.display = 'block';
        }
        
        // Close image modal
        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }
        
        // Format file size function
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Notification function
        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()">×</button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('imageModal');
            if (e.target === modal) {
                closeImageModal();
            }
        });
    </script>

    <style>
        .upload-form {
            background: #f8fafc;
            padding: 2rem;
            border-radius: 12px;
            border: 2px dashed #cbd5e0;
            margin-bottom: 2rem;
        }
        
        .upload-info {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #ebf8ff;
            border-radius: 8px;
            color: #2b6cb0;
            border-left: 4px solid #3182ce;
        }
        
        .upload-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .upload-grid .full-width {
            grid-column: 1 / -1;
        }
        
        .file-info {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #718096;
            display: none;
        }
        
        .upload-actions {
            display: flex;
            gap: 1rem;
        }
        
        .upload-progress {
            margin-top: 1rem;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3182ce, #63b3ed);
            transition: width 0.3s ease;
            width: 0%;
        }
        
        .progress-text {
            display: block;
            text-align: center;
            margin-top: 0.5rem;
            color: #4a5568;
        }
        
        .category-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .category-filter {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            text-decoration: none;
            color: #4a5568;
            transition: all 0.3s ease;
        }
        
        .category-filter:hover,
        .category-filter.active {
            background: #3182ce;
            color: white;
            border-color: #3182ce;
        }
        
        .category-filter .count {
            margin-left: auto;
            background: rgba(0,0,0,0.1);
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .files-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .file-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            background: white;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .file-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .file-icon {
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .file-icon i {
            font-size: 3rem;
            color: #3182ce;
        }
        
        .file-title {
            margin: 0 0 0.5rem 0;
            color: #2d3748;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .file-description {
            color: #718096;
            margin: 0 0 1rem 0;
            font-size: 0.9rem;
        }
        
        .file-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: #718096;
        }
        
        .file-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .file-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }
        
        .btn-action {
            background: none;
            border: 1px solid #e2e8f0;
            padding: 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            color: #4a5568;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .btn-action:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
        }
        
        .btn-action.btn-danger {
            color: #e53e3e;
            border-color: #e53e3e;
        }
        
        .btn-action.btn-danger:hover {
            background: #fed7d7;
        }
        
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }
        
        .modal-content {
            position: relative;
            margin: 5% auto;
            width: 90%;
            max-width: 800px;
            text-align: center;
        }
        
        .modal-content img {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 8px;
        }
        
        .close {
            position: absolute;
            top: -40px;
            right: 0;
            font-size: 30px;
            color: white;
            cursor: pointer;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 1001;
            animation: slideIn 0.3s ease;
        }
        
        .notification.success {
            border-left: 4px solid #38a169;
            color: #38a169;
        }
        
        .notification.error {
            border-left: 4px solid #e53e3e;
            color: #e53e3e;
        }
        
        .notification button {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            margin-left: 1rem;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @media (max-width: 768px) {
            .upload-grid {
                grid-template-columns: 1fr;
            }
            
            .files-grid {
                grid-template-columns: 1fr;
            }
            
            .category-filters {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>

<?php
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = array('Bytes', 'KB', 'MB', 'GB');
    $i = floor(log($bytes) / log($k));
    return round(($bytes / pow($k, $i)), 2) . ' ' . $sizes[$i];
}
?>