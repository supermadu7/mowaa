<?php
session_start();
require_once 'includes/auth.php';
require_once '../config/database.php';
require_once '../config/config.php';

// Require user to be logged in and have permission to approve requests
requireLogin();
if (!canApproveRequests()) {
    $_SESSION['error'] = 'You do not have permission to perform this action.';
    header('Location: travel-requests.php');
    exit();
}

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
    $checkSql = "SELECT id, request_id, status, requester, first_name, last_name, email, travel_date, departure_airport, arrival_airport, estimated_cost FROM travel_requests WHERE id = ? LIMIT 1";
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
            
            // Send email notification to traveller
            if (!empty($request['email'])) {
                $subject = "Travel Request Approved - " . $request['request_id'];
                $message = generateStatusUpdateEmailTemplate($request, 'approved', $current_user, $comments);
                
                try {
                    $emailSent = sendEmailNotification($request['email'], $subject, $message);
                    if ($emailSent) {
                        error_log("Approval notification sent to traveller: " . $request['email']);
                    } else {
                        error_log("Failed to send approval notification to: " . $request['email']);
                    }
                } catch (Exception $e) {
                    error_log("Email error during approval: " . $e->getMessage());
                }
            }
            
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
            
            // Send email notification to traveller
            if (!empty($request['email'])) {
                $subject = "Travel Request Rejected - " . $request['request_id'];
                $message = generateStatusUpdateEmailTemplate($request, 'rejected', $current_user, $reason);
                
                try {
                    $emailSent = sendEmailNotification($request['email'], $subject, $message);
                    if ($emailSent) {
                        error_log("Rejection notification sent to traveller: " . $request['email']);
                    } else {
                        error_log("Failed to send rejection notification to: " . $request['email']);
                    }
                } catch (Exception $e) {
                    error_log("Email error during rejection: " . $e->getMessage());
                }
            }
            
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

/**
 * Send email notification
 */
function sendEmailNotification($to, $subject, $message, $fromEmail = FROM_EMAIL, $fromName = FROM_NAME) {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $fromEmail,
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

/**
 * Generate email template for approval/rejection notifications
 */
function generateStatusUpdateEmailTemplate($requestData, $status, $actionBy, $comments = '') {
    $isApproved = ($status === 'approved');
    $statusText = $isApproved ? 'Approved' : 'Rejected';
    $statusColor = $isApproved ? '#28a745' : '#dc3545';
    
    $template = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: ' . $statusColor . '; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f8f9fa; }
            .details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            .status-badge { background-color: ' . $statusColor . '; color: white; padding: 8px 15px; border-radius: 5px; display: inline-block; margin: 10px 0; }
            .comments-box { background-color: #f8f9fa; padding: 15px; border-left: 4px solid ' . $statusColor . '; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Travel Request ' . $statusText . '</h1>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($requestData['first_name'] . ' ' . $requestData['last_name']) . ',</p>
                <p>Your travel request has been <strong>' . strtolower($statusText) . '</strong>.</p>
                
                <div class="status-badge">
                    Status: ' . $statusText . '
                </div>
                
                <div class="details">
                    <h3>Request Details</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Request ID:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($requestData['request_id']) . '</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Travel Date:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($requestData['travel_date']) . '</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Destination:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($requestData['departure_airport'] . ' → ' . $requestData['arrival_airport']) . '</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Estimated Cost:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">$' . number_format($requestData['estimated_cost'], 2) . '</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>' . ($isApproved ? 'Approved' : 'Rejected') . ' By:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($actionBy) . '</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Action Date:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . date('M d, Y H:i') . '</td></tr>
                    </table>
                </div>
                
                ' . (!empty($comments) ? 
                    '<div class="comments-box">
                        <h4>' . ($isApproved ? 'Approval Comments:' : 'Rejection Reason:') . '</h4>
                        <p>' . nl2br(htmlspecialchars($comments)) . '</p>
                    </div>' : ''
                ) . '
                
                <div class="details">
                    <h4>Next Steps:</h4>
                    ' . ($isApproved ? 
                        '<ul>
                            <li>Your travel request has been approved</li>
                            <li>You can now proceed with your travel arrangements</li>
                            <li>Please follow your organization\'s travel booking procedures</li>
                            <li>Keep this email for your records</li>
                        </ul>' : 
                        '<ul>
                            <li>Your travel request has been rejected</li>
                            <li>Please review the rejection reason above</li>
                            <li>You may submit a new request if needed</li>
                            <li>Contact your approver if you have questions</li>
                        </ul>'
                    ) . '
                </div>
            </div>
            <div class="footer">
                <p>This is an automated message from the Mowaa Travel Management System.</p>
                <p>Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $template;
}

/**
 * Get requester's email by user ID
 */
function getRequesterEmail($db, $userId) {
    $sql = "SELECT email FROM users WHERE id = ? LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $user['email'] ?? null;
}
?>
