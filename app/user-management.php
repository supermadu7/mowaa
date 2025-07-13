<?php
session_start();
require_once 'includes/auth.php';
require_once '../config/database.php';

// Require user to be logged in and have permission to manage users
requireLogin();
if (!canManageUsers()) {
    header('Location: index.php');
    exit();
}

// Handle edit user form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
	$edit_user_id = (int) $_POST['user_id'];
	$username = trim($_POST['username']);
	$email = trim($_POST['email']);
	$first_name = trim($_POST['first_name']);
	$last_name = trim($_POST['last_name']);
	$title = trim($_POST['title']);
	$user_role = $_POST['role'];
	$department = trim($_POST['department']);
	$phone = trim($_POST['phone']);
	$is_active = isset($_POST['is_active']) ? 1 : 0;
	$new_password = trim($_POST['new_password']);

	$errors = [];

	// Validation
	if (empty($username)) $errors[] = "Username is required";
	if (empty($email)) $errors[] = "Email is required";
	if (empty($first_name)) $errors[] = "First name is required";
	if (empty($last_name)) $errors[] = "Last name is required";
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";

	if (empty($errors)) {
		try {
			$db = new Database();
			$connection = $db->getConnection();

			// Check if username or email already exists (excluding current user)
			$checkSql = "SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?";
			$checkStmt = $connection->prepare($checkSql);
			$checkStmt->execute([$username, $email, $edit_user_id]);

			if ($checkStmt->fetchColumn() > 0) {
				$errors[] = "Username or email already exists";
			} else {
				// Build update query
				$updateFields = [
					'username = ?',
					'email = ?',
					'first_name = ?',
					'last_name = ?',
					'title = ?',
					'user_role = ?',
					'department = ?',
					'phone = ?',
					'is_active = ?',
					'updated_at = NOW()'
				];
				$updateValues = [$username, $email, $first_name, $last_name, $title, $user_role, $department, $phone, $is_active];

				// Add password update if provided
				if (!empty($new_password)) {
					if (strlen($new_password) < 6) {
						$errors[] = "Password must be at least 6 characters";
					} else {
						$updateFields[] = 'password = ?';
						$updateValues[] = password_hash($new_password, PASSWORD_DEFAULT);
					}
				}

				if (empty($errors)) {
					$updateValues[] = $edit_user_id;
					$updateSql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
					$updateStmt = $connection->prepare($updateSql);
					$result = $updateStmt->execute($updateValues);

					if ($result) {
						$_SESSION['alert'] = [
							'type' => 'success',
							'message' => "User '{$username}' updated successfully!"
						];
						header('Location: user-management.php');
						exit;
					} else {
						$errors[] = "Failed to update user. Please try again.";
					}
				}
			}
		} catch (Exception $e) {
			$errors[] = "Database error: " . $e->getMessage();
		}
	}

	if (!empty($errors)) {
		$_SESSION['alert'] = [
			'type' => 'danger',
			'message' => implode('<br>', $errors)
		];
	}
}

// Handle toggle user status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
	$toggle_user_id = (int) $_POST['user_id'];

	try {
		$db = new Database();
		$connection = $db->getConnection();

		// Get current status
		$statusSql = "SELECT username, is_active FROM users WHERE id = ? LIMIT 1";
		$statusStmt = $connection->prepare($statusSql);
		$statusStmt->execute([$toggle_user_id]);
		$user_status = $statusStmt->fetch(PDO::FETCH_ASSOC);

		if ($user_status && $user_status['username'] !== 'admin') {
			$new_status = $user_status['is_active'] ? 0 : 1;
			$updateSql = "UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?";
			$updateStmt = $connection->prepare($updateSql);
			$result = $updateStmt->execute([$new_status, $toggle_user_id]);

			if ($result) {
				$status_text = $new_status ? 'activated' : 'deactivated';
				$_SESSION['alert'] = [
					'type' => 'success',
					'message' => "User '{$user_status['username']}' has been {$status_text} successfully."
				];
			} else {
				$_SESSION['alert'] = [
					'type' => 'danger',
					'message' => 'Failed to update user status. Please try again.'
				];
			}
		} else {
			$_SESSION['alert'] = [
				'type' => 'warning',
				'message' => 'Cannot modify the main administrator account.'
			];
		}
	} catch (Exception $e) {
		$_SESSION['alert'] = [
			'type' => 'danger',
			'message' => 'Database error occurred. Please try again later.'
		];
	}

	header('Location: user-management.php');
	exit;
}

