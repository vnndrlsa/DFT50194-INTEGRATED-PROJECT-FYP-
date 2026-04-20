<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'config.php';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch user data
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    header("Location: admin_interface.php?page=user_management");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staff_id = trim($_POST['staff_id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);
    $status = $_POST['status'];
    
    // Update user
    $update_sql = "UPDATE users SET staff_id = ?, full_name = ?, email = ?, department = ?, status = ? WHERE user_id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "sssssi", $staff_id, $full_name, $email, $department, $status, $user_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        header("Location: admin_interface.php?page=user_management&success=user_updated");
        exit();
    } else {
        $error = "Error updating user.";
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMURAS - Edit User</title>
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
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 30px;
            transition: background 0.3s;
        }
        
        .back-btn:hover {
            background: #5568d3;
        }
        
        h2 {
            color: #667eea;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .subtitle {
            color: #999;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 8px 20px;
            background: #4CAF50;
            color: white;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-row {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.5);
        }
        
        .btn-cancel {
            background: #f1f1f1;
            color: #555;
        }
        
        .btn-cancel:hover {
            background: #ddd;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
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
    <div class="container">
        <a href="admin_interface.php?page=user_management" class="back-btn">← Back to User Management</a>
        
        <h2>✏️ Edit User</h2>
        <p class="subtitle">Update user information below</p>
        
        <div>
            <strong>Role :</strong>
            <span class="role-badge"><?php echo strtoupper($user['role']); ?></span>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Staff ID</label>
                <input type="text" name="staff_id" value="<?php echo htmlspecialchars($user['staff_id']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Department</label>
                <!-- ✅ CHANGED: Dropdown instead of text input -->
                <select name="department" required>
                    <option value="">Select Department</option>
                    <?php
                    $all_departments = array(
                        "Jabatan Kejurutetraan Awam",
                        "Jabatan Kejuruteraan Elektrik",
                        "Jabatan Kejuruteraan Mekanikal",
                        "Jabatan Perdagangan",
                        "Jabatan Teknologi Maklumat dan Komunikasi",
                        "Jabatan Matematik, Sains dan Komputer",
                        "Jabatan Pengajian Am",
                        "Jabatan Hal Ehwal dan Pembangunan",
                        "Jabatan Sukan, Kokurikulum dan Kebudayaan",
                        "Unit Peperiksaan",
                        "Unit Perhubungan dan Latihan Industri",
                        "Unit Pengurusan Aset",
                        "Unit Pengurusan Psikologi",
                        "Unit Pembangunan Instruksional dan Multimedia",
                        "Unit Latihan dan Pendidikan Lanjutan",
                        "Unit Teknologi Maklumat",
                        "Unit Pusat Sumber",
                        "Unit Komunikasi Korporat",
                        "Unit Pembangunan dan Penyelenggaraan Fasiliti",
                        "Unit Pengurusan Kolej Kediaman",
                        "Unit Khidmat Pengurusan",
                        "Unit Kewangan dan Akaun"
                    );
                    
                    foreach ($all_departments as $dept):
                        $selected = ($user['department'] == $dept) ? 'selected' : '';
                    ?>
                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($dept); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Status</label>
                <select name="status" required>
                    <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="deactive" <?php echo $user['status'] == 'deactive' ? 'selected' : ''; ?>>Deactive</option>
                </select>
            </div>
            
            <div class="btn-row">
                <a href="admin_interface.php?page=user_management" class="btn btn-cancel">Cancel</a>
                <button type="submit" class="btn btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</body>
</html>