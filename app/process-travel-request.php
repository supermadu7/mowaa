<?php
session_start();

// Set maximum execution time for file processing
ini_set('max_execution_time', 300);

// Include database and configuration
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

// EMAIL CONFIGURATION NOTE:
// For email notifications to work properly, ensure that:
// 1. PHP's mail() function is configured on your server
// 2. SMTP settings are properly configured in php.ini
// 3. Alternative: Consider using PHPMailer or similar library for production
// 4. Test email functionality on your server environment

// Configuration from config file
$uploadDir = UPLOAD_DIR;
$maxFileSize = MAX_FILE_SIZE;
$maxUploadSize = 5 * 1024 * 1024; // 5MB initial upload limit (reasonable for high-quality images)
$allowedTypes = ALLOWED_FILE_TYPES;

// Create upload directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

/**
 * Redirect back to form with errors
 */
function redirectWithErrors($errors, $formData = [], $generalError = '') {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $formData;
    if (!empty($generalError)) {
        $_SESSION['general_error'] = $generalError;
    }
    header('Location: travel-request.php');
    exit;
}

/**
 * Compress and resize image to reduce file size
 */
function compressImage($source, $destination, $quality = 75) {
    $info = getimagesize($source);
    
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/gif') {
        $image = imagecreatefromgif($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } else {
        return false;
    }
    
    // Get original dimensions
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Calculate new dimensions (max 1920px width)
    $maxWidth = 1920;
    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = ($height * $maxWidth) / $width;
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }
    
    // Create new image
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($info['mime'] == 'image/png' || $info['mime'] == 'image/gif') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save compressed image
    if ($info['mime'] == 'image/jpeg') {
        imagejpeg($newImage, $destination, $quality);
    } elseif ($info['mime'] == 'image/gif') {
        imagegif($newImage, $destination);
    } elseif ($info['mime'] == 'image/png') {
        imagepng($newImage, $destination, 6);
    }
    
    // Clean up memory
    imagedestroy($image);
    imagedestroy($newImage);
    
    return true;
}

/**
 * Compress PDF file using ghostscript (if available)
 */
function compressPDF($source, $destination) {
    // Check if Ghostscript is available
    $gsCommand = 'gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH -sOutputFile="' . $destination . '" "' . $source . '"';
    
    $output = shell_exec($gsCommand . ' 2>&1');
    
    if (file_exists($destination) && filesize($destination) > 0) {
        return true;
    }
    
    // If Ghostscript fails, just copy the original file
    return copy($source, $destination);
}

/**
 * Process and compress uploaded file
 */
function processUploadedFile($file, $uploadDir, $prefix = '') {
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    // Check for upload errors
    if ($fileError !== UPLOAD_ERR_OK) {
        throw new Exception("File upload error: " . $fileError);
    }
    
    // Get file extension
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Validate file type
    global $allowedTypes;
    if (!in_array($fileExtension, $allowedTypes)) {
        throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowedTypes));
    }
    
    // Generate unique filename - simplified format with conflict handling
    $uniqueName = $prefix . '.' . $fileExtension;
    $destination = $uploadDir . $uniqueName;
    
    // Handle file naming conflicts by adding a counter
    $counter = 1;
    while (file_exists($destination)) {
        $uniqueName = $prefix . '_' . $counter . '.' . $fileExtension;
        $destination = $uploadDir . $uniqueName;
        $counter++;
    }
    
    $tempDestination = $uploadDir . 'temp_' . $uniqueName;
    
    // Move uploaded file to temp location
    if (!move_uploaded_file($fileTmpName, $tempDestination)) {
        throw new Exception("Failed to move uploaded file");
    }
    
    // Compress file based on type
    if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
        // Compress image
        if (compressImage($tempDestination, $destination, 75)) {
            unlink($tempDestination); // Remove temp file
        } else {
            rename($tempDestination, $destination); // Use original if compression fails
        }
    } elseif ($fileExtension === 'pdf') {
        // Compress PDF
        if (compressPDF($tempDestination, $destination)) {
            unlink($tempDestination); // Remove temp file
        } else {
            rename($tempDestination, $destination); // Use original if compression fails
        }
    } else {
        // For other file types, just rename
        rename($tempDestination, $destination);
    }
    
    // Check final file size and compress further if needed
    $finalSize = filesize($destination);
    global $maxFileSize;
    
    if ($finalSize > $maxFileSize && in_array($fileExtension, ['jpg', 'jpeg'])) {
        // Try with lower quality for JPEG
        compressImage($destination, $destination, 50);
        $finalSize = filesize($destination);
        
        if ($finalSize > $maxFileSize) {
            compressImage($destination, $destination, 30);
        }
    }
    
    return [
        'original_name' => $fileName,
        'stored_name' => $uniqueName,
        'file_path' => $destination,
        'file_size' => filesize($destination),
        'file_type' => $fileExtension
    ];
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
 * Generate email template for travel request notifications
 */