// Handle form submission for creating new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
	$username = trim($_POST['username']);
	$email = trim($_POST['email']);
	$password = $_POST['password'];
	$confirm_password = $_POST['confirm_password'];
	$first_name = trim($_POST['first_name']);
	$last_name = trim($_POST['last_name']);
	$title = trim($_POST['title']);
	$user_role = $_POST['role'];
	$department = trim($_POST['department']);
	$phone = trim($_POST['phone']);
	$is_active = isset($_POST['is_active']) ? 1 : 0;

	$errors = [];

	// Validation
	if (empty($username)) $errors[] = "Username is required";
	if (empty($email)) $errors[] = "Email is required";
	if (empty($password)) $errors[] = "Password is required";
	if (empty($first_name)) $errors[] = "First name is required";
	if (empty($last_name)) $errors[] = "Last name is required";
	if ($password !== $confirm_password) $errors[] = "Passwords do not match";
	if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";

	if (empty($errors)) {
		try {
			$db = new Database();
			$connection = $db->getConnection();

			// Check if username or email already exists
			$checkSql = "SELECT COUNT(*) FROM users WHERE username = ? OR email = ?";
			$checkStmt = $connection->prepare($checkSql);
			$checkStmt->execute([$username, $email]);

			if ($checkStmt->fetchColumn() > 0) {
				$errors[] = "Username or email already exists";
			} else {
				// Hash password
				$hashed_password = password_hash($password, PASSWORD_DEFAULT);

				// Insert new user
				$insertSql = "INSERT INTO users (username, email, password, first_name, last_name, title, user_role, department, phone, is_active) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
				$insertStmt = $connection->prepare($insertSql);
				$result = $insertStmt->execute([
					$username,
					$email,
					$hashed_password,
					$first_name,
					$last_name,
					$title,
					$user_role,
					$department,
					$phone,
					$is_active
				]);

				if ($result) {
					$_SESSION['alert'] = [
						'type' => 'success',
						'message' => "User '{$username}' created successfully!"
					];
					header('Location: user-management.php');
					exit;
				} else {
					$errors[] = "Failed to create user. Please try again.";
				}
			}
		} catch (Exception $e) {
			$errors[] = "Database error: " . $e->getMessage();
		}
	}

	if (!empty($errors)) {
		$_SESSION['alert'] = [
			'type' => 'danger',
			'message' => implode('<br>', $errors)
		];
	}
}

