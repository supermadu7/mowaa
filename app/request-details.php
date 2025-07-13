<?php
session_start();
// Include database connection
require_once '../config/database.php';

// Get request ID from URL parameter
$request_id = isset($_GET['d']) ? (int)$_GET['d'] : 0;

if ($request_id <= 0) {
    header('Location: travel-requests.php');
    exit;
}

// Fetch travel request details
try {
    $db = new Database();
    $connection = $db->getConnection();

    // Fetch specific travel request with all details
    $sql = "SELECT 
                id, request_id, first_name, last_name, email, department, 
                travel_date, departure_airport, arrival_airport, reason_travel,
                estimated_cost, project_name, budget_code, approver, requester,
                passport_file, additional_files_paths, status, approved_by,
                approved_at, rejection_reason, created_at, updated_at
            FROM travel_requests 
            WHERE id = :id";

    $stmt = $connection->prepare($sql);
    $stmt->bindParam(':id', $request_id, PDO::PARAM_INT);
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        header('Location: travel-requests.php');
        exit;
    }

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error fetching travel request: " . $e->getMessage() . "</div>";
    exit;
}

?>

<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="close">

<head>

    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>MOWAA - Travel Request Details</title>

    <!-- Favicon -->
    <link rel="icon" href="../assets/images/brand-logos/favicon.ico" type="image/x-icon">

    <!-- Choices JS -->
    <script src="../assets/libs/choices.js/public/assets/scripts/choices.min.js"></script>

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

    <!-- Color Picker Css -->
    <link rel="stylesheet" href="../assets/libs/flatpickr/flatpickr.min.css">
    <link rel="stylesheet" href="../assets/libs/@simonwep/pickr/themes/nano.min.css">

    <!-- Choices Css -->
    <link rel="stylesheet" href="../assets/libs/choices.js/public/assets/styles/choices.min.css">

    <!-- Custom CSS for Request Details -->
    <style>
        .detail-card {
            border-left: 4px solid var(--primary-color);
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .file-preview {
            max-width: 150px;
            max-height: 150px;
            object-fit: cover;
        }
        .file-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .file-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .action-buttons {
            gap: 0.5rem;
        }
    </style>

</head>

<body>

    <!-- Loader -->
    <div id="loader">
        <img src="../assets/images/media/media-79.svg" alt="">
    </div>
    <!-- Loader -->

    <div class="page">
        <!-- app-header -->
        <?php include 'includes/header.php'; ?>
        <!-- /app-header -->
        <!-- Start::app-sidebar -->
        <aside class="app-sidebar sticky" id="sidebar">

            <!-- Start::main-sidebar-header -->
            <div class="main-sidebar-header">
                <a href="index.html" class="header-logo">
                    <img src="../assets/images/brand-logos/desktop-white.png" class="desktop-white" alt="logo">
                    <img src="../assets/images/brand-logos/toggle-white.png" class="toggle-white" alt="logo">
                    <img src="../assets/images/brand-logos/desktop-logo.png" class="desktop-logo" alt="logo">
                    <img src="../assets/images/brand-logos/toggle-dark.png" class="toggle-dark" alt="logo">
                    <img src="../assets/images/brand-logos/toggle-logo.png" class="toggle-logo" alt="logo">
                    <img src="../assets/images/brand-logos/desktop-dark.png" class="desktop-dark" alt="logo">
                </a>
            </div>
            <!-- End::main-sidebar-header -->

            <!-- Start::main-sidebar -->
            <div class="main-sidebar" id="sidebar-scroll">

                <!-- Start::nav -->
                <?php include 'includes/sidebar.php'; ?>
                <!-- End::nav -->

            </div>
            <!-- End::main-sidebar -->

        </aside>
        <!-- End::app-sidebar -->

        <!-- Start::app-content -->
        <div class="main-content app-content">
            <div class="container-fluid">

                <!-- Page Header -->
                <div class="d-md-flex d-block align-items-center justify-content-between page-header-breadcrumb">
                    <div>
                        <h2 class="main-content-title fs-24 mb-1">Travel Request Details</h2>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="travel-requests.php">Travel Requests</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Request Details</li>
                        </ol>
                    </div>
                    <div class="d-flex action-buttons">
                        <a href="travel-requests.php" class="btn btn-secondary my-2 me-2">
                            <i class="fe fe-arrow-left me-2"></i> Back to List
                        </a>
                        <?php if ($request['status'] == 'pending'): ?>
                            <button type="button" class="btn btn-success my-2 me-2" onclick="approveRequest(<?php echo $request['id']; ?>)">
                                <i class="fe fe-check me-2"></i> Approve
                            </button>
                            <button type="button" class="btn btn-danger my-2" onclick="rejectRequest(<?php echo $request['id']; ?>)">
                                <i class="fe fe-x me-2"></i> Reject
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Page Header Close -->

                <!-- Request Overview Card -->
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card custom-card detail-card">
                            <div class="card-header">
                                <div class="card-title d-flex justify-content-between align-items-center w-100">
                                    <span>Request Overview - <?php echo htmlspecialchars($request['request_id']); ?></span>
                                    <?php
                                    $statusClass = '';
                                    switch($request['status']) {
                                        case 'pending':
                                            $statusClass = 'bg-warning';
                                            break;
                                        case 'approved':
                                            $statusClass = 'bg-success';
                                            break;
                                        case 'rejected':
                                            $statusClass = 'bg-danger';
                                            break;
                                        case 'cancelled':
                                            $statusClass = 'bg-secondary';
                                            break;
                                        default:
                                            $statusClass = 'bg-light text-dark';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?> status-badge">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="fw-semibold text-muted mb-3">Traveller Information</h6>
                                        <table class="table table-borderless">
                                            <tr>
                                                <td class="fw-semibold">Full Name:</td>
                                                <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Email:</td>
                                                <td><?php echo htmlspecialchars($request['email']); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Department:</td>
                                                <td><?php echo htmlspecialchars($request['department']); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Requester:</td>
                                                <td><?php echo htmlspecialchars($request['requester']); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-semibold text-muted mb-3">Travel Information</h6>
                                        <table class="table table-borderless">
                                            <tr>
                                                <td class="fw-semibold">Travel Date:</td>
                                                <td><?php echo date('M d, Y', strtotime($request['travel_date'])); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Route:</td>
                                                <td><?php echo htmlspecialchars($request['departure_airport'] . ' → ' . $request['arrival_airport']); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Estimated Cost:</td>
                                                <td class="fw-bold text-primary">$<?php echo number_format($request['estimated_cost'], 2); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Created:</td>
                                                <td><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Travel Details Card -->
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card custom-card">
                            <div class="card-header">
                                <div class="card-title">Travel Details</div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="fw-semibold text-muted mb-3">Project Information</h6>
                                        <table class="table table-borderless">
                                            <tr>
                                                <td class="fw-semibold">Project Name:</td>
                                                <td><?php echo htmlspecialchars($request['project_name'] ?: 'Not specified'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Budget Code:</td>
                                                <td><?php echo htmlspecialchars($request['budget_code'] ?: 'Not specified'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold">Approver:</td>
                                                <td><?php echo htmlspecialchars($request['approver'] ?: 'Not assigned'); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-semibold text-muted mb-3">Reason for Travel</h6>
                                        <div class="bg-light p-3 rounded">
                                            <?php echo nl2br(htmlspecialchars($request['reason_travel'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Uploaded Files Card -->
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card custom-card">
                            <div class="card-header">
                                <div class="card-title">Uploaded Documents</div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Passport File -->
                                    <div class="col-md-6">
                                        <h6 class="fw-semibold text-muted mb-3">Passport Document</h6>
                                        <?php if (!empty($request['passport_file_path'])): ?>
                                            <div class="file-item">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <?php
                                                        $ext = strtolower(pathinfo($request['passport_file_path'], PATHINFO_EXTENSION));
                                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                                                        ?>
                                                            <img src="<?php echo htmlspecialchars($request['passport_file_path']); ?>" 
                                                                 alt="Passport" class="file-preview rounded">
                                                        <?php else: ?>
                                                            <div class="bg-primary text-white p-3 rounded text-center">
                                                                <i class="fe fe-file fs-24"></i>
                                                                <br><small><?php echo strtoupper($ext); ?></small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">Passport Document</h6>
                                                        <p class="text-muted mb-2"><?php echo basename($request['passport_file_path']); ?></p>
                                                        <a href="<?php echo htmlspecialchars($request['passport_file_path']); ?>" 
                                                           target="_blank" class="btn btn-sm btn-primary">
                                                            <i class="fe fe-download me-1"></i> Download
                                                        </a>
                                                        <a href="<?php echo htmlspecialchars($request['passport_file_path']); ?>" 
                                                           target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="fe fe-eye me-1"></i> View
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted">
                                                <i class="fe fe-info"></i> No passport document uploaded
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Additional Files -->
                                    <div class="col-md-6">
                                        <h6 class="fw-semibold text-muted mb-3">Additional Documents</h6>
                                        <?php 
                                        if (!empty($request['additional_files_paths'])):
                                            $additionalFiles = json_decode($request['additional_files_paths'], true);
                                            if ($additionalFiles && is_array($additionalFiles)):
                                                foreach ($additionalFiles as $file):
                                        ?>
                                            <div class="file-item">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <?php
                                                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                                                        ?>
                                                            <img src="<?php echo htmlspecialchars($file); ?>" 
                                                                 alt="Document" class="file-preview rounded">
                                                        <?php else: ?>
                                                            <div class="bg-secondary text-white p-3 rounded text-center">
                                                                <i class="fe fe-file fs-24"></i>
                                                                <br><small><?php echo strtoupper($ext); ?></small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">Additional Document</h6>
                                                        <p class="text-muted mb-2"><?php echo basename($file); ?></p>
                                                        <a href="<?php echo htmlspecialchars($file); ?>" 
                                                           target="_blank" class="btn btn-sm btn-primary">
                                                            <i class="fe fe-download me-1"></i> Download
                                                        </a>
                                                        <a href="<?php echo htmlspecialchars($file); ?>" 
                                                           target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="fe fe-eye me-1"></i> View
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php 
                                                endforeach;
                                            else:
                                        ?>
                                            <div class="text-muted">
                                                <i class="fe fe-info"></i> No additional documents uploaded
                                            </div>
                                        <?php 
                                            endif;
                                        else:
                                        ?>
                                            <div class="text-muted">
                                                <i class="fe fe-info"></i> No additional documents uploaded
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status History Card -->
                <?php if ($request['status'] != 'pending'): ?>
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card custom-card">
                            <div class="card-header">
                                <div class="card-title">Status History</div>
                            </div>
                            <div class="card-body">
                                <?php if ($request['status'] == 'approved'): ?>
                                    <div class="alert alert-success">
                                        <h6 class="alert-heading">Request Approved</h6>
                                        <p class="mb-1">
                                            <strong>Approved by:</strong> <?php echo htmlspecialchars($request['approved_by'] ?: 'System'); ?>
                                        </p>
                                        <p class="mb-0">
                                            <strong>Approved on:</strong> 
                                            <?php echo $request['approved_at'] ? date('M d, Y H:i', strtotime($request['approved_at'])) : 'Not specified'; ?>
                                        </p>
                                    </div>
                                <?php elseif ($request['status'] == 'rejected'): ?>
                                    <div class="alert alert-danger">
                                        <h6 class="alert-heading">Request Rejected</h6>
                                        <?php if (!empty($request['rejection_reason'])): ?>
                                            <p class="mb-1">
                                                <strong>Reason:</strong> <?php echo nl2br(htmlspecialchars($request['rejection_reason'])); ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="mb-0">
                                            <strong>Updated on:</strong> 
                                            <?php echo date('M d, Y H:i', strtotime($request['updated_at'])); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <!-- End::app-content -->

        <!-- Footer Start -->
        <?php include 'includes/footer.php'; ?>
        <!-- Footer End -->

    </div>

    <script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>

    <!-- Scroll To Top -->
    <div class="scrollToTop">
        <span class="arrow"><i class="fe fe-arrow-up"></i></span>
    </div>
    <div id="responsive-overlay"></div>
    <!-- Scroll To Top -->

    <!-- Popper JS -->
    <script src="../assets/libs/@popperjs/core/umd/popper.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Defaultmenu JS -->
    <script src="../assets/js/defaultmenu.min.js"></script>

    <!-- Node Waves JS-->
    <script src="../assets/libs/node-waves/waves.min.js"></script>

    <!-- Sticky JS -->
    <script src="../assets/js/sticky.js"></script>

    <!-- Simplebar JS -->
    <script src="../assets/libs/simplebar/simplebar.min.js"></script>
    <script src="../assets/js/simplebar.js"></script>

    <!-- Color Picker JS -->
    <script src="../assets/libs/@simonwep/pickr/pickr.es5.min.js"></script>

    <!-- Custom-Switcher JS -->
    <script src="../assets/js/custom-switcher.min.js"></script>

    <!-- Custom JS -->
    <script src="../assets/js/custom.js"></script>

    <!-- Request Details JS -->
    <script>
        function approveRequest(id) {
            if (confirm('Are you sure you want to approve this travel request?')) {
                $.ajax({
                    url: 'update-request-status.php',
                    method: 'POST',
                    data: {
                        id: id,
                        status: 'approved'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error approving request: ' + (response.message || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error processing request: ' + error);
                    }
                });
            }
        }

        function rejectRequest(id) {
            const reason = prompt('Please provide a reason for rejection:');
            if (reason && reason.trim()) {
                $.ajax({
                    url: 'update-request-status.php',
                    method: 'POST',
                    data: {
                        id: id,
                        status: 'rejected',
                        reason: reason
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error rejecting request: ' + (response.message || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error processing request: ' + error);
                    }
                });
            }
        }
    </script>

</body>

</html>
