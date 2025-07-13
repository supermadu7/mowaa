<?php
session_start();
require_once 'includes/auth.php';
require_once '../config/database.php';

// Require user to be logged in
requireLogin();

// Fetch dashboard statistics
try {
    $db = new Database();
    $connection = $db->getConnection();
    
    // User statistics
    $userStatsQueries = [
        'total_users' => "SELECT COUNT(*) FROM users",
        'active_users' => "SELECT COUNT(*) FROM users WHERE is_active = 1",
        'admin_users' => "SELECT COUNT(*) FROM users WHERE user_role = 'admin'",
        'recent_users' => "SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND is_active = 1"
    ];
    
    $userStats = [];
    foreach ($userStatsQueries as $key => $query) {
        $stmt = $connection->prepare($query);
        $stmt->execute();
        $userStats[$key] = $stmt->fetchColumn();
    }
    
    // Travel request statistics (check if table exists first)
    $tableCheckQuery = "SHOW TABLES LIKE 'travel_requests'";
    $tableCheckStmt = $connection->prepare($tableCheckQuery);
    $tableCheckStmt->execute();
    $travelTableExists = $tableCheckStmt->fetchColumn();
    
    if ($travelTableExists) {
        $requestStatsQueries = [
            'total_requests' => "SELECT COUNT(*) FROM travel_requests",
            'pending_requests' => "SELECT COUNT(*) FROM travel_requests WHERE status = 'pending'",
            'approved_requests' => "SELECT COUNT(*) FROM travel_requests WHERE status = 'approved'",
            'rejected_requests' => "SELECT COUNT(*) FROM travel_requests WHERE status = 'rejected'"
        ];
        
        $requestStats = [];
        foreach ($requestStatsQueries as $key => $query) {
            $stmt = $connection->prepare($query);
            $stmt->execute();
            $requestStats[$key] = $stmt->fetchColumn();
        }
    } else {
        // Default values if table doesn't exist
        $requestStats = [
            'total_requests' => 0,
            'pending_requests' => 0,
            'approved_requests' => 0,
            'rejected_requests' => 0
        ];
    }
    
    // Recent activities (latest 5 users and requests)
    $recentUsersQuery = "SELECT username, first_name, last_name, user_role, created_at 
                         FROM users 
                         WHERE is_active = 1 
                         ORDER BY created_at DESC 
                         LIMIT 5";
    $recentUsersStmt = $connection->prepare($recentUsersQuery);
    $recentUsersStmt->execute();
    $recentUsers = $recentUsersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($travelTableExists) {
        $recentRequestsQuery = "SELECT CONCAT(first_name, ' ', last_name) as employee_name, 
                                       CONCAT(departure_airport, ' → ', arrival_airport) as destination, 
                                       reason_travel as purpose, 
                                       status, 
                                       created_at 
                                FROM travel_requests 
                                ORDER BY created_at DESC 
                                LIMIT 5";
        $recentRequestsStmt = $connection->prepare($recentRequestsQuery);
        $recentRequestsStmt->execute();
        $recentRequests = $recentRequestsStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $recentRequests = [];
    }
    
} catch (Exception $e) {
    $userStats = ['total_users' => 0, 'active_users' => 0, 'admin_users' => 0, 'recent_users' => 0];
    $requestStats = ['total_requests' => 0, 'pending_requests' => 0, 'approved_requests' => 0, 'rejected_requests' => 0];
    $recentUsers = [];
    $recentRequests = [];
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => "Error fetching dashboard data: " . $e->getMessage()
    ];
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="close">

