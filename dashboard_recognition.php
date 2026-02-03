<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

$success_message = "";
$error_message = "";
$current_file = "";

// Load user's saved default data
$user_data_file = 'user_defaults.json';
$user_defaults = [];

if (file_exists($user_data_file)) {
    $all_defaults = json_decode(file_get_contents($user_data_file), true);
    if (isset($all_defaults[$user_id])) {
        $user_defaults = $all_defaults[$user_id];
    }
}

// Handle Save button - Save default user data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_defaults'])) {
    $name = trim($_POST['name']);
    $department = trim($_POST['department']);
    
    // Load all user defaults
    $all_defaults = [];
    if (file_exists($user_data_file)) {
        $all_defaults = json_decode(file_get_contents($user_data_file), true);
    }
    
    // Save this user's defaults
    $all_defaults[$user_id] = [
        'name' => $name,
        'department' => $department,
        'saved_at' => date('Y-m-d H:i:s')
    ];
    
    if (file_put_contents($user_data_file, json_encode($all_defaults, JSON_PRETTY_PRINT))) {
        $success_message = "Default data saved successfully! Your information will be auto-filled next time.";
        $user_defaults = $all_defaults[$user_id];
    } else {
        $error_message = "Error saving default data.";
    }
}

// Handle file removal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_file'])) {
    if (isset($_SESSION['temp_file']) && file_exists($_SESSION['temp_file'])) {
        unlink($_SESSION['temp_file']);
        unset($_SESSION['temp_file']);
        $success_message = "File removed successfully!";
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_recognition'])) {
    // Get form data
    $name = trim($_POST['name']);
    $department = trim($_POST['department']);
    $category = $_POST['category'];
    $program_name = trim($_POST['program_name']);
    $recognition_type = $_POST['recognition_type'];
    $date = $_POST['date'];
    $level = $_POST['level'];
    
    // Handle file upload
    $file_uploaded = false;
    $file_path = "";
    
    // Check if there's already a file in session
    if (isset($_SESSION['temp_file']) && file_exists($_SESSION['temp_file'])) {
        $file_uploaded = true;
        $file_path = $_SESSION['temp_file'];
    }
    
    // Handle new file upload
    if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
        // Check file type - Only PDF allowed
        $file_type = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
        $file_size = $_FILES['document']['size'];
        $max_size = 2 * 1024 * 1024; // 2MB in bytes
        
        if ($file_type != 'pdf') {
            $error_message = "Only PDF files are allowed!";
        } elseif ($file_size > $max_size) {
            $error_message = "File size must not exceed 2MB!";
        } else {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Remove old temp file if exists
            if (isset($_SESSION['temp_file']) && file_exists($_SESSION['temp_file'])) {
                unlink($_SESSION['temp_file']);
            }
            
            $file_name = time() . '_' . basename($_FILES['document']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['document']['tmp_name'], $target_file)) {
                $file_uploaded = true;
                $file_path = $target_file;
                $_SESSION['temp_file'] = $target_file;
            } else {
                $error_message = "Error uploading file. Please try again.";
            }
        }
    }
    
    // Save to JSON file only if no errors
    if (empty($error_message)) {
        $submissions_file = 'submissions.json';
        $submissions = [];
        
        if (file_exists($submissions_file)) {
            $submissions = json_decode(file_get_contents($submissions_file), true);
        }
        
        $new_submission = [
            'id' => time(),
            'user_id' => $user_id,
            'submitted_by' => $full_name,
            'name' => $name,
            'department' => $department,
            'category' => $category,
            'program_name' => $program_name,
            'recognition_type' => $recognition_type,
            'date' => $date,
            'level' => $level,
            'document' => $file_path,
            'submitted_at' => date('Y-m-d H:i:s')
        ];
        
        $submissions[] = $new_submission;
        
        if (file_put_contents($submissions_file, json_encode($submissions, JSON_PRETTY_PRINT))) {
            $success_message = "Recognition submitted successfully!";
            // Clear temp file from session after successful submission
            unset($_SESSION['temp_file']);
        } else {
            $error_message = "Error saving submission. Please try again.";
        }
    }
}