// Fetch existing users
try {
	$db = new Database();
	$connection = $db->getConnection();

	$sql = "SELECT * FROM users";

	$stmt = $connection->prepare($sql);
	$stmt->execute();
	$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
	$users = [];
	$_SESSION['alert'] = [
		'type' => 'danger',
		'message' => "Error fetching users: " . $e->getMessage()
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
	<title>MOWAA - User Management</title>

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

	<!-- DataTables Css -->
	<link rel="stylesheet" href="../assets/libs/data-tables/css/dataTables.bootstrap5.min.css">
	<link rel="stylesheet" href="../assets/libs/data-tables/css/buttons.bootstrap5.min.css">

	<!-- Custom CSS -->
	<style>
		.user-card {
			transition: all 0.3s ease;
		}

		.user-card:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
		}

		.role-badge {
			font-size: 0.8rem;
			padding: 0.4rem 0.8rem;
		}

		.status-indicator {
			width: 12px;
			height: 12px;
			border-radius: 50%;
			display: inline-block;
			margin-right: 8px;
		}

		.status-active {
			background-color: #28a745;
		}

		.status-inactive {
			background-color: #dc3545;
		}

		.form-floating>label {
			color: #6c757d;
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
						<h2 class="main-content-title fs-24 mb-1">User Management</h2>                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">User Management</li>
                        </ol>
					</div>
					<div class="d-flex">
						<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
							<i class="fe fe-plus me-2"></i> Create New User
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

				<!-- Users Statistics Cards -->
				<div class="row">
					<?php
					$total_users = count($users);
					$active_users = count(array_filter($users, function ($user) {
						return $user['is_active'];
					}));
					$admin_users = count(array_filter($users, function ($user) {
						return $user['user_role'] === 'admin';
					}));
					$recent_users = count(array_filter($users, function ($user) {
						return strtotime($user['created_at']) > strtotime('-30 days');
					}));
					?>
					<div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
						<div class="card custom-card">
							<div class="card-body">
								<div class="d-flex align-items-start justify-content-between">
									<div>
										<span class="d-block mb-1">Total Users</span>
										<h4 class="fw-semibold mb-0"><?php echo $total_users; ?></h4>
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
						<div class="card custom-card">
							<div class="card-body">
								<div class="d-flex align-items-start justify-content-between">
									<div>
										<span class="d-block mb-1">Active Users</span>
										<h4 class="fw-semibold mb-0"><?php echo $active_users; ?></h4>
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
						<div class="card custom-card">
							<div class="card-body">
								<div class="d-flex align-items-start justify-content-between">
									<div>
										<span class="d-block mb-1">Administrators</span>
										<h4 class="fw-semibold mb-0"><?php echo $admin_users; ?></h4>
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
						<div class="card custom-card">
							<div class="card-body">
								<div class="d-flex align-items-start justify-content-between">
									<div>
										<span class="d-block mb-1">New Users (30 days)</span>
										<h4 class="fw-semibold mb-0"><?php echo $recent_users; ?></h4>
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

				<!-- Users Table -->
				<div class="row">
					<div class="col-xl-12">
						<div class="card custom-card">
							<div class="card-header">
								<div class="card-title">All Users</div>
							</div>
							<div class="card-body">
								<div class="table-responsive">
									<table id="usersTable" class="table table-bordered text-nowrap w-100">
										<thead>
											<tr>
												<th>User</th>
												<th>Email</th>
												<th>Title</th>
												<th>Role</th>
												<th>Department</th>
												<th>Status</th>
												<th>Last Login</th>
												<th>Created</th>
												<th>Actions</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($users as $user): ?>
												<tr>
													<td>
														<div class="d-flex align-items-center">
															<span class="avatar avatar-md me-2 avatar-rounded">
																<?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
															</span>
															<div>
																<p class="mb-0 fw-semibold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
																<p class="mb-0 text-muted fs-12">@<?php echo htmlspecialchars($user['username']); ?></p>
															</div>
														</div>
													</td>
													<td><?php echo htmlspecialchars($user['email']); ?></td>
													<td><?php echo htmlspecialchars($user['title'] ?: 'Not assigned'); ?></td>
													<td> <?php
															$roleClass = '';
															switch ($user['user_role']) {
																case 'admin':
																	$roleClass = 'bg-danger';
																	break;
																case 'manager':
																	$roleClass = 'bg-warning';
																	break;
																case 'approver':
																	$roleClass = 'bg-info';
																	break;
																case 'user':
																	$roleClass = 'bg-primary';
																	break;
															}
															?>
														<span class="badge <?php echo $roleClass; ?> role-badge">
															<?php echo ucfirst($user['user_role']); ?>
														</span>
													</td>
													<td><?php echo htmlspecialchars($user['department'] ?: 'Not assigned'); ?></td>
													<td>
														<span class="status-indicator <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>"></span>
														<?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
													</td>
													<td>
														<?php
														echo $user['last_login']
															? date('M d, Y H:i', strtotime($user['last_login']))
															: '<span class="text-muted">Never</span>';
														?>
													</td>
													<td>
														<?php echo date('M d, Y', strtotime($user['created_at'])); ?>

													</td>
													<td>
														<div class="btn-group" role="group">
															<button type="button" class="btn btn-sm btn-primary" onclick="editUser(<?php echo $user['id']; ?>)" title="Edit User">
																<i class="fe fe-edit"></i>
															</button>
															<button type="button" class="btn btn-sm btn-info" onclick="viewUser(<?php echo $user['id']; ?>)" title="View Details">
																<i class="fe fe-eye"></i>
															</button>
															<?php if ($user['username'] !== 'admin'): ?>
																<button type="button" class="btn btn-sm btn-<?php echo $user['is_active'] ? 'warning' : 'success'; ?>"
																	onclick="toggleUserStatus(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 'false' : 'true'; ?>)"
																	title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?> User">
																	<i class="fe fe-<?php echo $user['is_active'] ? 'user-x' : 'user-check'; ?>"></i>
																</button>
																<button type="button" class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)" title="Delete User">
																	<i class="fe fe-trash-2"></i>
																</button>
															<?php endif; ?>
														</div>
													</td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
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

	<!-- Create User Modal -->
	<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="createUserModalLabel">Create New User</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<form method="POST" action="">
					<div class="modal-body">
						<div class="row">
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
									<label for="username">Username <span class="text-danger">*</span></label>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
									<label for="email">Email <span class="text-danger">*</span></label>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name" required>
									<label for="first_name">First Name <span class="text-danger">*</span></label>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name" required>
									<label for="last_name">Last Name <span class="text-danger">*</span></label>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<div class="form-floating mb-3">
									<input type="text" class="form-control" id="title" name="title" placeholder="Title">
									<label for="title">Job Title</label>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
									<label for="password">Password <span class="text-danger">*</span></label>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
									<label for="confirm_password">Confirm Password <span class="text-danger">*</span></label>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<select class="form-select" id="role" name="role" required>
										<option value="">Select Role</option>
										<option value="user">User</option>
										<option value="approver">Approver</option>
										<option value="manager">Manager</option>
										<option value="admin">Administrator</option>
									</select>
									<label for="role">Role <span class="text-danger">*</span></label>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<input type="text" class="form-control" id="department" name="department" placeholder="Department">
									<label for="department">Department</label>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone">
									<label for="phone">Phone Number</label>
								</div>
							</div>
							<div class="col-md-6 d-flex align-items-center">
								<div class="form-check form-switch">
									<input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
									<label class="form-check-label" for="is_active">
										Active User
									</label>
								</div>
							</div>
						</div>
						<input type="hidden" name="action" value="create_user">
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn btn-primary">
							<i class="fe fe-plus me-2"></i>Create User
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Edit User Modal -->
	<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<form method="POST" action="" id="editUserForm">
					<div class="modal-body">
						<div class="row">
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<input type="text" class="form-control" id="edit_username" name="username" placeholder="Username" required>
									<label for="edit_username">Username <span class="text-danger">*</span></label>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<input type="email" class="form-control" id="edit_email" name="email" placeholder="Email" required>
									<label for="edit_email">Email <span class="text-danger">*</span></label>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<input type="text" class="form-control" id="edit_first_name" name="first_name" placeholder="First Name" required>
									<label for="edit_first_name">First Name <span class="text-danger">*</span></label>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<input type="text" class="form-control" id="edit_last_name" name="last_name" placeholder="Last Name" required>
									<label for="edit_last_name">Last Name <span class="text-danger">*</span></label>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<div class="form-floating mb-3">
									<input type="text" class="form-control" id="edit_title" name="title" placeholder="Title">
									<label for="edit_title">Job Title</label>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<input type="password" class="form-control" id="edit_new_password" name="new_password" placeholder="New Password">
									<label for="edit_new_password">New Password (leave blank to keep current)</label>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<select class="form-select" id="edit_role" name="role" required>
										<option value="">Select Role</option>
										<option value="user">User</option>
										<option value="approver">Approver</option>
										<option value="manager">Manager</option>
										<option value="admin">Administrator</option>
									</select>
									<label for="edit_role">Role <span class="text-danger">*</span></label>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<input type="text" class="form-control" id="edit_department" name="department" placeholder="Department">
									<label for="edit_department">Department</label>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-floating mb-3">
									<input type="tel" class="form-control" id="edit_phone" name="phone" placeholder="Phone">
									<label for="edit_phone">Phone Number</label>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6 d-flex align-items-center">
								<div class="form-check form-switch">
									<input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
									<label class="form-check-label" for="edit_is_active">
										Active User
									</label>
								</div>
							</div>
						</div>
						<input type="hidden" name="action" value="edit_user">
						<input type="hidden" name="user_id" id="edit_user_id">
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn btn-primary">
							<i class="fe fe-save me-2"></i>Update User
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- View User Modal -->
	<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="viewUserModalLabel">User Details</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body" id="viewUserContent">
					<!-- User details will be loaded here -->
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
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

	<!-- DataTables -->
	<script src="../assets/libs/data-tables/js/jquery.dataTables.min.js"></script>
	<script src="../assets/libs/data-tables/js/dataTables.bootstrap5.min.js"></script>
	<script src="../assets/libs/data-tables/js/dataTables.buttons.min.js"></script>
	<script src="../assets/libs/data-tables/js/buttons.bootstrap5.min.js"></script>

	<script>
		// Initialize DataTable
		$(document).ready(function() {
			$('#usersTable').DataTable({
				responsive: true,
				pageLength: 10,
				order: [
					[6, 'desc']
				], // Sort by created date
				columnDefs: [{
						orderable: false,
						targets: -1
					} // Disable sorting on actions column
				]
			});
		});

		// Password confirmation validation
		document.getElementById('confirm_password').addEventListener('input', function() {
			const password = document.getElementById('password').value;
			const confirmPassword = this.value;

			if (password !== confirmPassword) {
				this.setCustomValidity('Passwords do not match');
			} else {
				this.setCustomValidity('');
			}
		});

		// User management functions
		function editUser(userId) {
			// Find the user data from the current page
			const userRow = document.querySelector(`button[onclick="editUser(${userId})"]`).closest('tr');
			const cells = userRow.querySelectorAll('td');

			// Extract user data from the table row
			const userNameCell = cells[0];
			const fullName = userNameCell.querySelector('p.fw-semibold').textContent.trim();
			const username = userNameCell.querySelector('p.text-muted').textContent.replace('@', '').trim();
			const email = cells[1].textContent.trim();
			const title = cells[2].textContent.trim();
			const role = cells[3].querySelector('.badge').textContent.toLowerCase().trim();
			const department = cells[4].textContent.trim();
			const isActive = cells[5].textContent.trim() === 'Active';

			// Split full name
			const nameParts = fullName.split(' ');
			const firstName = nameParts[0] || '';
			const lastName = nameParts.slice(1).join(' ') || '';

			// Populate edit form
			document.getElementById('edit_user_id').value = userId;
			document.getElementById('edit_username').value = username;
			document.getElementById('edit_email').value = email;
			document.getElementById('edit_first_name').value = firstName;
			document.getElementById('edit_last_name').value = lastName;
			document.getElementById('edit_title').value = title === 'Not assigned' ? '' : title;
			document.getElementById('edit_role').value = role;
			document.getElementById('edit_department').value = department === 'Not assigned' ? '' : department;
			document.getElementById('edit_phone').value = ''; // Not displayed in table
			document.getElementById('edit_is_active').checked = isActive;

			// Show modal
			new bootstrap.Modal(document.getElementById('editUserModal')).show();
		}

		function viewUser(userId) {
			// Find the user data from the current page
			const userRow = document.querySelector(`button[onclick="viewUser(${userId})"]`).closest('tr');
			const cells = userRow.querySelectorAll('td');

			// Extract user data from the table row
			const userNameCell = cells[0];
			const fullName = userNameCell.querySelector('p.fw-semibold').textContent.trim();
			const username = userNameCell.querySelector('p.text-muted').textContent.replace('@', '').trim();
			const email = cells[1].textContent.trim();
			const title = cells[2].textContent.trim();
			const role = cells[3].querySelector('.badge').textContent.trim();
			const department = cells[4].textContent.trim();
			const status = cells[5].textContent.trim();
			const lastLogin = cells[6].textContent.trim();
			const created = cells[7].textContent.trim();
			alert('Created: ' + created);

			// Split full name
			const nameParts = fullName.split(' ');
			const firstName = nameParts[0] || '';
			const lastName = nameParts.slice(1).join(' ') || '';

			const statusBadge = status === 'Active' ?
				'<span class="badge bg-success">Active</span>' :
				'<span class="badge bg-danger">Inactive</span>';

			const roleBadge = getRoleBadge(role.toLowerCase());

			const content = `
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="avatar avatar-xxl mx-auto mb-3 bg-primary text-white d-flex align-items-center justify-content-center" style="font-size: 2rem;">
                            ${firstName.charAt(0)}${lastName.charAt(0)}
                        </div>
                        <h5>${fullName}</h5>
                        <p class="text-muted">@${username}</p>
                        ${statusBadge}
                    </div>
                    <div class="col-md-8">
                        <table class="table table-borderless">
                            <tr><td class="fw-semibold">Email:</td><td>${email}</td></tr>
                            <tr><td class="fw-semibold">Title:</td><td>${title}</td></tr>
                            <tr><td class="fw-semibold">Role:</td><td>${roleBadge}</td></tr>
                            <tr><td class="fw-semibold">Department:</td><td>${department}</td></tr>
                            <tr><td class="fw-semibold">Last Login:</td><td>${lastLogin}</td></tr>
                            <tr><td class="fw-semibold">Created:</td><td>${created}</td></tr>
                           
                        </table>
                    </div>
                </div>
            `;

			document.getElementById('viewUserContent').innerHTML = content;
			new bootstrap.Modal(document.getElementById('viewUserModal')).show();
		}

		function toggleUserStatus(userId, newStatus) {
			const action = newStatus === 'true' ? 'activate' : 'deactivate';
			if (confirm(`Are you sure you want to ${action} this user?`)) {
				// Create form and submit
				const form = document.createElement('form');
				form.method = 'POST';
				form.action = '';

				const actionInput = document.createElement('input');
				actionInput.type = 'hidden';
				actionInput.name = 'action';
				actionInput.value = 'toggle_status';

				const userIdInput = document.createElement('input');
				userIdInput.type = 'hidden';
				userIdInput.name = 'user_id';
				userIdInput.value = userId;

				form.appendChild(actionInput);
				form.appendChild(userIdInput);
				document.body.appendChild(form);
				form.submit();
			}
		}

		function deleteUser(userId) {
			if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
				// For now, just show an alert - implement delete functionality as needed
				alert('Delete functionality can be implemented based on requirements');
			}
		}

		function getRoleBadge(role) {
			const roleClasses = {
				'admin': 'bg-danger',
				'administrator': 'bg-danger',
				'manager': 'bg-warning',
				'approver': 'bg-info',
				'user': 'bg-primary'
			};
			return `<span class="badge ${roleClasses[role] || 'bg-light text-dark'}">${role.charAt(0).toUpperCase() + role.slice(1)}</span>`;
		}
	</script>

</body>

</html>