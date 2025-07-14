<?php
session_start();

// Set maximum execution time for file processing
ini_set('max_execution_time', 300);

// Configuration
$uploadDir = '../uploads/travel-requests/';
$maxFileSize = 1024 * 1024; // 1MB final compressed size
$maxUploadSize = 5 * 1024 * 1024; // 5MB initial upload limit (reasonable for high-quality images)
$allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];

// Database configuration (adjust as needed)
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'mowaa_db';

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
    
    // Generate unique filename
    $uniqueName = $prefix . '_' . uniqid() . '_' . time() . '.' . $fileExtension;
    $destination = $uploadDir . $uniqueName;
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

    // Generate unique request ID
    $requestId = uniqid('TR_', true);
    
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
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
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
        
    } catch (PDOException $e) {
        // If database fails, redirect with error
        error_log("Database error in travel request: " . $e->getMessage());
        redirectWithErrors([], $formData, "Database error occurred. Please try again later.");
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
