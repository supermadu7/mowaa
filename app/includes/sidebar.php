<!-- Start::app-sidebar -->
<aside class="app-sidebar sticky" id="sidebar">
    <!-- Start::main-sidebar-header -->
    <div class="main-sidebar-header">
        <a href="index.php" class="header-logo">
            <img src="../assets/images/brand-logos/logo.png" class="desktop-white" alt="logo">
            <img src="../assets/images/brand-logos/logo.png" class="toggle-white" alt="logo">
            <img src="../assets/images/brand-logos/logo.png" class="desktop-logo" alt="logo">
            <img src="../assets/images/brand-logos/logo.png" class="toggle-dark" alt="logo">
            <img src="../assets/images/brand-logos/logo.png" class="toggle-logo" alt="logo">
            <img src="../assets/images/brand-logos/logo.png" class="desktop-dark" alt="logo">
        </a>
    </div>
    
    <!-- Embedded Sidebar Logo CSS -->
    <style>
        /* Sidebar Logo Styling - Embedded for immediate effect */
        .main-sidebar-header .header-logo img {
            max-height: 40px !important;
            width: auto !important;
            object-fit: contain !important;
        }

        .main-sidebar-header {
            padding: 1rem !important;
            max-height: 70px !important;
            overflow: hidden !important;
        }

        .header-logo {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        /* Theme-based logo display rules */
        [data-theme-mode=dark] .app-sidebar .main-sidebar-header .header-logo .desktop-dark {
            display: block !important;
        }

        [data-theme-mode=dark] .app-sidebar .main-sidebar-header .header-logo .desktop-logo,
        [data-theme-mode=dark] .app-sidebar .main-sidebar-header .header-logo .toggle-logo,
        [data-theme-mode=dark] .app-sidebar .main-sidebar-header .header-logo .toggle-dark,
        [data-theme-mode=dark] .app-sidebar .main-sidebar-header .header-logo .desktop-white,
        [data-theme-mode=dark] .app-sidebar .main-sidebar-header .header-logo .toggle-white {
            display: none !important;
        }

        /* Light menu styles */
        [data-menu-styles=light][data-nav-layout=vertical] .app-sidebar .main-sidebar-header .header-logo .desktop-logo {
            display: block !important;
        }

        [data-menu-styles=light][data-nav-layout=vertical] .app-sidebar .main-sidebar-header .header-logo .desktop-dark,
        [data-menu-styles=light][data-nav-layout=vertical] .app-sidebar .main-sidebar-header .header-logo .toggle-dark,
        [data-menu-styles=light][data-nav-layout=vertical] .app-sidebar .main-sidebar-header .header-logo .toggle-logo,
        [data-menu-styles=light][data-nav-layout=vertical] .app-sidebar .main-sidebar-header .header-logo .desktop-white,
        [data-menu-styles=light][data-nav-layout=vertical] .app-sidebar .main-sidebar-header .header-logo .toggle-white {
            display: none !important;
        }

        /* Dark menu styles */
        [data-menu-styles=dark][data-nav-layout=vertical] .app-sidebar .main-sidebar-header .header-logo .desktop-dark {
            display: block !important;
        }

        [data-menu-styles=dark][data-nav-layout=vertical] .app-sidebar .main-sidebar-header .header-logo .desktop-logo,
        [data-menu-styles=dark][data-nav-layout=vertical] .app-sidebar .main-sidebar-header .header-logo .toggle-dark,
        [data-menu-styles=dark][data-nav-layout=vertical] .app-sidebar .main-sidebar-header .header-logo .toggle-logo,
        [data-menu-styles=dark][data-nav-layout=vertical] .app-sidebar .main-sidebar-header .header-logo .desktop-white,
        [data-menu-styles=dark][data-nav-layout=vertical] .app-sidebar .main-sidebar-header .header-logo .toggle-white {
            display: none !important;
        }
    </style>
    
    <!-- Start::main-sidebar -->
    <div class="main-sidebar" id="sidebar-scroll">
        <!-- Start::nav -->
        <nav class="main-menu-container nav nav-pills flex-column sub-open">
            <div class="slide-left" id="slide-left">
                <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path> </svg>
            </div>
            <ul class="main-menu">
                <!-- Start::slide__category -->
                <li class="slide__category"><span class="category-name">Dashboard</span></li>
                <!-- End::slide__category -->

                <!-- Start::slide -->
                <li class="slide">
                    <a href="index.php" class="side-menu__item">
                        <span class="shape1"></span>
                        <span class="shape2"></span>
                        <i class="ti-home side-menu__icon"></i>
                        <span class="side-menu__label">Dashboard</span>
                    </a>
                </li>
                <!-- End::slide -->

                <!-- Start::slide__category -->
                <li class="slide__category"><span class="category-name">Travel Management</span></li>
                <!-- End::slide__category -->

                <!-- Start::slide -->
                <li class="slide">
                    <a href="travel-requests.php" class="side-menu__item">
                        <span class="shape1"></span>
                        <span class="shape2"></span>
                        <i class="ti-briefcase side-menu__icon"></i>
                        <span class="side-menu__label">Travel Requests</span>
                    </a>
                </li>
                <!-- End::slide -->

                <!-- Start::slide__category -->
                <li class="slide__category"><span class="category-name">Administration</span></li>
                <!-- End::slide__category -->

                <!-- Start::slide -->
                <li class="slide">
                    <a href="user-management.php" class="side-menu__item">
                        <span class="shape1"></span>
                        <span class="shape2"></span>
                        <i class="ti-user side-menu__icon"></i>
                        <span class="side-menu__label">User Management</span>
                    </a>
                </li>
                <!-- End::slide -->

                <!-- Start::slide__category -->
                <li class="slide__category"><span class="category-name">My Account</span></li>
                <!-- End::slide__category -->

                <!-- Start::slide -->
                <li class="slide">
                    <a href="profile.php" class="side-menu__item">
                        <span class="shape1"></span>
                        <span class="shape2"></span>
                        <i class="ti-settings side-menu__icon"></i>
                        <span class="side-menu__label">My Profile</span>
                    </a>
                </li>
                <!-- End::slide -->
            </ul>
        </nav>
        <!-- End::nav -->
    </div>
</aside>
<!-- End::app-sidebar -->