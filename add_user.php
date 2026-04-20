<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'config.php';

$success = "";
$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staff_id   = trim($_POST['staff_id']);
    $full_name  = trim($_POST['full_name']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $department = trim($_POST['department']);
    
    // Validate inputs
    if (empty($staff_id) || empty($full_name) || empty($email) || empty($password) || empty($department)) {
        $error = "❌ Please fill in all required fields!";
    } else {
        // ✅ Server-side password strength validation
        $pw_errors = [];
        if (strlen($password) < 8)                     $pw_errors[] = "at least 8 characters";
        if (!preg_match('/[A-Z]/', $password))         $pw_errors[] = "one uppercase letter";
        if (!preg_match('/[a-z]/', $password))         $pw_errors[] = "one lowercase letter";
        if (!preg_match('/[0-9]/', $password))         $pw_errors[] = "one number";
        if (!preg_match('/[\W_]/', $password))         $pw_errors[] = "one special character (!@#$%...)";

        if (!empty($pw_errors)) {
            $error = "❌ Password must contain: " . implode(", ", $pw_errors) . ".";
        } else {
            // Check if staff_id or email already exists
            $check_sql = "SELECT user_id FROM users WHERE staff_id = ? OR email = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "ss", $staff_id, $email);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            
            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $error = "❌ Staff ID or Email already exists!";
            } else {
                // ✅ Use password_hash instead of md5
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // Insert new user with status = 'active' (NO approval needed)
                $insert_sql = "INSERT INTO users (staff_id, full_name, email, password, department, role, status, created_at) 
                              VALUES (?, ?, ?, ?, ?, 'staff', 'active', NOW())";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, "sssss", 
                    $staff_id, 
                    $full_name, 
                    $email, 
                    $hashed_password, 
                    $department
                );
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    $success = "✅ User added successfully! User can login immediately.";
                    // Clear form
                    $staff_id = $full_name = $email = $password = $department = "";
                } else {
                    $error = "❌ Error adding user. Please try again.";
                }
                mysqli_stmt_close($insert_stmt);
            }
            mysqli_stmt_close($check_stmt);
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMURAS - Add New User</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 500px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo img {
            width: 150px;
            height: auto;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 22px;
            margin-bottom: 5px;
            margin-top: 10px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .note {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #0c5460;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
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
        
        .required {
            color: #dc3545;
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
        
        .form-group input[readonly] {
            background: #e9ecef;
            cursor: not-allowed;
        }
        
        .btn-register {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-register:hover {
            background: #871b23;
        }
        
        .signin-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .signin-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .signin-link a:hover {
            text-decoration: underline;
        }

        /* ── Password enhancements ── */
        .password-wrapper {
            position: relative;
        }
        .password-wrapper input {
            padding-right: 42px;
        }
        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            color: #888;
            font-size: 16px;
            line-height: 1;
        }
        .toggle-pw:hover { color: #667eea; }

        /* Strength bar */
        .strength-bar-wrap {
            display: flex;
            gap: 4px;
            margin-top: 8px;
        }
        .strength-segment {
            flex: 1;
            height: 5px;
            border-radius: 3px;
            background: #e0e0e0;
            transition: background 0.3s;
        }
        .strength-label {
            font-size: 11px;
            margin-top: 4px;
            font-weight: 600;
        }

        /* Checklist */
        .pw-checklist {
            list-style: none;
            margin-top: 8px;
            padding: 8px 10px;
            background: #f8f8f8;
            border-radius: 5px;
            font-size: 12px;
        }
        .pw-checklist li {
            padding: 2px 0;
            color: #999;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }
        .pw-checklist li.pass { color: #3a3; }
        .pw-checklist li::before {
            content: '✗';
            font-weight: bold;
            color: #ccc;
        }
        .pw-checklist li.pass::before { content: '✓'; color: #3a3; }

        /* Match indicator */
        .match-msg {
            font-size: 12px;
            margin-top: 5px;
            font-weight: 500;
        }
        .match-msg.ok   { color: #3a3; }
        .match-msg.fail { color: #c33; }
    </style>
</head>
<body>
    
<div class="register-container">
    <div class="logo">
        <img src="img/Politeknik-Mukah.png" alt="Politeknik Mukah Logo">
        <h1>Add New User</h1>
        <p>Create staff account (Admin)</p>
    </div>
    
    <div class="note">
        <strong>ℹ️ Note:</strong> Users created by admin are automatically activated and can login immediately.
    </div>
    
    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" id="addUserForm">
        
        <div class="form-group">
            <label>Staff ID <span class="required">*</span></label>
            <input type="text" name="staff_id" 
                   value="<?php echo isset($staff_id) ? htmlspecialchars($staff_id) : ''; ?>"
                   placeholder="e.g. 12345"
                   pattern="[0-9]+" 
                   inputmode="numeric"
                   oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                   title="Numbers only" required>
        </div>
        
        <div class="form-group">
            <label>Full Name <span class="required">*</span></label>
            <input type="text" name="full_name" 
                   value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>"
                   placeholder="e.g. John Doe" required>
        </div>
        
        <div class="form-group">
            <label>Email Address <span class="required">*</span></label>
            <input type="email" name="email" 
                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                   placeholder="use @pmu.edu.my" required>
        </div>
        
        <div class="form-group">
            <label>Department <span class="required">*</span></label>
            <select name="department" required>
                <option value="">Select Department</option>
                <option value="Jabatan Kejurutetraan Awam">Jabatan Kejurutetraan Awam</option>
                <option value="Jabatan Kejuruteraan Elektrik">Jabatan Kejuruteraan Elektrik</option>
                <option value="Jabatan Kejuruteraan Mekanikal">Jabatan Kejuruteraan Mekanikal</option>
                <option value="Jabatan Perdagangan">Jabatan Perdagangan</option>
                <option value="Jabatan Teknologi Maklumat dan Komunikasi">Jabatan Teknologi Maklumat dan Komunikasi</option>
                <option value="Jabatan Matematik, Sains dan Komputer">Jabatan Matematik, Sains dan Komputer</option>
                <option value="Jabatan Pengajian Am">Jabatan Pengajian Am</option>
                <option value="Jabatan Hal Ehwal dan Pembangunan">Jabatan Hal Ehwal dan Pembangunan</option>
                <option value="Jabatan Sukan, Kokurikulum dan Kebudayaan">Jabatan Sukan, Kokurikulum dan Kebudayaan</option>
                <option value="Unit Peperiksaan">Unit Peperiksaan</option>
                <option value="Unit Perhubungan dan Latihan Industri">Unit Perhubungan dan Latihan Industri</option>
                <option value="Unit Pengurusan Aset">Unit Pengurusan Aset</option>
                <option value="Unit Pengurusan Psikologi">Unit Pengurusan Psikologi</option>
                <option value="Unit Pembangunan Instruksional dan Multimedia">Unit Pembangunan Instruksional dan Multimedia</option>
                <option value="Unit Latihan dan Pendidikan Lanjutan">Unit Latihan dan Pendidikan Lanjutan</option>
                <option value="Unit Teknologi Maklumat">Unit Teknologi Maklumat</option>
                <option value="Unit Pusat Sumber">Unit Pusat Sumber</option>
                <option value="Unit Komunikasi Korporat">Unit Komunikasi Korporat</option>
                <option value="Unit Pembangunan dan Penyelenggaraan Fasiliti">Unit Pembangunan dan Penyelenggaraan Fasiliti</option>
                <option value="Unit Pengurusan Kolej Kediaman">Unit Pengurusan Kolej Kediaman</option>
                <option value="Unit Khidmat Pengurusan">Unit Khidmat Pengurusan</option>
                <option value="Unit Kewangan dan Akaun">Unit Kewangan dan Akaun</option>
            </select>
        </div>

        <!-- ── Password with show/hide + strength ── -->
        <div class="form-group">
            <label>Password <span class="required">*</span></label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password"
                       placeholder="Min. 8 characters" autocomplete="new-password" required
                       oninput="checkStrength(this.value); checkMatch();">
                <button type="button" class="toggle-pw" onclick="toggleVis('password', this)" title="Show/Hide">👁</button>
            </div>

            <!-- Strength bar: 4 segments -->
            <div class="strength-bar-wrap" id="strengthBar">
                <div class="strength-segment" id="seg1"></div>
                <div class="strength-segment" id="seg2"></div>
                <div class="strength-segment" id="seg3"></div>
                <div class="strength-segment" id="seg4"></div>
            </div>
            <div class="strength-label" id="strengthLabel" style="color:#aaa;">Enter a password</div>

            <!-- Requirements checklist -->
            <ul class="pw-checklist" id="pwChecklist">
                <li id="chk-len">At least 8 characters</li>
                <li id="chk-upper">One uppercase letter (A–Z)</li>
                <li id="chk-lower">One lowercase letter (a–z)</li>
                <li id="chk-num">One number (0–9)</li>
                <li id="chk-sym">One special character (!@#$%^&amp;*...)</li>
            </ul>
        </div>

        <!-- ── Confirm Password ── -->
        <div class="form-group">
            <label>Confirm Password <span class="required">*</span></label>
            <div class="password-wrapper">
                <input type="password" name="confirm_password" id="confirm_password"
                       placeholder="Re-enter password" autocomplete="new-password" required
                       oninput="checkMatch();">
                <button type="button" class="toggle-pw" onclick="toggleVis('confirm_password', this)" title="Show/Hide">👁</button>
            </div>
            <div class="match-msg" id="matchMsg"></div>
        </div>
        
        <button type="submit" class="btn-register">Create User</button>
    </form>
    
    <div class="signin-link">
        <a href="admin_interface.php?page=user_management">← Back to User Management</a>
    </div>
</div>

<script>
// ── Toggle show/hide password ──
function toggleVis(fieldId, btn) {
    const field = document.getElementById(fieldId);
    const isHidden = field.type === 'password';
    field.type = isHidden ? 'text' : 'password';
    btn.textContent = isHidden ? '🙈' : '👁';
}

// ── Password strength checker ──
function checkStrength(pw) {
    const rules = {
        len:   pw.length >= 8,
        upper: /[A-Z]/.test(pw),
        lower: /[a-z]/.test(pw),
        num:   /[0-9]/.test(pw),
        sym:   /[\W_]/.test(pw)
    };

    toggle('chk-len',   rules.len);
    toggle('chk-upper', rules.upper);
    toggle('chk-lower', rules.lower);
    toggle('chk-num',   rules.num);
    toggle('chk-sym',   rules.sym);

    const score = Object.values(rules).filter(Boolean).length;

    const colors      = ['#e0e0e0', '#e74c3c', '#e67e22', '#f1c40f', '#27ae60'];
    const labels      = ['', 'Very Weak', 'Weak', 'Fair', 'Strong'];
    const labelColors = ['#aaa', '#e74c3c', '#e67e22', '#d4a017', '#27ae60'];

    for (let i = 1; i <= 4; i++) {
        document.getElementById('seg' + i).style.background =
            i <= score ? colors[score] : '#e0e0e0';
    }

    const lbl = document.getElementById('strengthLabel');
    lbl.textContent = pw.length === 0 ? 'Enter a password' : (labels[score] || 'Strong');
    lbl.style.color  = pw.length === 0 ? '#aaa' : (labelColors[score] || '#27ae60');
}

function toggle(id, pass) {
    const el = document.getElementById(id);
    if (pass) el.classList.add('pass'); else el.classList.remove('pass');
}

// ── Password match checker ──
function checkMatch() {
    const pw  = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_password').value;
    const msg = document.getElementById('matchMsg');

    if (cpw.length === 0) { msg.textContent = ''; return; }

    if (pw === cpw) {
        msg.textContent = '✓ Passwords match';
        msg.className   = 'match-msg ok';
    } else {
        msg.textContent = '✗ Passwords do not match';
        msg.className   = 'match-msg fail';
    }
}

// ── Prevent submit if weak or mismatched ──
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    const pw  = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_password').value;

    const rules = [
        pw.length >= 8,
        /[A-Z]/.test(pw),
        /[a-z]/.test(pw),
        /[0-9]/.test(pw),
        /[\W_]/.test(pw)
    ];
    const score = rules.filter(Boolean).length;

    if (score < 4) {
        e.preventDefault();
        alert('Please make the password stronger before submitting.');
        return;
    }
    if (pw !== cpw) {
        e.preventDefault();
        alert('Passwords do not match!');
    }
});
</script>

</body>
</html>