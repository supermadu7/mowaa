<?php
session_start();
require_once 'includes/auth.php';
require_once '../config/database.php';

// Require user to be logged in
requireLogin();
$currentUserId = $_SESSION['user_id'];

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Database();
        $connection = $db->getConnection();
        
        if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
            // Update profile information
            $firstName = trim($_POST['first_name']);
            $lastName = trim($_POST['last_name']);
            $title = trim($_POST['title']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $department = trim($_POST['department']);
            
            // Validate required fields
            if (empty($firstName) || empty($lastName) || empty($email) || empty($title)) {
                throw new Exception('Please fill in all required fields.');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address.');
            }
            
            // Check if email already exists for another user
            $emailCheckSql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $emailCheckStmt = $connection->prepare($emailCheckSql);
            $emailCheckStmt->execute([$email, $currentUserId]);
            
            if ($emailCheckStmt->rowCount() > 0) {
                throw new Exception('This email address is already in use by another user.');
            }
            
            // Update user profile
            $updateSql = "UPDATE users SET 
                         first_name = ?, 
                         last_name = ?, 
                         title = ?,
                         email = ?, 
                         phone = ?, 
                         department = ?,
                         updated_at = NOW()
                         WHERE id = ?";
            
            $updateStmt = $connection->prepare($updateSql);
            $updateStmt->execute([$firstName, $lastName, $title, $email, $phone, $department, $currentUserId]);
            
            $message = 'Profile updated successfully!';
            $messageType = 'success';
            
        } elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
            // Change password
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Validate passwords
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception('Please fill in all password fields.');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('New password and confirmation do not match.');
            }
            
            if (strlen($newPassword) < 6) {
                throw new Exception('New password must be at least 6 characters long.');
            }
            
            // Get current user's password
            $getUserSql = "SELECT password FROM users WHERE id = ?";
            $getUserStmt = $connection->prepare($getUserSql);
            $getUserStmt->execute([$currentUserId]);
            $user = $getUserStmt->fetch();
            
            if (!$user) {
                throw new Exception('User not found.');
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception('Current password is incorrect.');
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updatePasswordSql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
            $updatePasswordStmt = $connection->prepare($updatePasswordSql);
            $updatePasswordStmt->execute([$hashedPassword, $currentUserId]);
            
            $message = 'Password changed successfully!';
            $messageType = 'success';
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Fetch current user data
try {
    $db = new Database();
    $connection = $db->getConnection();
    
    $userSql = "SELECT username, email, first_name, last_name, title, department, phone, 
                       user_role, is_active, last_login, created_at 
                FROM users WHERE id = ?";
    $userStmt = $connection->prepare($userSql);
    $userStmt->execute([$currentUserId]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        throw new Exception('User not found.');
    }
    
} catch (Exception $e) {
    $message = 'Error loading user data: ' . $e->getMessage();
    $messageType = 'danger';
    $user = [];
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="close">

<head>
    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>MOWAA - Profile</title>

    <!-- Favicon -->
    <link rel="icon" href="../assets/images/brand-logos/icon.png" type="image/png">

    <!-- Bootstrap Css -->
    <link id="style" href="../assets/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Main Theme Js -->
    <script src="../assets/js/main.js"></script>

    <!-- Style Css -->
    <link href="../assets/css/styles.min.css" rel="stylesheet">

    <!-- Icons Css -->
    <link href="../assets/css/icons.css" rel="stylesheet">

    <!-- Node Waves Css -->
    <link href="../assets/libs/node-waves/waves.min.css" rel="stylesheet">

    <!-- Simplebar Css -->
    <link href="../assets/libs/simplebar/simplebar.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .stats-card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .nav-pills .nav-link {
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        

    </style>
</head>

<body>
    <!-- Loader -->
    <div id="loader">
        <img src="../assets/images/media/media-79.svg" alt="">
    </div>

    <div class="page">
        <!-- app-header -->
        <?php include 'includes/header.php'; ?>
        
        <!-- Start::app-sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        <!-- End::app-sidebar -->

        <!-- Start::app-content -->
        <div class="main-content app-content">
            <div class="container-fluid">

                <!-- Page Header -->
                <div class="d-md-flex d-block align-items-center justify-content-between page-header-breadcrumb">
                    <div>
                        <h2 class="main-content-title fs-24 mb-1">Profile</h2>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Profile</li>
                        </ol>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <i class="fe fe-<?php echo $messageType === 'success' ? 'check-circle' : 'alert-triangle'; ?> me-2"></i>
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Profile Header -->
                <div class="row">
                    <div class="col-12">
                        <div class="card custom-card profile-header">
                            <div class="card-body text-center">
                                <div class="d-flex flex-column align-items-center">
                                    <img src="../assets/images/faces/9.jpg" alt="Profile" class="rounded-circle profile-avatar mb-3">
                                    <h3 class="mb-1"><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></h3>
                                    <p class="mb-1"><?php echo htmlspecialchars($user['title'] ?? ''); ?></p>
                                    <p class="mb-0 opacity-75">@<?php echo htmlspecialchars($user['username'] ?? ''); ?></p>
                                    <span class="badge bg-<?php 
                                        echo ($user['user_role'] ?? '') === 'admin' ? 'danger' : 
                                             (($user['user_role'] ?? '') === 'manager' ? 'warning' : 
                                              (($user['user_role'] ?? '') === 'approver' ? 'info' : 'primary')); 
                                    ?> mt-2">
                                        <?php echo ucfirst($user['user_role'] ?? 'User'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Stats -->
                <div class="row mb-4">
                    <div class="col-lg-4 col-md-6">
                        <div class="card custom-card stats-card">
                            <div class="card-body text-center">
                                <i class="fe fe-calendar text-primary fs-24 mb-3"></i>
                                <h6>Member Since</h6>
                                <p class="text-muted mb-0"><?php echo date('M d, Y', strtotime($user['created_at'] ?? 'now')); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="card custom-card stats-card">
                            <div class="card-body text-center">
                                <i class="fe fe-clock text-success fs-24 mb-3"></i>
                                <h6>Last Login</h6>
                                <p class="text-muted mb-0"><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="card custom-card stats-card">
                            <div class="card-body text-center">
                                <i class="fe fe-<?php echo ($user['is_active'] ?? 0) ? 'check-circle text-success' : 'x-circle text-danger'; ?> fs-24 mb-3"></i>
                                <h6>Account Status</h6>
                                <p class="text-muted mb-0"><?php echo ($user['is_active'] ?? 0) ? 'Active' : 'Inactive'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="row">
                    <div class="col-lg-3">
                        <!-- Navigation Pills -->
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                    <button class="nav-link active" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab">
                                        <i class="fe fe-user me-2"></i>Edit Profile
                                    </button>
                                    <button class="nav-link" id="v-pills-password-tab" data-bs-toggle="pill" data-bs-target="#v-pills-password" type="button" role="tab">
                                        <i class="fe fe-lock me-2"></i>Change Password
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-9">
                        <div class="tab-content" id="v-pills-tabContent">
                            <!-- Edit Profile Tab -->
                            <div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel">
                                <div class="card custom-card">
                                    <div class="card-header">
                                        <div class="card-title">
                                            <i class="fe fe-user me-2"></i>Edit Profile Information
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="update_profile">
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                                           value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                                           value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="title" name="title" 
                                                           value="<?php echo htmlspecialchars($user['title'] ?? ''); ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                                    <input type="email" class="form-control" id="email" name="email" 
                                                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="phone" class="form-label">Phone Number</label>
                                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="department" class="form-label">Department</label>
                                                    <input type="text" class="form-control" id="department" name="department" 
                                                           value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>">
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="username" class="form-label">Username</label>
                                                    <input type="text" class="form-control" id="username" 
                                                           value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" readonly>
                                                    <div class="form-text">Username cannot be changed</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="user_role" class="form-label">Role</label>
                                                    <input type="text" class="form-control" id="user_role" 
                                                           value="<?php echo ucfirst($user['user_role'] ?? 'User'); ?>" readonly>
                                                    <div class="form-text">Contact administrator to change role</div>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-end">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fe fe-save me-2"></i>Update Profile
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Change Password Tab -->
                            <div class="tab-pane fade" id="v-pills-password" role="tabpanel">
                                <div class="card custom-card">
                                    <div class="card-header">
                                        <div class="card-title">
                                            <i class="fe fe-lock me-2"></i>Change Password
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="" id="passwordForm">
                                            <input type="hidden" name="action" value="change_password">
                                            
                                            <div class="mb-3">
                                                <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                                        <i class="fe fe-eye" id="current_password_icon"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                                        <i class="fe fe-eye" id="new_password_icon"></i>
                                                    </button>
                                                </div>
                                                <div class="form-text">Password must be at least 6 characters long</div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                                        <i class="fe fe-eye" id="confirm_password_icon"></i>
                                                    </button>
                                                </div>
                                                <div class="invalid-feedback" id="password_mismatch" style="display: none;">
                                                    Passwords do not match
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-end">
                                                <button type="submit" class="btn btn-warning">
                                                    <i class="fe fe-key me-2"></i>Change Password
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- End::app-content -->

        <!-- Footer Start -->
        <?php include 'includes/footer.php'; ?>
        <!-- Footer End -->

    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
    <script src="../assets/libs/@popperjs/core/umd/popper.min.js"></script>
    <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/defaultmenu.min.js"></script>
    <script src="../assets/libs/node-waves/waves.min.js"></script>
    <script src="../assets/js/sticky.js"></script>
    <script src="../assets/libs/simplebar/simplebar.min.js"></script>
    <script src="../assets/js/simplebar.js"></script>
    <script src="../assets/js/custom-switcher.min.js"></script>
    <script src="../assets/js/custom.js"></script>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '_icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'fe fe-eye-off';
            } else {
                field.type = 'password';
                icon.className = 'fe fe-eye';
            }
        }

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            const mismatchDiv = document.getElementById('password_mismatch');
            
            if (confirmPassword && newPassword !== confirmPassword) {
                this.classList.add('is-invalid');
                mismatchDiv.style.display = 'block';
            } else {
                this.classList.remove('is-invalid');
                mismatchDiv.style.display = 'none';
            }
        });

        // Form validation
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                document.getElementById('confirm_password').classList.add('is-invalid');
                document.getElementById('password_mismatch').style.display = 'block';
                return false;
            }
        });

        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>

</body>
</html>