function generateTravelRequestEmailTemplate($requestData, $isApprover = false) {
    $template = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f8f9fa; }
            .details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Travel Request ' . ($isApprover ? 'Approval Required' : 'Submitted') . '</h1>
            </div>
            <div class="content">
                ' . ($isApprover ? 
                    '<p>Dear Approver,</p>
                    <p>A new travel request has been submitted and requires your approval.</p>' : 
                    '<p>Dear ' . htmlspecialchars($requestData['firstName'] . ' ' . $requestData['lastName']) . ',</p>
                    <p>Your travel request has been successfully submitted and is now pending approval.</p>'
                ) . '
                
                <div class="details">
                    <h3>Travel Request Details</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Request ID:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($requestData['requestId']) . '</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Traveller:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($requestData['firstName'] . ' ' . $requestData['lastName']) . '</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Department:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($requestData['department']) . '</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Travel Date:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($requestData['travelDate']) . '</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>From:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($requestData['departureAirport']) . '</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>To:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($requestData['arrivalAirport']) . '</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Reason:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($requestData['reasonTravel']) . '</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Estimated Cost:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">$' . number_format($requestData['estimatedCost'], 2) . '</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Project:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($requestData['projectName']) . '</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Budget Code:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($requestData['budgetCode']) . '</td></tr>
                        ' . ($isApprover ? '<tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Approver:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($requestData['approver']) . '</td></tr>' : '') . '
                    </table>
                </div>
                
                ' . ($isApprover ? 
                    '<p>Please review this request and take appropriate action.</p>
                    <p><strong>Next Steps:</strong></p>
                    <ul>
                        <li>Log into the system to review the full request details</li>
                        <li>Approve or reject the request with comments</li>
                        <li>The traveller will be notified of your decision</li>
                    </ul>' : 
                    '<p><strong>Next Steps:</strong></p>
                    <ul>
                        <li>Your request will be reviewed by: ' . htmlspecialchars($requestData['approver']) . '</li>
                        <li>You will receive an email notification once approved/rejected</li>
                        <li>Keep your Request ID for future reference</li>
                    </ul>'
                ) . '
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
 * Generate a unique request ID with database uniqueness check
 */
function generateUniqueRequestId() {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $maxAttempts = 10;
    $attempt = 0;
    
    do {
        $attempt++;
        
        // Generate a shorter, more readable ID
        $timestamp = time();
        $random = strtoupper(substr(uniqid(), -4)); // 4 character suffix
        $requestId = 'TR_' . substr($timestamp, -4) . $random; // TR_XXXXYYY format
        
        // Check if this ID already exists in the database
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM travel_requests WHERE request_id = ?");
        $stmt->execute([$requestId]);
        $exists = $stmt->fetchColumn() > 0;
        
        if (!$exists) {
            return $requestId;
        }
        
        // If ID exists, wait a moment and try again
        usleep(100000); // 0.1 second
        
    } while ($attempt < $maxAttempts);
    
    // Fallback to longer ID if we can't generate a unique shorter one
    return 'TR_' . uniqid();
}

// Main processing
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Not a POST request - redirect to form
    header('Location: travel-request.php');
    exit;
}

