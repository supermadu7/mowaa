<?php
// Get current user information
require_once 'auth.php';
$currentUser = getCurrentUser();
$userName = $currentUser ? $currentUser['full_name'] : 'Guest User';
$userRole = $currentUser ? ucfirst($currentUser['user_role']) : 'Guest';

// Role badge colors
$roleBadgeClass = '';
switch($currentUser['user_role'] ?? '') {
    case 'admin':
        $roleBadgeClass = 'danger';
        break;
    case 'manager':
        $roleBadgeClass = 'warning';
        break;
    case 'approver':
        $roleBadgeClass = 'info';
        break;
    case 'user':
        $roleBadgeClass = 'primary';
        break;
    default:
        $roleBadgeClass = 'secondary';
}
?>
<header class="app-header">

            <!-- Start::main-header-container -->
            <div class="main-header-container container-fluid">

                <!-- Start::header-content-left -->
                <div class="header-content-left">

                    <!-- Start::header-element -->
                    <div class="header-element">
                        <div class="horizontal-logo">
                            <a href="index.php" class="header-logo">
                                <img src="../assets/images/brand-logos/desktop-logo.png" alt="logo" class="desktop-logo">
                                <img src="../assets/images/brand-logos/toggle-logo.png" alt="logo" class="toggle-logo">
                                <img src="../assets/images/brand-logos/desktop-dark.png" alt="logo" class="desktop-dark">
                                <img src="../assets/images/brand-logos/toggle-dark.png" alt="logo" class="toggle-dark">
                                <img src="../assets/images/brand-logos/desktop-white.png" alt="logo" class="desktop-white">
                                <img src="../assets/images/brand-logos/toggle-white.png" alt="logo" class="toggle-white">
                            </a>
                        </div>
                    </div>
                    <!-- End::header-element -->

                    <!-- Start::header-element -->
                    <div class="header-element">
                        <!-- Start::header-link -->
                        <a aria-label="Hide Sidebar" class="sidemenu-toggle header-link animated-arrow hor-toggle horizontal-navtoggle" data-bs-toggle="sidebar" href="javascript:void(0);"><span></span></a>
                        <!-- End::header-link -->
                    </div>
                    <!-- End::header-element -->
 
                </div>
                <!-- End::header-content-left -->

                <!-- Start::header-content-right -->
                <div class="header-content-right">

                    <!-- Start::header-element -->
                     
                    <!-- End::header-element -->

                    <!-- Start::header-element -->
                    <div class="header-element">
                        <!-- Start::header-link|dropdown-toggle -->
                        <a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <div class="d-flex align-items-center">
                                <div class="me-sm-2 me-0">
                                    <img src="../assets/images/faces/9.jpg" alt="img" width="32" height="32" class="rounded-circle">
                                </div>
                                <div class="d-sm-block d-none">
                                    <p class="fw-semibold mb-0 lh-1"><?php echo htmlspecialchars($userName); ?></p>
                                    <span class="op-7 fw-normal d-block fs-11"><?php echo htmlspecialchars($userRole); ?></span>
                                </div>
                            </div>
                        </a>
                        <!-- End::header-link|dropdown-toggle -->
                        <ul class="main-header-dropdown dropdown-menu pt-0 overflow-hidden header-profile-dropdown dropdown-menu-end" aria-labelledby="mainHeaderProfile">
                           
                            <li>
                                <div class="dropdown-item-text">
                                    <small class="text-muted">Logged in as:</small><br>
                                    <span class="fw-semibold"><?php echo htmlspecialchars($currentUser['username'] ?? ''); ?></span>
                                    <span class="badge bg-<?php echo $roleBadgeClass; ?> ms-2"><?php echo htmlspecialchars($userRole); ?></span>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                             <li><a class="dropdown-item d-flex" href="profile.php"><i class="ti ti-user-circle fs-18 me-2 op-7"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item d-flex" href="logout.php"><i class="ti ti-logout fs-18 me-2 op-7"></i>Log Out</a></li>
                        </ul>
                    </div>  
                    <!-- End::header-element -->    

                    <!-- Start::header-element -->
                    <div class="header-element">
                        <!-- Start::header-link|switcher-icon -->
                        <a href="javascript:void(0);" class="header-link switcher-icon" data-bs-toggle="offcanvas" data-bs-target="#switcher-canvas">
                            <i class="fe fe-settings header-link-icon"></i>
                        </a>
                        <!-- End::header-link|switcher-icon -->
                    </div>
                    <!-- End::header-element -->

                </div>
                <!-- End::header-content-right -->

            </div>
            <!-- End::main-header-container -->

        </header>