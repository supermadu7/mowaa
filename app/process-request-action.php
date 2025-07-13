<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in (you may need to adjust this based on your authentication system)
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit;
// }

// Initialize response variables
$success = false;
$message = '';
$redirect_url = '';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $message = 'Invalid request method';
    header('Location: travel-requests.php');
    exit;
}

// Validate required fields
if (!isset($_POST['request_id']) || !isset($_POST['action'])) {
    $message = 'Missing required fields';
    header('Location: travel-requests.php');
    exit;
}

$request_id = (int) $_POST['request_id'];
$action = $_POST['action'];

// Validate action
if (!in_array($action, ['approve', 'reject'])) {
    $message = 'Invalid action';
    header('Location: travel-requests.php');
    exit;
}

// Get current user (you may need to adjust this based on your authentication system)
$current_user = $_SESSION['username'] ?? 'System Admin';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // First, check if the request exists and is still pending
    $checkSql = "SELECT id, request_id, status, requester FROM travel_requests WHERE id = ? LIMIT 1";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([$request_id]);
    $request = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Travel request not found.'
        ];
        header('Location: travel-requests.php');
        exit;
    }
    
    if ($request['status'] !== 'pending') {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'message' => 'This request has already been processed and cannot be modified.'
        ];
        header('Location: request-details.php?d=' . $request_id);
        exit;
    }
    
    // Process the action
    if ($action === 'approve') {
        // Approve the request
        $comments = $_POST['comments'] ?? '';
        
        $updateSql = "UPDATE travel_requests 
                      SET status = 'approved', 
                          approved_by = ?, 
                          approved_at = NOW(),
                          approval_comments = ?,
                          updated_at = NOW()
                      WHERE id = ?";
        
        $updateStmt = $db->prepare($updateSql);
        $result = $updateStmt->execute([$current_user, $comments, $request_id]);
        
        if ($result) {
            $success = true;
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Travel request #' . $request['request_id'] . ' has been approved successfully.'
            ];
            
            // Log the action (optional)
            logAction($db, $request_id, 'approved', $current_user, $comments);
            
        } else {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => 'Failed to approve the request. Please try again.'
            ];
        }
        
    } elseif ($action === 'reject') {
        // Reject the request
        $reason = $_POST['reason'] ?? '';
        
        if (empty(trim($reason))) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => 'Rejection reason is required.'
            ];
            header('Location: request-details.php?d=' . $request_id);
            exit;
        }
        
        $updateSql = "UPDATE travel_requests 
                      SET status = 'rejected', 
                          rejection_reason = ?,
                          rejected_by = ?,
                          rejected_at = NOW(),
                          updated_at = NOW()
                      WHERE id = ?";
        
        $updateStmt = $db->prepare($updateSql);
        $result = $updateStmt->execute([$reason, $current_user, $request_id]);
        
        if ($result) {
            $success = true;
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Travel request #' . $request['request_id'] . ' has been rejected.'
            ];
            
            // Log the action (optional)
            logAction($db, $request_id, 'rejected', $current_user, $reason);
            
        } else {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => 'Failed to reject the request. Please try again.'
            ];
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error in process-request-action.php: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Database error occurred. Please try again later.'
    ];
}

// Redirect back to request details page
header('Location: request-details.php?d=' . $request_id);
exit;

/**
 * Log the action for audit trail (optional)
 */
function logAction($db, $request_id, $action, $user, $notes = '') {
    try {
        $logSql = "INSERT INTO request_actions_log (request_id, action, performed_by, notes, created_at) 
                   VALUES (?, ?, ?, ?, NOW())";
        $logStmt = $db->prepare($logSql);
        $logStmt->execute([$request_id, $action, $user, $notes]);
    } catch (PDOException $e) {
        // Log error but don't fail the main operation
        error_log("Failed to log action: " . $e->getMessage());
    }
}
?>