// Check if there's a temp file for display
if (isset($_SESSION['temp_file']) && file_exists($_SESSION['temp_file'])) {
    $current_file = $_SESSION['temp_file'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PMURAS - Recognition Form</title>

<style>
* { 
    margin: 0; 
    padding: 0; 
    box-sizing: border-box; 
}

body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    background: linear-gradient(135deg, #0a0015 0%, #1a1a40 100%);
    min-height: 100vh;
    padding: 20px;
    position: relative;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
}

/* Animated network background */
.network-bg {
    position: fixed;
    top: 0;
    right: 0;
    width: 50%;
    height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800"><defs><linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:rgb(59,130,246);stop-opacity:0.1" /><stop offset="100%" style="stop-color:rgb(139,92,246);stop-opacity:0.1" /></linearGradient></defs><path d="M100,100 L700,100 L700,700 L100,700 Z" fill="none" stroke="url(%23grad)" stroke-width="1"/><circle cx="100" cy="100" r="3" fill="rgb(139,92,246)"/><circle cx="700" cy="100" r="3" fill="rgb(59,130,246)"/><circle cx="700" cy="700" r="3" fill="rgb(139,92,246)"/><circle cx="100" cy="700" r="3" fill="rgb(59,130,246)"/><line x1="100" y1="100" x2="700" y2="700" stroke="url(%23grad)" stroke-width="0.5"/><line x1="700" y1="100" x2="100" y2="700" stroke="url(%23grad)" stroke-width="0.5"/></svg>') no-repeat center;
    background-size: contain;
    opacity: 0.3;
    z-index: 0;
    pointer-events: none;
}

/* Top navigation */
.top-nav {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 30px;
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 1400px;
}

.nav-links {
    display: flex;
    gap: 20px;
    color: white;
    font-size: 14px;
}

.nav-links a {
    color: white;
    text-decoration: none;
    transition: color 0.3s;
}

.nav-links a:hover {
    color: #8b5cf6;
}

.nav-links span {
    color: rgba(255, 255, 255, 0.5);
}

.container {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    gap: 30px;
    max-width: 1400px;
    width: 100%;
    margin: 0 auto;
    position: relative;
    z-index: 10;
}

/* Form Card */
.form-card {
    background: rgba(30, 30, 80, 0.7);
    backdrop-filter: blur(20px);
    border: 2px solid rgba(139, 92, 246, 0.4);
    border-radius: 30px;
    padding: 50px 60px;
    max-width: 800px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

.form-group {
    margin-bottom: 28px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.form-group label {
    color: white;
    font-size: 15px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 14px 20px;
    border-radius: 12px;
    border: 2px solid rgba(139, 92, 246, 0.3);
    background: rgba(255, 255, 255, 0.95);
    font-size: 15px;
    transition: all 0.3s;
    color: #333;
}

.form-group input::placeholder {
    color: #999;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #8b5cf6;
    background: white;
    box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
    transform: translateY(-2px);
}

.form-group input[type="date"] {
    position: relative;
    cursor: pointer;
}

.upload-container {
    display: flex;
    align-items: center;
    gap: 15px;
}

.upload-btn {
    padding: 12px 30px;
    background: linear-gradient(135deg, #dc2626, #ef4444);
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    font-size: 15px;
}

.upload-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(220, 38, 38, 0.5);
    background: linear-gradient(135deg, #b91c1c, #dc2626);
}

.file-info {
    color: rgba(255, 255, 255, 0.8);
    font-size: 13px;
}

/* File Preview Card */
.file-preview {
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(139, 92, 246, 0.4);
    border-radius: 12px;
    padding: 15px 20px;
    margin-top: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.file-preview-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.file-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: white;
    font-weight: bold;
}

.file-details {
    flex: 1;
}

.file-name {
    color: white;
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 3px;
    word-break: break-all;
}

.file-size {
    color: rgba(255, 255, 255, 0.6);
    font-size: 12px;
}

.file-actions {
    display: flex;
    gap: 10px;
}

.btn-view, .btn-remove {
    padding: 8px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.3s;
}

.btn-view {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.btn-view:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(59, 130, 246, 0.5);
}

.btn-remove {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.btn-remove:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(239, 68, 68, 0.5);
}

/* Form actions button styling */
.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 50px;
    justify-content: center;
}

.btn {
    padding: 16px 45px;
    border-radius: 50px;
    border: none;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.btn-save {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
}

.btn-edit {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
    box-shadow: 0 5px 15px rgba(139, 92, 246, 0.4);
}

.btn-submit {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
}

.btn:hover:not(:disabled) {
    transform: translateY(-4px);
    box-shadow: 0 15px 35px rgba(59, 130, 246, 0.6);
}

.btn-save:hover:not(:disabled) {
    box-shadow: 0 15px 35px rgba(59, 130, 246, 0.7);
}

.btn-edit:hover:not(:disabled) {
    box-shadow: 0 15px 35px rgba(139, 92, 246, 0.7);
}

.btn-submit:hover:not(:disabled) {
    box-shadow: 0 15px 35px rgba(16, 185, 129, 0.7);
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.field-locked {
    background: rgba(200, 200, 200, 0.95) !important;
    cursor: not-allowed !important;
}

.info-text {
    color: rgba(255, 255, 255, 0.7);
    font-size: 13px;
    margin-top: 10px;
    text-align: center;
}

/* Dropdown Guide */
.dropdown-guide {
    display: none; /* Hidden to keep form centered and clean */
    background: rgba(30, 30, 80, 0.6);
    backdrop-filter: blur(20px);
    border: 2px solid rgba(139, 92, 246, 0.3);
    border-radius: 30px;
    padding: 30px;
}

.dropdown-title {
    color: white;
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 25px;
    text-align: center;
}

.dropdown-section {
    margin-bottom: 30px;
}

.dropdown-section h3 {
    color: #8b5cf6;
    font-size: 18px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dropdown-section select {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: 2px solid rgba(139, 92, 246, 0.5);
    background: white;
    font-size: 15px;
    margin-bottom: 10px;
    cursor: pointer;
}

.dropdown-options {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 10px;
}

.dropdown-options div {
    padding: 8px 12px;
    color: white;
    border-radius: 5px;
    margin-bottom: 5px;
    background: rgba(255, 255, 255, 0.1);
    font-size: 14px;
}

.success-message {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 15px 20px;
    border-radius: 15px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 600;
}

.error-message {
    background: linear-gradient(135deg, #dc2626, #ef4444);
    color: white;
    padding: 15px 20px;
    border-radius: 15px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 1200px) {
    .container {
        flex-direction: column;
        align-items: center;
    }
    
    .form-card {
        max-width: 100%;
    }
}

@media (max-width: 768px) {
    .form-card {
        padding: 30px 25px;
    }
    
    .form-group label {
        font-size: 14px;
    }
    
    .form-group input,
    .form-group select {
        padding: 12px 16px;
        font-size: 14px;
    }
    
    .form-actions {
        flex-direction: column;
        gap: 12px;
    }
    
    .btn {
        width: 100%;
        padding: 14px 30px;
    }
    
    .upload-container {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
</head>

<body>

<!-- Network Background -->
<div class="network-bg"></div>

<!-- Top Navigation -->
<div class="top-nav">
    <div class="nav-links">
        <a href="review_submission.php">Review Submission</a>
        <span>|</span>
        <a href="dashboard_main.php">Main Page</a>
        <span>|</span>
        <a href="logout.php">Logout</a>
    </div>
</div>

<!-- Main Container -->
<div class="container">
    
    <!-- Form Card -->
    <div class="form-card">
        
        <?php if ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="recognitionForm">
            
            <div class="form-group">
                <label>Name :</label>
                <input type="text" name="name" id="name" required placeholder="Enter name" 
                       value="<?php echo isset($user_defaults['name']) ? htmlspecialchars($user_defaults['name']) : ''; ?>"
                       <?php echo isset($user_defaults['name']) ? 'readonly class="field-locked"' : ''; ?>>
            </div>
            
            <div class="form-group">
                <label>Department :</label>
                <input type="text" name="department" id="department" required placeholder="Enter department"
                       value="<?php echo isset($user_defaults['department']) ? htmlspecialchars($user_defaults['department']) : ''; ?>"
                       <?php echo isset($user_defaults['department']) ? 'readonly class="field-locked"' : ''; ?>>
            </div>
            
            <?php if (isset($user_defaults['name'])): ?>
                <p class="info-text">💡 Your default information is auto-filled. Click "Edit" to modify, or continue with other fields.</p>
            <?php endif; ?>
            
            <div class="form-group">
                <label>Category :</label>
                <select name="category" required>
                    <option value="">Select Category</option>
                    <option value="Recognition">Recognition</option>
                    <option value="Achievement">Achievement</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Program Name :</label>
                <input type="text" name="program_name" required placeholder="Enter program name">
            </div>
            
            <div class="form-group">
                <label>Recognition :</label>
                <select name="recognition_type" required>
                    <option value="">Select Type</option>
                    <option value="Awards">Awards</option>
                    <option value="Certificate">Certificate</option>
                    <option value="Letter of Appreciation">Letter of Appreciation</option>
                    <option value="Medal">Medal</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Date :</label>
                <input type="date" name="date" required>
            </div>
            
            <div class="form-group">
                <label>Level :</label>
                <select name="level" required>
                    <option value="">Select Level</option>
                    <option value="International">International</option>
                    <option value="National">National</option>
                    <option value="State">State</option>
                    <option value="Institution">Institution</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Document upload :</label>
                
                <?php if ($current_file): ?>
                    <!-- File Preview Card -->
                    <div class="file-preview">
                        <div class="file-preview-info">
                            <div class="file-icon">📄</div>
                            <div class="file-details">
                                <div class="file-name"><?php echo htmlspecialchars(basename($current_file)); ?></div>
                                <div class="file-size"><?php echo number_format(filesize($current_file) / 1024, 2); ?> KB</div>
                            </div>
                        </div>
                        <div class="file-actions">
                            <button type="button" class="btn-view" onclick="viewPDF('view_pdf.php?file=<?php echo urlencode($current_file); ?>')">View</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this file?');">
                                <button type="submit" name="remove_file" class="btn-remove">Remove</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Upload Section -->
                    <div class="upload-container">
                        <label for="file-upload" class="upload-btn">Upload</label>
                        <input id="file-upload" type="file" name="document" style="display: none;" accept=".pdf" onchange="this.form.submit()">
                        <span class="file-info">PDF only, max 2MB</span>
                    </div>
                <?php endif; ?>
                
                <span id="file-name" style="color: rgba(255, 255, 255, 0.7); font-size: 13px; margin-top: 5px;"></span>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="save_defaults" class="btn btn-save" id="saveBtn">
                    💾 Save Defaults
                </button>
                <button type="button" class="btn btn-edit" id="editBtn" onclick="enableEditing()">
                    ✏️ Edit
                </button>
                <button type="submit" name="submit_recognition" class="btn btn-submit">
                    ✓ Submit
                </button>
            </div>
            
        </form>
    </div>
    
    <!-- Dropdown Guide -->
    <div class="dropdown-guide">
        <div class="dropdown-title">DropDown</div>
        
        <!-- Category Dropdown -->
        <div class="dropdown-section">
            <h3>PMURAS :</h3>
            <select>
                <option>Recognition</option>
                <option>Achievement</option>
            </select>
            <div class="dropdown-options">
                <div>Recognition</div>
                <div>Achievement</div>
            </div>
        </div>
        
        <!-- Recognition Type Dropdown -->
        <div class="dropdown-section">
            <h3>Recognition :</h3>
            <select>
                <option>Awards</option>
                <option>Certificate</option>
            </select>
            <div class="dropdown-options">
                <div>Awards</div>
                <div>Certificate</div>
            </div>
        </div>
        
        <!-- Level Dropdown -->
        <div class="dropdown-section">
            <h3>Level :</h3>
            <select>
                <option>International</option>
                <option>National</option>
                <option>State</option>
            </select>
            <div class="dropdown-options">
                <div>International</div>
                <div>National</div>
                <div>State</div>
            </div>
        </div>
        
    </div>

</div>

<script>
// Enable editing for locked fields
function enableEditing() {
    const nameField = document.getElementById('name');
    const deptField = document.getElementById('department');
    const editBtn = document.getElementById('editBtn');
    
    if (nameField && deptField) {
        nameField.removeAttribute('readonly');
        nameField.classList.remove('field-locked');
        deptField.removeAttribute('readonly');
        deptField.classList.remove('field-locked');
        
        // Change button text
        editBtn.textContent = '✓ Editing Enabled';
        editBtn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
        editBtn.disabled = true;
        
        // Focus on name field
        nameField.focus();
    }
}

// Check if fields are filled to enable/disable Save button
function checkSaveButton() {
    const nameField = document.getElementById('name');
    const deptField = document.getElementById('department');
    const saveBtn = document.getElementById('saveBtn');
    
    if (nameField && deptField && saveBtn) {
        if (nameField.value.trim() && deptField.value.trim()) {
            saveBtn.disabled = false;
        } else {
            saveBtn.disabled = true;
        }
    }
}

// Add event listeners
document.addEventListener('DOMContentLoaded', function() {
    const nameField = document.getElementById('name');
    const deptField = document.getElementById('department');
    
    if (nameField) {
        nameField.addEventListener('input', checkSaveButton);
    }
    if (deptField) {
        deptField.addEventListener('input', checkSaveButton);
    }
    
    // Initial check
    checkSaveButton();
});

// View PDF in new tab
function viewPDF(filePath) {
    window.open(filePath, '_blank');
}

// Show selected file name and validate
document.getElementById('file-upload')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    const fileNameDisplay = document.getElementById('file-name');
    
    if (file) {
        // Check file type
        const fileType = file.name.split('.').pop().toLowerCase();
        if (fileType !== 'pdf') {
            alert('Error: Only PDF files are allowed!');
            this.value = ''; // Clear the selection
            if (fileNameDisplay) fileNameDisplay.textContent = '';
            return false;
        }
        
        // Check file size (2MB = 2 * 1024 * 1024 bytes)
        const maxSize = 2 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('Error: File size must not exceed 2MB!');
            this.value = ''; // Clear the selection
            if (fileNameDisplay) fileNameDisplay.textContent = '';
            return false;
        }
        
        // If validation passes, the form will auto-submit
        if (fileNameDisplay) {
            const fileSizeKB = (file.size / 1024).toFixed(2);
            fileNameDisplay.textContent = '⏳ Uploading: ' + file.name + ' (' + fileSizeKB + ' KB)';
            fileNameDisplay.style.color = '#f59e0b';
        }
    }
});
</script>

</body>
</html>