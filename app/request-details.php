<?php
session_start();
require_once 'includes/auth.php';
require_once '../config/database.php';

// Require user to be logged in
requireLogin();

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
                approved_at, approval_comments, rejected_by, rejected_at,
                rejection_reason, created_at, updated_at
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
    <link rel="icon" href="../assets/images/brand-logos/icon.png" type="image/png">

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
        <?php include 'includes/sidebar.php'; ?>
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
                            <button type="button" class="btn btn-success my-2 me-2" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="fe fe-check me-2"></i> Approve
                            </button>
                            <button type="button" class="btn btn-danger my-2" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="fe fe-x me-2"></i> Reject
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Page Header Close -->

                <!-- Display Alert Messages -->
                <?php if (isset($_SESSION['alert'])): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['alert']['type']); ?> alert-dismissible fade show" role="alert">
                        <i class="fe fe-<?php echo $_SESSION['alert']['type'] == 'success' ? 'check-circle' : ($_SESSION['alert']['type'] == 'danger' ? 'alert-triangle' : 'info'); ?> me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['alert']['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['alert']); ?>
                <?php endif; ?>

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
                                        <p class="mb-1">
                                            <strong>Approved on:</strong> 
                                            <?php echo $request['approved_at'] ? date('M d, Y H:i', strtotime($request['approved_at'])) : 'Not specified'; ?>
                                        </p>
                                        <?php if (!empty($request['approval_comments'])): ?>
                                            <p class="mb-0">
                                                <strong>Comments:</strong> <?php echo nl2br(htmlspecialchars($request['approval_comments'])); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($request['status'] == 'rejected'): ?>
                                    <div class="alert alert-danger">
                                        <h6 class="alert-heading">Request Rejected</h6>
                                        <?php if (!empty($request['rejection_reason'])): ?>
                                            <p class="mb-1">
                                                <strong>Reason:</strong> <?php echo nl2br(htmlspecialchars($request['rejection_reason'])); ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="mb-1">
                                            <strong>Rejected by:</strong> <?php echo htmlspecialchars($request['rejected_by'] ?: 'System'); ?>
                                        </p>
                                        <p class="mb-0">
                                            <strong>Rejected on:</strong> 
                                            <?php echo $request['rejected_at'] ? date('M d, Y H:i', strtotime($request['rejected_at'])) : date('M d, Y H:i', strtotime($request['updated_at'])); ?>
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

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Approve Travel Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="process-request-action.php">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fe fe-info me-2"></i>
                            Are you sure you want to approve this travel request?
                        </div>
                        <div class="mb-3">
                            <strong>Request ID:</strong> <?php echo htmlspecialchars($request['request_id']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Requester:</strong> <?php echo htmlspecialchars($request['requester']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Destination:</strong> <?php echo htmlspecialchars($request['arrival_airport']); ?>
                        </div>
                        <div class="mb-3">
                            <label for="approval_comments" class="form-label">Comments (Optional)</label>
                            <textarea class="form-control" id="approval_comments" name="comments" rows="3" placeholder="Add any comments for this approval..."></textarea>
                        </div>
                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fe fe-check me-2"></i>Approve Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Reject Travel Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="process-request-action.php">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fe fe-alert-triangle me-2"></i>
                            Are you sure you want to reject this travel request?
                        </div>
                        <div class="mb-3">
                            <strong>Request ID:</strong> <?php echo htmlspecialchars($request['request_id']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Requester:</strong> <?php echo htmlspecialchars($request['requester']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Destination:</strong> <?php echo htmlspecialchars($request['arrival_airport']); ?>
                        </div>
                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="rejection_reason" name="reason" rows="4" placeholder="Please provide a clear reason for rejecting this request..." required></textarea>
                            <div class="form-text">This reason will be shown to the requester.</div>
                        </div>
                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fe fe-x me-2"></i>Reject Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>
