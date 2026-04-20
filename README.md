# PMURAS - PMU Recognition & Achievement System

## System Overview
PMURAS adalah sistem pengurusan pengiktirafan dan pencapaian untuk PMU (Polytechnic Management Unit).

## Features
- User Authentication (Login/Register)
- Admin Dashboard
- Staff Dashboard
- Submission Management (Recognition & Achievement)
- User Management
- Category Management
- Report Generation
- Document Upload (PDF)

## User Roles
1. **Admin**: Dapat menguruskan semua submission, user, category, dan report
2. **Staff**: Dapat submit recognition dan achievement sendiri

## Installation Instructions

### 1. Database Setup
1. Buka phpMyAdmin atau MySQL command line
2. Import fail `database.sql` untuk create database dan table
3. Database akan create dengan nama `pmuras_db`

### 2. Configuration
1. Edit fail `config.php` jika perlu tukar database credentials:
   - DB_HOST: localhost
   - DB_USER: root
   - DB_PASS: (kosong untuk XAMPP default)
   - DB_NAME: pmuras_db

### 3. File Structure
```
pmuras_system/
├── config.php                    (Database connection)
├── database.sql                  (Database schema)
├── login.php                     (Login page)
├── register.php                  (Registration page)
├── mainAdmin_dashboard.php       (Admin main dashboard)
├── admin_interface.php           (Admin interface with tabs)
├── staff_dashboard.php           (Staff dashboard)
├── recognition_achievement.php   (Recognition/Achievement page)
├── submit_form.php              (Form submission handler)
├── approve_submission.php        (Approval handler)
├── view_submission.php           (View submission details)
├── toggle_user_status.php        (Toggle user active/deactive)
├── forgot_password.php           (Forgot password page)
├── logout.php                    (Logout handler)
└── uploads/                      (Upload directory - will be created)
```

### 4. Permissions
Pastikan folder `uploads/` ada permission untuk write:
```bash
chmod 777 uploads/
```

## Default Accounts

### Admin Account
- Staff ID: ADMIN001
- Password: admin123

### Staff Accounts
1. Staff ID: IT001, Password: password123 (IT Department)
2. Staff ID: CE001, Password: password123 (Civil Engineering Department)
3. Staff ID: BUS001, Password: password123 (Business Department)

## Usage Flow

### For Admin:
1. Login dengan ADMIN001
2. Pilih "Manage Submission" untuk approve/reject submission
3. Pilih "User Management" untuk manage users (activate/deactivate)
4. Pilih "Categories" untuk manage categories dan levels
5. Pilih "Reports" untuk generate reports

### For Staff:
1. Login dengan staff account
2. Pilih RECOGNITION atau ACHIEVEMENT
3. Fill in form dan submit
4. Tunggu admin approval

### Admin Interface Navigation:
Bila admin click "ENDORSEMENT" di mainAdmin_dashboard.php, ia akan pergi ke recognition_achievement.php dengan type=achievement (mengikut sketsa yang pertama).

## Important Notes
1. User baru yang register akan ada status "deactive" - admin perlu approve di User Management
2. Document upload limited to PDF sahaja, max 2MB
3. Semua submission perlu approval dari admin
4. Password di-hash menggunakan MD5 (untuk production, gunakan bcrypt)

## Technology Stack
- PHP 7.4+
- MySQL 5.7+
- HTML5/CSS3
- Responsive Design

## Browser Compatibility
- Chrome (Recommended)
- Firefox
- Safari
- Edge

## Security Features
- Session-based authentication
- SQL injection protection (mysqli_real_escape_string)
- File upload validation
- Role-based access control

## Future Enhancements
- Email notification system
- Advanced reporting with charts
- Export to Excel/PDF
- Multi-language support
- Enhanced password reset functionality

## Support
For technical support, contact: admin@pmuras.com

## License
Copyright © 2026 PMURAS System. All rights reserved.