try {
    // Store form data for repopulation
    $formData = $_POST;
    $errors = [];
    
    // Debug: Log file upload information for troubleshooting
    error_log("DEBUG - Files received: " . print_r($_FILES, true));
    error_log("DEBUG - Post data: " . print_r($_POST, true));
    
    // Validate required fields
    $requiredFields = [
        'firstName' => 'First Name',
        'lastName' => 'Last Name', 
        'email' => 'Email',
        'department' => 'Department',
        'travelDate' => 'Travel Date',
        'departureAirport' => 'Departure Airport',
        'arrivalAirport' => 'Arrival Airport',
        'reasonTravel' => 'Reason for Travel',
        'estimatedCost' => 'Estimated Cost',
        'projectName' => 'Project Name',
        'budgetCode' => 'Budget Code',
        'approver' => 'Approver'
    ];
    
    foreach ($requiredFields as $field => $label) {
        if (empty($_POST[$field])) {
            $errors[] = ['field' => $field, 'message' => "$label is required"];
        }
    }
    
    // Additional validation
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = ['field' => 'email', 'message' => "Please enter a valid email address"];
    }
    
    if (!empty($_POST['travelDate'])) {
        $travelDate = DateTime::createFromFormat('Y-m-d', $_POST['travelDate']);
        if (!$travelDate || $travelDate < new DateTime()) {
            $errors[] = ['field' => 'travelDate', 'message' => "Travel date must be in the future"];
        }
    }
    
    if (!empty($_POST['estimatedCost']) && (!is_numeric($_POST['estimatedCost']) || $_POST['estimatedCost'] < 0)) {
        $errors[] = ['field' => 'estimatedCost', 'message' => "Please enter a valid estimated cost"];
    }
    
    // Validate passport upload (optional)
    if (!empty($_FILES['passportUpload']['name']) && $_FILES['passportUpload']['error'] !== UPLOAD_ERR_OK) {
        $uploadError = '';
        switch ($_FILES['passportUpload']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $uploadError = "File is too large (exceeds server limit)";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $uploadError = "File is too large (exceeds form limit)";
                break;
            case UPLOAD_ERR_PARTIAL:
                $uploadError = "File was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $uploadError = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $uploadError = "Missing temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $uploadError = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $uploadError = "File upload stopped by extension";
                break;
            default:
                $uploadError = "Unknown upload error (code: " . $_FILES['passportUpload']['error'] . ")";
        }
        $errors[] = ['field' => 'passportUpload', 'message' => "Error uploading passport document: " . $uploadError];
    } elseif (!empty($_FILES['passportUpload']['name']) && $_FILES['passportUpload']['size'] > $maxUploadSize) {
        $errors[] = ['field' => 'passportUpload', 'message' => "Passport file is too large. Maximum size is " . round($maxUploadSize / 1024 / 1024) . "MB"];
    }
    
    // If there are validation errors, redirect back with errors
    if (!empty($errors)) {
        redirectWithErrors($errors, $formData);
    }
    
    // Sanitize and validate input data
    $data = [
        'firstName' => trim($_POST['firstName']),
        'lastName' => trim($_POST['lastName']),
        'email' => filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL),
        'department' => trim($_POST['department']),
        'travelDate' => $_POST['travelDate'],
        'departureAirport' => trim($_POST['departureAirport']),
        'arrivalAirport' => trim($_POST['arrivalAirport']),
        'reasonTravel' => trim($_POST['reasonTravel']),
        'estimatedCost' => floatval($_POST['estimatedCost']),
        'projectName' => trim($_POST['projectName']),
        'budgetCode' => trim($_POST['budgetCode']),
        'approver' => $_POST['approver'],
        'requester' => !empty($_POST['requester']) ? trim($_POST['requester']) : trim($_POST['firstName']) . ' ' . trim($_POST['lastName'])
    ];

    // Generate shorter unique request ID (8 characters) with uniqueness check
    $requestId = generateUniqueRequestId();
    
    // Process passport upload (optional)
    $passportFile = null;
    if (!empty($_FILES['passportUpload']['name'])) {
        try {
            $passportFile = processUploadedFile($_FILES['passportUpload'], $uploadDir, 'passport_' . $requestId);
        } catch (Exception $e) {
            redirectWithErrors([['field' => 'passportUpload', 'message' => $e->getMessage()]], $formData);
        }
    }
    
    // Process additional document (optional - single file only)
    $additionalFile = null;
    if (!empty($_FILES['additionalDocuments']['name'])) {
        // Handle single file upload
        if ($_FILES['additionalDocuments']['size'] > $maxUploadSize) {
            redirectWithErrors([['field' => 'additionalDocuments', 'message' => "Additional document is too large. Maximum size is " . round($maxUploadSize / 1024 / 1024) . "MB"]], $formData);
        }
        
        try {
            $additionalFile = processUploadedFile($_FILES['additionalDocuments'], $uploadDir, 'additional_' . $requestId);
        } catch (Exception $e) {
            redirectWithErrors([['field' => 'additionalDocuments', 'message' => "Error processing additional document: " . $e->getMessage()]], $formData);
        }
    }
    
    // Save to database
    try {
        $db = new Database();
        $pdo = $db->getConnection();
        
        // Handle additional file path (single file only)
        $additionalFilePath = $additionalFile ? $additionalFile['file_path'] : null;
        
        // Insert main request
        $sql = "INSERT INTO travel_requests (
            request_id, first_name, last_name, email, department, 
            travel_date, departure_airport, arrival_airport, 
            reason_travel, estimated_cost, project_name, budget_code, 
            approver, requester, passport_file, additional_file, created_at, status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending'
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $requestId,
            $data['firstName'],
            $data['lastName'],
            $data['email'],
            $data['department'],
            $data['travelDate'],
            $data['departureAirport'],
            $data['arrivalAirport'],
            $data['reasonTravel'],
            $data['estimatedCost'],
            $data['projectName'],
            $data['budgetCode'],
            $data['approver'],
            $data['requester'],
            $passportFile ? $passportFile['file_path'] : null,
            $additionalFilePath
        ]);
        
        // Get approver email from database using the approver ID
        $approverEmail = null;
        $approverName = null;
        
        try {
            $approverStmt = $pdo->prepare("SELECT email, first_name FROM users WHERE id = ?");
            $approverStmt->execute([$data['approver']]);
            $approverData = $approverStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($approverData) {
                $approverEmail = $approverData['email'];
                $approverName = $approverData['first_name'];
            } else {
                error_log("Approver not found for ID: " . $data['approver']);
                $approverEmail = null; // Will skip approver email
            }
        } catch (PDOException $e) {
            error_log("Database error fetching approver: " . $e->getMessage());
            $approverEmail = null; // Will skip approver email
        }
        
        // Send email notifications after successful database save
        $emailData = [
            'requestId' => $requestId,
            'firstName' => $data['firstName'],
            'lastName' => $data['lastName'],
            'department' => $data['department'],
            'travelDate' => $data['travelDate'],
            'departureAirport' => $data['departureAirport'],
            'arrivalAirport' => $data['arrivalAirport'],
            'reasonTravel' => $data['reasonTravel'],
            'estimatedCost' => $data['estimatedCost'],
            'projectName' => $data['projectName'],
            'budgetCode' => $data['budgetCode'],
            'approver' => $approverName ? $approverName : 'Unknown Approver'
        ];
        
        // Send email to traveller
        $travellerSubject = "Travel Request Submitted Successfully - " . $requestId;
        $travellerMessage = generateTravelRequestEmailTemplate($emailData, false);
        $travellerEmailSent = false;
        
        try {
            $travellerEmailSent = sendEmailNotification($data['email'], $travellerSubject, $travellerMessage);
        } catch (Exception $e) {
            error_log("Failed to send email to traveller: " . $e->getMessage());
        }
        
        
        // Send email to approver (only if we have valid email)
        $approverEmailSent = false;
        
        if ($approverEmail) {
            $approverSubject = "Travel Request Approval Required - " . $requestId;
            $approverMessage = generateTravelRequestEmailTemplate($emailData, true);
            
            try {
                $approverEmailSent = sendEmailNotification($approverEmail, $approverSubject, $approverMessage);
            } catch (Exception $e) {
                error_log("Failed to send email to approver: " . $e->getMessage());
            }
        } else {
            error_log("No valid approver email found for approver ID: " . $data['approver']);
        }
        
        // Log email sending results
        if ($travellerEmailSent) {
            error_log("Travel request notification sent to traveller: " . $data['email']);
        } else {
            error_log("Failed to send travel request notification to traveller: " . $data['email']);
        }
        
        if ($approverEmailSent) {
            error_log("Travel request notification sent to approver: " . $approverEmail . " (" . $approverName . ")");
        } else {
            error_log("Failed to send travel request notification to approver: " . ($approverEmail ? $approverEmail : 'No email found') . " (ID: " . $data['approver'] . ")");
        }
        
    } catch (PDOException $e) {
        // If database fails, redirect with error
        error_log("Database error in travel request: " . $e->getMessage());
        redirectWithErrors([], $formData, "Database error occurred. Please try again later.".$e->getMessage());
    }
    
    // Success response
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Travel Request Submitted - Mowaa</title>
        <link href="../assets/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="../assets/css/icons.css" rel="stylesheet">
        <style>
            body { font-family: 'Inter', 'Segoe UI', sans-serif; background-color: #f8f9fa; }
            .success-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
            .success-card { max-width: 600px; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
        </style>
    </head>
    <body>
        <div class="success-container">
            <div class="card success-card">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="fe fe-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h2 class="text-success mb-3">Travel Request Submitted Successfully!</h2>
                    <p class="text-muted mb-4">Your travel request has been processed and submitted for approval.</p>
                    
                    <div class="row text-start mb-4">
                        <div class="col-md-6">
                            <strong>Request ID:</strong><br>
                            <code><?php echo htmlspecialchars($requestId); ?></code>
                        </div>
                        <div class="col-md-6">
                            <strong>Traveller:</strong><br>
                            <?php echo htmlspecialchars($data['firstName'] . ' ' . $data['lastName']); ?>
                        </div>
                        <div class="col-md-6 mt-3">
                            <strong>Travel Date:</strong><br>
                            <?php echo htmlspecialchars($data['travelDate']); ?>
                        </div>
                        <div class="col-md-6 mt-3">
                            <strong>Estimated Cost:</strong><br>
                            $<?php echo number_format($data['estimatedCost'], 2); ?>
                        </div>
                    </div>
                    
                    <?php if ($travellerEmailSent || $approverEmailSent): ?>
                    <div class="alert alert-success">
                        <strong>✓ Email Notifications:</strong><br>
                        <?php if ($travellerEmailSent): ?>
                        • Confirmation email sent to you (<?php echo htmlspecialchars($data['email']); ?>)<br>
                        <?php endif; ?>
                        <?php if ($approverEmailSent): ?>
                        • Approval request sent to <?php echo htmlspecialchars($approverName . ' (' . $approverEmail . ')'); ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!$travellerEmailSent || !$approverEmailSent): ?>
                    <div class="alert alert-warning">
                        <strong>⚠ Email Notification Status:</strong><br>
                        <?php if (!$travellerEmailSent): ?>
                        • Unable to send confirmation email to you (<?php echo htmlspecialchars($data['email']); ?>)<br>
                        <?php endif; ?>
                        <?php if (!$approverEmailSent): ?>
                        • Unable to send approval request to <?php echo htmlspecialchars($approverName ? $approverName . ' (' . $approverEmail . ')' : 'Selected approver (ID: ' . $data['approver'] . ')'); ?><br>
                        <?php endif; ?>
                        The request was still saved successfully.
                    </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <strong>Next Steps:</strong><br>
                        • Your request will be reviewed by the selected approver<br>
                        • You will receive an email notification once approved/rejected<br>
                        • Keep your Request ID for future reference
                    </div>
                    
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="travel-request.php" class="btn btn-primary">Submit Another Request</a>
                        <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    // General error - redirect back to form
    redirectWithErrors([], $formData, $e->getMessage());
}
?>
