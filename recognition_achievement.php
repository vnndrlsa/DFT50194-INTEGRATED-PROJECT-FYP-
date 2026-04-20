<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role'] == 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMURAS - Recognition & Achievement</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1f0021f0 0%, #d3321d 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .header-links {
            color: white;
        }
        
        .header-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }
        
        .content-box {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .content-box h2 {
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .submission-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .submission-table th,
        .submission-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .submission-table th {
            background: #667eea;
            color: white;
            font-weight: 500;
        }
        
        .submission-table tr:hover {
            background: #f5f5f5;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
            font-size: 14px;
        }
        
        .btn-view {
            background: #2196F3;
            color: white;
        }
        
        .btn-approved {
            background: #4CAF50;
            color: white;
        }
        
        .btn-rejected {
            background: #f44336;
            color: white;
        }
        
        .status-pending {
            background: #ff9800;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .status-approved {
            background: #4CAF50;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .status-rejected {
            background: #f44336;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        /* ✅ Placeholder text - same dark color as normal text */
        .form-group input::placeholder {
            color: #666;
            opacity: 1;
            font-style: italic;
        }
        
        .btn-submit {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-save {
            background: #4CAF50;
            color: white;
        }
        
        .btn-edit {
            background: #ff9800;
            color: white;
        }

        /* ✅ ADDED: Success Popup Overlay */
        .popup-overlay {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
        }

        .popup-overlay.show {
            display: flex;
        }

        .popup-box {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            text-align: center;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: popIn 0.4s ease;
        }

        @keyframes popIn {
            from { transform: scale(0.7); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        .popup-icon {
            font-size: 70px;
            margin-bottom: 15px;
        }

        .popup-box h2 {
            color: #4CAF50;
            font-size: 26px;
            margin-bottom: 12px;
        }

        .popup-box p {
            color: #666;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .popup-box p span {
            color: #667eea;
            font-weight: 700;
        }

        .popup-btn {
            display: inline-block;
            padding: 13px 35px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            margin: 5px;
        }

        .popup-btn:hover {
            background: #5568d3;
        }

        .popup-btn.secondary {
            background: #f1f1f1;
            color: #555;
        }

        /* ✅ ADDED: Save Popup */
        .popup-box.saved h2 {
            color: #ff9800;
        }
        
      /* ── LOGO ── */
        .logo-header {
            display: flex;
            position: center;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: transparent;
            padding: 10px;
        }

        /* ✏️ Tukar height ikut saiz logo anda */
        .logo-img {
            height: 70px;
            width: auto;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.4));
    }   
        
    </style>
</head>
<body>

<!-- ── LOGO HEADER ── -->
    <div class="logo-header">
        <img src="img/Politeknik-Mukah.png"
             alt="Politeknik Malaysia Logo"
             class="logo-img"
             onerror="this.style.display='none';">
    </div>
    
    
    <?php 
    // Clear session messages without showing them (we have our own popup)
    if (isset($_SESSION['success_message'])) unset($_SESSION['success_message']);
    if (isset($_SESSION['alert_type'])) unset($_SESSION['alert_type']);
    if (isset($_SESSION['error_message'])) unset($_SESSION['error_message']);
    ?>
    
    <div class="header">
        <div></div>
        <div class="header-links">
            <a href="view_my_submissions.php">View Submissions</a>
            <?php if ($is_admin): ?>
                <a href="mainAdmin_dashboard.php">Main Page</a>
            <?php else: ?>
                <a href="staff_dashboard.php">Main Page</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="content-box">
        <h2>Recognition & Achievement - Submission</h2>
        
        <div class="form-container">
            <form id="submissionForm" action="process_submission.php" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Department:</label>
                    <input type="text" name="department" value="<?php echo htmlspecialchars($_SESSION['department']); ?>" readonly>
                </div>
                
                <!-- ✅ CHANGED: Category sorted Recognition > Achievement > Others -->
                <div class="form-group">
                    <label>Type of Recognition/Achievement:</label>
                    <select name="category" required>
                        <option value="">Select category</option>
                        <?php
                        $categories_sql = "SELECT * FROM categories ORDER BY 
                            CASE 
                                WHEN LOWER(category_name) LIKE '%recognition%' THEN 1
                                WHEN LOWER(category_name) LIKE '%achievement%' THEN 2
                                ELSE 3
                            END, category_name";
                        $categories_result = mysqli_query($conn, $categories_sql);
                        while ($cat = mysqli_fetch_assoc($categories_result)):
                        ?>
                        <option value="<?php echo htmlspecialchars(!empty($cat['category']) ? $cat['category'] : $cat['category_name']); ?>">
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Program Name:</label>
                    <input type="text" 
                           name="program_name" 
                           placeholder="Contoh: Anugerah PolyCC, NETFPA 2026, Pertandingan Inovasi" 
                           required>
                </div>
                
                <div class="form-group">
                    <label>Type of Category:</label>
                    <select name="type_of_category" required>
                        <option value="">Select type of category</option>
                        <?php
                        $category_types_sql = "SELECT * FROM category_types ORDER BY category_type_name";
                        $category_types_result = mysqli_query($conn, $category_types_sql);
                        while ($cat_type = mysqli_fetch_assoc($category_types_result)):
                        ?>
                        <option value="<?php echo htmlspecialchars($cat_type['category_type_name']); ?>">
                            <?php echo htmlspecialchars($cat_type['category_type_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Date:</label>
                    <input type="date" name="date" required>
                </div>
                
                <div class="form-group">
                    <label>Level:</label>
                    <select name="level" required>
                        <option value="">Select Level</option>
                        <?php
                        $levels_sql = "SELECT * FROM levels ORDER BY level_name";
                        $levels_result = mysqli_query($conn, $levels_sql);
                        while ($level = mysqli_fetch_assoc($levels_result)):
                        ?>
                        <option value="<?php echo htmlspecialchars($level['level_name']); ?>">
                            <?php echo htmlspecialchars($level['level_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Document Upload: (PDF only, Max 2MB)</label>
                    <input type="file" name="document" accept=".pdf">
                </div>
                
                <!-- ✅ FIXED: Show popup first, then submit -->
                <button type="button" class="btn btn-submit btn-save" onclick="showPopupThenSubmit('save')">SAVE</button>
                <button type="button" class="btn btn-submit" onclick="showPopupThenSubmit('submit')">SUBMIT</button>
                
                <!-- Hidden input for action type -->
                <input type="hidden" name="action_type" id="action_type" value="">
            </form>
        </div>
    </div>

    <!-- ✅ Submit Success Popup -->
    <div class="popup-overlay" id="submitPopup">
        <div class="popup-box">
            <div class="popup-icon">🎉</div>
            <h2>Submission Successful!</h2>
            <p>
                Your submission is being sent!<br>
                You can view your submission at<br>
                <span>'My Submissions'</span> page.
            </p>
            <button class="popup-btn" onclick="closePopup('submitPopup')">OK</button>
        </div>
    </div>

    <!-- ✅ Save Success Popup -->
    <div class="popup-overlay" id="savePopup">
        <div class="popup-box saved">
            <div class="popup-icon">💾</div>
            <h2>Draft Saved!</h2>
            <p>
                Your draft is being saved!<br>
                You can view and edit your draft at<br>
                <span>'My Submissions'</span> page.
            </p>
            <button class="popup-btn" onclick="closePopup('savePopup')">OK</button>
        </div>
    </div>

    <script>
    function showPopupThenSubmit(type) {
        const form = document.querySelector('form');

        // ✅ ADDED: Validate file type FIRST — PDF only
        const fileInput = document.querySelector('input[type="file"]');
        if (fileInput && fileInput.files.length > 0) {
            const file = fileInput.files[0];

            // Check file type
            const allowedTypes = ['application/pdf'];
            const fileExtension = file.name.split('.').pop().toLowerCase();

            if (file.type !== 'application/pdf' || fileExtension !== 'pdf') {
                showFileError(
                    '📄',
                    'Wrong File Format!',
                    'Only PDF files are accepted for document upload.\n\nPlease make sure your document is saved as a PDF file (.pdf) and try again.'
                );
                return; // Stop here
            }

            // Check file size (Max 2MB)
            const maxSize = 2 * 1024 * 1024;
            if (file.size > maxSize) {
                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                showFileError(
                    '⚠️',
                    type === 'save' ? 'Save Failed!' : 'Submission Failed!',
                    'PDF file size (' + fileSizeMB + ' MB) exceeds the 2MB limit.\n\nPlease reduce the file size or upload a smaller PDF file and try again.'
                );
                return; // Stop here
            }
        }

        // Validate required fields
        const required = form.querySelectorAll('[required]');
        let valid = true;
        required.forEach(field => {
            if (!field.value) {
                field.style.border = '2px solid red';
                valid = false;
            } else {
                field.style.border = '';
            }
        });

        if (!valid) {
            alert('Please fill in all required fields!');
            return;
        }

        // Set action type
        document.getElementById('action_type').value = type;

        // Show appropriate popup
        if (type === 'submit') {
            document.getElementById('submitPopup').classList.add('show');
        } else {
            document.getElementById('savePopup').classList.add('show');
        }
    }

    function closePopup(id) {
        document.getElementById(id).classList.remove('show');
        // Submit form after popup closes
        document.querySelector('form').submit();
    }
    
    // ✅ UPDATED: Unified file error popup (replaces old showFileSizeError)
    function showFileError(icon, title, message) {
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; display: flex; align-items: center; justify-content: center;';
        
        const popup = document.createElement('div');
        popup.style.cssText = 'background: white; padding: 40px; border-radius: 20px; max-width: 480px; width: 90%; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: popIn 0.4s ease;';
        
        const iconDiv = document.createElement('div');
        iconDiv.style.cssText = 'font-size: 70px; margin-bottom: 15px;';
        iconDiv.textContent = icon;
        
        const titleDiv = document.createElement('div');
        titleDiv.style.cssText = 'font-size: 24px; font-weight: bold; color: #f44336; margin-bottom: 15px;';
        titleDiv.textContent = title;
        
        const messageDiv = document.createElement('div');
        messageDiv.style.cssText = 'font-size: 15px; color: #555; margin-bottom: 25px; white-space: pre-line; line-height: 1.8;';
        messageDiv.textContent = message;

        // Accepted formats hint
        const hintDiv = document.createElement('div');
        hintDiv.style.cssText = 'font-size: 13px; color: #999; margin-bottom: 20px;';
        hintDiv.textContent = 'Accepted format: PDF (.pdf) only';
        
        const button = document.createElement('button');
        button.textContent = 'Got it, I\'ll fix it!';
        button.style.cssText = 'padding: 12px 40px; background: #667eea; color: white; border: none; border-radius: 50px; font-size: 15px; font-weight: 700; cursor: pointer;';
        button.onmouseover = () => { button.style.background = '#5568d3'; };
        button.onmouseout = () => { button.style.background = '#667eea'; };
        button.onclick = () => {
            document.body.removeChild(overlay);
            // Clear file input so user can reselect
            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput) fileInput.value = '';
        };
        
        popup.appendChild(iconDiv);
        popup.appendChild(titleDiv);
        popup.appendChild(messageDiv);
        popup.appendChild(hintDiv);
        popup.appendChild(button);
        overlay.appendChild(popup);
        document.body.appendChild(overlay);
    }
    </script>
</body>
</html>