<head>
    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>MOWAA - Dashboard</title>

    <!-- Favicon -->
    <link rel="icon" href="../assets/images/brand-logos/favicon.ico" type="image/x-icon">

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
        .stats-card {
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .activity-item {
            border-left: 3px solid #e9ecef;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .activity-item.user { border-left-color: #0d6efd; }
        .activity-item.request { border-left-color: #198754; }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
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
        <aside class="app-sidebar sticky" id="sidebar">
            <!-- Start::main-sidebar-header -->
            <div class="main-sidebar-header">
                <a href="index.php" class="header-logo">
                    <img src="../assets/images/brand-logos/desktop-white.png" class="desktop-white" alt="logo">
                    <img src="../assets/images/brand-logos/toggle-white.png" class="toggle-white" alt="logo">
                    <img src="../assets/images/brand-logos/desktop-logo.png" class="desktop-logo" alt="logo">
                    <img src="../assets/images/brand-logos/toggle-dark.png" class="toggle-dark" alt="logo">
                    <img src="../assets/images/brand-logos/toggle-logo.png" class="toggle-logo" alt="logo">
                    <img src="../assets/images/brand-logos/desktop-dark.png" class="desktop-dark" alt="logo">
                </a>
            </div>
            
            <!-- Start::main-sidebar -->
            <div class="main-sidebar" id="sidebar-scroll">
                <!-- Start::nav -->
                <?php include 'includes/sidebar.php'; ?>
                <!-- End::nav -->
            </div>
        </aside>
        <!-- End::app-sidebar -->

        <!-- Start::app-content -->
        <div class="main-content app-content">
            <div class="container-fluid">

                <!-- Page Header -->
                <div class="d-md-flex d-block align-items-center justify-content-between page-header-breadcrumb">
                    <div>
                        <h2 class="main-content-title fs-24 mb-1">Dashboard</h2>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </div>
                    <div class="d-flex">
                        <button type="button" class="btn btn-primary me-2" onclick="window.location.href='travel-requests.php'">
                            <i class="fe fe-briefcase me-2"></i> Travel Requests
                        </button>
                        <button type="button" class="btn btn-success" onclick="window.location.href='user-management.php'">
                            <i class="fe fe-users me-2"></i> User Management
                        </button>
                    </div>
                </div>

                <!-- Display Alert Messages -->
                <?php if (isset($_SESSION['alert'])): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['alert']['type']); ?> alert-dismissible fade show" role="alert">
                        <i class="fe fe-<?php echo $_SESSION['alert']['type'] == 'success' ? 'check-circle' : 'alert-triangle'; ?> me-2"></i>
                        <?php echo $_SESSION['alert']['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['alert']); ?>
                <?php endif; ?>

                <!-- Welcome Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <span class="avatar avatar-xl bg-primary-transparent">
                                            <i class="fe fe-home fs-24"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="fw-semibold mb-1">Welcome to MOWAA Travel Management System</h4>
                                        <p class="text-muted mb-0">Monitor your travel requests, manage users, and track system activities from this central dashboard.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Statistics Cards -->
                <div class="row">
                    <div class="col-12">
                        <h5 class="mb-3">User Management Overview</h5>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card custom-card stats-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <span class="d-block mb-1">Total Users</span>
                                        <h4 class="fw-semibold mb-0"><?php echo $userStats['total_users']; ?></h4>
                                        <small class="text-muted">All registered users</small>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar avatar-md br-5 bg-primary-transparent">
                                            <i class="fe fe-users fs-18"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card custom-card stats-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <span class="d-block mb-1">Active Users</span>
                                        <h4 class="fw-semibold mb-0"><?php echo $userStats['active_users']; ?></h4>
                                        <small class="text-muted">Currently active accounts</small>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar avatar-md br-5 bg-success-transparent">
                                            <i class="fe fe-user-check fs-18"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card custom-card stats-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <span class="d-block mb-1">Administrators</span>
                                        <h4 class="fw-semibold mb-0"><?php echo $userStats['admin_users']; ?></h4>
                                        <small class="text-muted">System admins</small>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar avatar-md br-5 bg-warning-transparent">
                                            <i class="fe fe-shield fs-18"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card custom-card stats-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <span class="d-block mb-1">New Users (30 days)</span>
                                        <h4 class="fw-semibold mb-0"><?php echo $userStats['recent_users']; ?></h4>
                                        <small class="text-muted">Active new registrations</small>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar avatar-md br-5 bg-info-transparent">
                                            <i class="fe fe-user-plus fs-18"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Travel Request Statistics Cards -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h5 class="mb-3">Travel Request Overview</h5>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card custom-card stats-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <span class="d-block mb-1">Total Requests</span>
                                        <h4 class="fw-semibold mb-0"><?php echo $requestStats['total_requests']; ?></h4>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar avatar-md br-5 bg-primary-transparent">
                                            <i class="fe fe-briefcase fs-18"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card custom-card stats-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <span class="d-block mb-1">Pending Requests</span>
                                        <h4 class="fw-semibold mb-0"><?php echo $requestStats['pending_requests']; ?></h4>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar avatar-md br-5 bg-warning-transparent">
                                            <i class="fe fe-clock fs-18"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card custom-card stats-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <span class="d-block mb-1">Approved Requests</span>
                                        <h4 class="fw-semibold mb-0"><?php echo $requestStats['approved_requests']; ?></h4>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar avatar-md br-5 bg-success-transparent">
                                            <i class="fe fe-check-circle fs-18"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="card custom-card stats-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <span class="d-block mb-1">Rejected Requests</span>
                                        <h4 class="fw-semibold mb-0"><?php echo $requestStats['rejected_requests']; ?></h4>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar avatar-md br-5 bg-danger-transparent">
                                            <i class="fe fe-x-circle fs-18"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row mt-4">
                    <div class="col-lg-6">
                        <div class="card custom-card">
                            <div class="card-header">
                                <div class="card-title">Recent Users</div>
                                <div class="ms-auto">
                                    <a href="user-management.php" class="btn btn-sm btn-primary">View All</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recentUsers)): ?>
                                    <?php foreach ($recentUsers as $user): ?>
                                        <div class="activity-item user">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h6>
                                                    <p class="text-muted mb-1">@<?php echo htmlspecialchars($user['username']); ?></p>                                    <span class="badge bg-<?php 
                                        echo $user['user_role'] === 'admin' ? 'danger' : 
                                             ($user['user_role'] === 'manager' ? 'warning' : 
                                              ($user['user_role'] === 'approver' ? 'info' : 'primary')); 
                                    ?> status-badge">
                                        <?php echo ucfirst($user['user_role']); ?>
                                    </span>
                                                </div>
                                                <small class="text-muted"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center">No recent users found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card custom-card">
                            <div class="card-header">
                                <div class="card-title">Recent Travel Requests</div>
                                <div class="ms-auto">
                                    <a href="travel-requests.php" class="btn btn-sm btn-primary">View All</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recentRequests)): ?>
                                    <?php foreach ($recentRequests as $request): ?>
                                        <div class="activity-item request">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($request['employee_name']); ?></h6>
                                                    <p class="text-muted mb-1"><?php echo htmlspecialchars($request['destination']); ?></p>
                                                    <p class="text-muted mb-1 small"><?php echo htmlspecialchars(substr($request['purpose'], 0, 50)) . (strlen($request['purpose']) > 50 ? '...' : ''); ?></p>
                                                    <span class="badge bg-<?php 
                                                        echo $request['status'] === 'approved' ? 'success' : 
                                                             ($request['status'] === 'rejected' ? 'danger' : 'warning'); 
                                                    ?> status-badge">
                                                        <?php echo ucfirst($request['status']); ?>
                                                    </span>
                                                </div>
                                                <small class="text-muted"><?php echo date('M d, Y', strtotime($request['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center">No recent travel requests found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card custom-card">
                            <div class="card-header">
                                <div class="card-title">Quick Actions</div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <button type="button" class="btn btn-outline-primary w-100" onclick="window.location.href='user-management.php'">
                                            <i class="fe fe-user-plus fs-18 d-block mb-2"></i>
                                            Create New User
                                        </button>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <button type="button" class="btn btn-outline-success w-100" onclick="window.location.href='travel-requests.php'">
                                            <i class="fe fe-briefcase fs-18 d-block mb-2"></i>
                                            View Travel Requests
                                        </button>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <button type="button" class="btn btn-outline-info w-100" onclick="window.location.href='profile.php'">
                                            <i class="fe fe-settings fs-18 d-block mb-2"></i>
                                            My Profile
                                        </button>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <button type="button" class="btn btn-outline-warning w-100" onclick="alert('Reports functionality coming soon!')">
                                            <i class="fe fe-bar-chart-2 fs-18 d-block mb-2"></i>
                                            View Reports
                                        </button>
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
        // Add some basic interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to stats cards
            const statsCards = document.querySelectorAll('.stats-card');
            statsCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '';
                });
            });
        });
    </script>

</body>
</html>
