<?php
session_start();

// Get errors and form data from session if they exist
$errors = isset($_SESSION['form_errors']) ? $_SESSION['form_errors'] : [];
$formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
$generalError = isset($_SESSION['general_error']) ? $_SESSION['general_error'] : '';

// Clear session data after retrieving
unset($_SESSION['form_errors'], $_SESSION['form_data'], $_SESSION['general_error']);

// Helper function to get form field value
function getFieldValue($fieldName, $formData, $default = '') {
    return isset($formData[$fieldName]) ? htmlspecialchars($formData[$fieldName]) : $default;
}

// Helper function to check if field has error
function hasFieldError($fieldName, $errors) {
    foreach ($errors as $error) {
        if (isset($error['field']) && $error['field'] === $fieldName) {
            return true;
        }
    }
    return false;
}

// Helper function to get field error message
function getFieldError($fieldName, $errors) {
    foreach ($errors as $error) {
        if (isset($error['field']) && $error['field'] === $fieldName) {
            return $error['message'];
        }
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="close">

<head>

   <!-- Meta Data -->
   <meta charset="UTF-8">
   <meta name='viewport' content='width=device-width, initial-scale=1.0'>
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <title> Mowaa - Travel Request </title>
   <meta name="Description" content=" ">
   <meta name="Author" content=" ">
   <meta name="keywords" content=" ">

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



   <!-- Prism CSS -->
   <link rel="stylesheet" href="../assets/libs/prismjs/themes/prism-coy.min.css">

   <!-- Custom CSS Override -->
   <style>
      @media (min-width: 992px) {
         .app-content {
            margin-inline-start: 0 !important;
         }
      }
      
      /* Custom Font Family Override */
      body, .form-control, .form-select, .btn, .card-title, .main-content-title {
         font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
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
      <header class="app-header">

         <!-- Start::main-header-container -->
         <div class="main-header-container container-fluid">

            <!-- Start::header-content-left -->
            <div class="header-content-left">

               <!-- Start::header-element -->
               <div class="header-element">
                  <div class="horizontal-logo">                    <a href="index.php" class="header-logo">
                        <img src="../assets/images/brand-logos/logo.png" alt="logo" class="desktop-logo">
                        <img src="../assets/images/brand-logos/logo.png" alt="logo" class="toggle-logo">
                        <img src="../assets/images/brand-logos/logo.png" alt="logo" class="desktop-dark">
                        <img src="../assets/images/brand-logos/logo.png" alt="logo" class="toggle-dark">
                        <img src="../assets/images/brand-logos/logo.png" alt="logo" class="desktop-white">
                        <img src="../assets/images/brand-logos/logo.png" alt="logo" class="toggle-white">
                    </a>
                  </div>
               </div>
               <!-- End::header-element -->




            </div>
            <!-- End::header-content-left -->

            <!-- Start::header-content-right -->
            <div class="header-content-right">

               <!-- Start::header-element -->
               <div class="header-element header-theme-mode">
                  <!-- Start::header-link|layout-setting -->
                  <a href="javascript:void(0);" class="header-link layout-setting">
                     <span class="light-layout">
                        <!-- Start::header-link-icon -->
                        <i class="fe fe-moon header-link-icon lh-2"></i>
                        <!-- End::header-link-icon -->
                     </span>
                     <span class="dark-layout">
                        <!-- Start::header-link-icon -->
                        <i class="fe fe-sun header-link-icon lh-2"></i>
                        <!-- End::header-link-icon -->
                     </span>
                  </a>
                  <!-- End::header-link|layout-setting -->
               </div>
               <!-- End::header-element -->


            </div>
            <!-- End::header-content-right -->

         </div>
         <!-- End::main-header-container -->

      </header>
      <!-- /app-header -->
      <!-- Start::app-sidebar -->

      <!-- End::app-sidebar -->

      <!--APP-CONTENT START-->
      <div class="main-content app-content">
         <div class="container">

            <!-- Page Header -->
            <div class="d-md-flex d-block align-items-center justify-content-between page-header-breadcrumb">
               <div class="w-100">
                  <h2 class="main-content-title mb-1 text-center">MOWAA TRAVEL REQUEST FORM</h2>
                  <!-- <ol class="breadcrumb mb-0">
                              <li class="breadcrumb-item"><a href="javascript:void(0)">Forms</a></li>
                              <li class="breadcrumb-item active" aria-current="page">Floating labels</li>
                          </ol> -->
               </div>
            </div>

            <!-- Page Header Close -->

            <!-- Display General Errors -->
            <?php if (!empty($generalError)): ?>
            <div class="col-10 mx-auto mb-4">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fe fe-alert-triangle me-2"></i>
                    <strong>Error:</strong> <?php echo htmlspecialchars($generalError); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Display Validation Errors -->
            <?php if (!empty($errors)): ?>
            <div class="col-10 mx-auto mb-4">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fe fe-alert-circle me-2"></i>
                    <strong>Please correct the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error['message']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Form Container with Bootstrap col-8 for proper responsive width -->
            <div class="col-10 mx-auto">
            <!-- Travel Request Form -->
            <form id="travelRequestForm" method="POST" action="process-travel-request.php" enctype="multipart/form-data" novalidate>
            
            <!-- Start:: row-1 -->
            <div class="row row-sm">
               <div class="col-xl-6">
                  <div class="card custom-card">
                     <div class="card-header justify-content-between">
                        <div class="card-title">
                           TRAVELLER DETAILS
                        </div>

                     </div>
                     <div class="card-body">
                        <!-- First Row: First Name and Last Name -->
                        <div class="row g-2 mb-3">
                           <div class="col-md-6">
                              <div class="form-floating">
                                 <input type="text" class="form-control<?php echo hasFieldError('firstName', $errors) ? ' is-invalid' : ''; ?>" 
                                    id="firstName" name="firstName" placeholder="First Name" 
                                    value="<?php echo getFieldValue('firstName', $formData); ?>" required>
                                 <label for="firstName">First Name</label>
                                 <?php if (hasFieldError('firstName', $errors)): ?>
                                    <div class="invalid-feedback">
                                       <?php echo getFieldError('firstName', $errors); ?>
                                    </div>
                                 <?php endif; ?>
                              </div>
                           </div>
                           <div class="col-md-6">
                              <div class="form-floating">
                                 <input type="text" class="form-control<?php echo hasFieldError('lastName', $errors) ? ' is-invalid' : ''; ?>" 
                                    id="lastName" name="lastName" placeholder="Last Name" 
                                    value="<?php echo getFieldValue('lastName', $formData); ?>" required>
                                 <label for="lastName">Last Name</label>
                                 <?php if (hasFieldError('lastName', $errors)): ?>
                                    <div class="invalid-feedback">
                                       <?php echo getFieldError('lastName', $errors); ?>
                                    </div>
                                 <?php endif; ?>
                              </div>
                           </div>
                        </div>

                        <!-- Second Row: Email -->
                        <div class="form-floating mb-3">
                           <input type="email" class="form-control<?php echo hasFieldError('email', $errors) ? ' is-invalid' : ''; ?>" 
                              id="email" name="email" placeholder="name@example.com" 
                              value="<?php echo getFieldValue('email', $formData); ?>" required>
                           <label for="floatingInput">Email address</label>
                           <?php if (hasFieldError('email', $errors)): ?>
                              <div class="invalid-feedback">
                                 <?php echo getFieldError('email', $errors); ?>
                              </div>
                           <?php endif; ?>
                        </div>

                        <!-- Third Row: Department -->
                        <div class="form-floating">
                           <input type="text" class="form-control<?php echo hasFieldError('department', $errors) ? ' is-invalid' : ''; ?>" 
                              id="department" name="department" placeholder="Department" 
                              value="<?php echo getFieldValue('department', $formData); ?>" required>
                           <label for="department">Department</label>
                           <?php if (hasFieldError('department', $errors)): ?>
                              <div class="invalid-feedback">
                                 <?php echo getFieldError('department', $errors); ?>
                              </div>
                           <?php endif; ?>
                        </div>
                     </div>
                     <div class="card-footer d-none border-top-0">

                     </div>
                  </div>
               </div>
               <div class="col-xl-6">
                  <div class="card custom-card">
                     <div class="card-header justify-content-between">
                        <div class="card-title">
                           TRAVEL DETAILS
                        </div>
                     </div>
                     <div class="card-body">
                        <!-- Date of Travel -->
                        <div class="form-floating mb-3">
                           <input type="date" class="form-control<?php echo hasFieldError('travelDate', $errors) ? ' is-invalid' : ''; ?>" 
                              id="travelDate" name="travelDate" placeholder="Select travel date" 
                              value="<?php echo getFieldValue('travelDate', $formData); ?>" required>
                           <label for="travelDate">Date of Travel</label>
                           <?php if (hasFieldError('travelDate', $errors)): ?>
                              <div class="invalid-feedback">
                                 <?php echo getFieldError('travelDate', $errors); ?>
                              </div>
                           <?php endif; ?>
                        </div>

                        <!-- Departure Airport -->
                        <div class="form-floating mb-3">
                           <input type="text" class="form-control<?php echo hasFieldError('departureAirport', $errors) ? ' is-invalid' : ''; ?>" 
                              id="departureAirport" name="departureAirport" placeholder="Departure Airport" 
                              value="<?php echo getFieldValue('departureAirport', $formData); ?>" required>
                           <label for="departureAirport">Departure Airport</label>
                           <?php if (hasFieldError('departureAirport', $errors)): ?>
                              <div class="invalid-feedback">
                                 <?php echo getFieldError('departureAirport', $errors); ?>
                              </div>
                           <?php endif; ?>
                        </div>

                        <!-- Arrival Airport -->
                        <div class="form-floating">
                           <input type="text" class="form-control<?php echo hasFieldError('arrivalAirport', $errors) ? ' is-invalid' : ''; ?>" 
                              id="arrivalAirport" name="arrivalAirport" placeholder="Arrival Airport" 
                              value="<?php echo getFieldValue('arrivalAirport', $formData); ?>" required>
                           <label for="arrivalAirport">Arrival Airport</label>
                           <?php if (hasFieldError('arrivalAirport', $errors)): ?>
                              <div class="invalid-feedback">
                                 <?php echo getFieldError('arrivalAirport', $errors); ?>
                              </div>
                           <?php endif; ?>
                        </div>
                     </div>
                     <div class="card-footer d-none border-top-0">

                     </div>
                  </div>
               </div>
            </div>
            <!-- End:: row-1 -->

            <!-- Start:: row-2 -->
            <div class="row row-sm">
               <div class="col-xl-6">
                  <div class="card custom-card">
                     <div class="card-header justify-content-between">
                        <div class="card-title">
                           PURPOSE AND BUDGET
                        </div>
                     </div>
                     <div class="card-body">
                        <!-- Reason for Travel -->
                        <div class="form-floating mb-3">
                           <textarea class="form-control<?php echo hasFieldError('reasonTravel', $errors) ? ' is-invalid' : ''; ?>" 
                              id="reasonTravel" name="reasonTravel" placeholder="Reason for travel" 
                              style="height: 100px;" required><?php echo getFieldValue('reasonTravel', $formData); ?></textarea>
                           <label for="reasonTravel">Reason for Travel</label>
                           <?php if (hasFieldError('reasonTravel', $errors)): ?>
                              <div class="invalid-feedback">
                                 <?php echo getFieldError('reasonTravel', $errors); ?>
                              </div>
                           <?php endif; ?>
                        </div>

                        <!-- Estimated Cost -->
                        <div class="form-floating mb-3">
                           <input type="number" class="form-control<?php echo hasFieldError('estimatedCost', $errors) ? ' is-invalid' : ''; ?>" 
                              id="estimatedCost" name="estimatedCost" placeholder="0.00" step="0.01" 
                              value="<?php echo getFieldValue('estimatedCost', $formData); ?>" required>
                           <label for="estimatedCost">Estimated Cost (USD)</label>
                           <?php if (hasFieldError('estimatedCost', $errors)): ?>
                              <div class="invalid-feedback">
                                 <?php echo getFieldError('estimatedCost', $errors); ?>
                              </div>
                           <?php endif; ?>
                        </div>

                        <!-- Project Name and Budget Code Row -->
                        <div class="row g-2">
                           <div class="col-md-6">
                              <div class="form-floating">
                                 <input type="text" class="form-control<?php echo hasFieldError('projectName', $errors) ? ' is-invalid' : ''; ?>" 
                                    id="projectName" name="projectName" placeholder="Project Name" 
                                    value="<?php echo getFieldValue('projectName', $formData); ?>" required>
                                 <label for="projectName">Project Name</label>
                                 <?php if (hasFieldError('projectName', $errors)): ?>
                                    <div class="invalid-feedback">
                                       <?php echo getFieldError('projectName', $errors); ?>
                                    </div>
                                 <?php endif; ?>
                              </div>
                           </div>
                           <div class="col-md-6">
                              <div class="form-floating">
                                 <input type="text" class="form-control<?php echo hasFieldError('budgetCode', $errors) ? ' is-invalid' : ''; ?>" 
                                    id="budgetCode" name="budgetCode" placeholder="Budget Code" 
                                    value="<?php echo getFieldValue('budgetCode', $formData); ?>" required>
                                 <label for="budgetCode">Budget Code</label>
                                 <?php if (hasFieldError('budgetCode', $errors)): ?>
                                    <div class="invalid-feedback">
                                       <?php echo getFieldError('budgetCode', $errors); ?>
                                    </div>
                                 <?php endif; ?>
                              </div>
                           </div>
                        </div>
                     </div>
                     <div class="card-footer d-none border-top-0">

                     </div>
                  </div>
               </div>
               <div class="col-xl-6">
                  <div class="card custom-card">
                     <div class="card-header justify-content-between">
                        <div class="card-title">
                           DOCUMENTS
                        </div>
                     </div>
                     <div class="card-body">
                        <!-- Passport Upload -->
                        <div class="mb-3">
                           <label for="passportUpload" class="form-label">Passport (PDF or Image) </label>
                           <input type="file" class="form-control<?php echo hasFieldError('passportUpload', $errors) ? ' is-invalid' : ''; ?>" 
                              id="passportUpload" name="passportUpload" accept=".pdf,.jpg,.jpeg,.png,.gif" >
                           <?php if (hasFieldError('passportUpload', $errors)): ?>
                              <div class="invalid-feedback">
                                 <?php echo getFieldError('passportUpload', $errors); ?>
                              </div>
                           <?php endif; ?>
                        </div>

                        <!-- Additional Documents Upload -->
                        <div class="mb-3">
                           <label for="additionalDocuments" class="form-label">Additional Document (PDF or Image)</label>
                           <input type="file" class="form-control<?php echo hasFieldError('additionalDocuments', $errors) ? ' is-invalid' : ''; ?>" 
                              id="additionalDocuments" name="additionalDocuments" accept=".pdf,.jpg,.jpeg,.png,.gif">
                           <div class="form-text">Optional: Select one additional document if needed</div>
                           <?php if (hasFieldError('additionalDocuments', $errors)): ?>
                              <div class="invalid-feedback">
                                 <?php echo getFieldError('additionalDocuments', $errors); ?>
                              </div>
                           <?php endif; ?>
                        </div>
                     </div>
                     <div class="card-footer d-none border-top-0">

                     </div>
                  </div>
               </div>
            </div>
            <!-- End:: row-2 -->

            <!-- Start:: row-3 -->
            <div class="row row-sm">
               <div class="col-xl-6">
                  <div class="card custom-card">
                     <div class="card-header justify-content-between">
                        <div class="card-title">
                           APPROVER
                        </div>
                     </div>
                     <div class="card-body">
                        <!-- Approver Dropdown -->
                        <div class="form-floating">
                           <select class="form-select<?php echo hasFieldError('approver', $errors) ? ' is-invalid' : ''; ?>" 
                              id="approver" name="approver" aria-label="Select approver" required>
                              <option value="">Select Approver</option>
                              <option value="manager1"<?php echo getFieldValue('approver', $formData) === 'manager1' ? ' selected' : ''; ?>>John Smith - Department Manager</option>
                              <option value="manager2"<?php echo getFieldValue('approver', $formData) === 'manager2' ? ' selected' : ''; ?>>Sarah Johnson - Finance Manager</option>
                              <option value="manager3"<?php echo getFieldValue('approver', $formData) === 'manager3' ? ' selected' : ''; ?>>Michael Brown - Operations Manager</option>
                              <option value="director1"<?php echo getFieldValue('approver', $formData) === 'director1' ? ' selected' : ''; ?>>Lisa Davis - Regional Director</option>
                           </select>
                           <label for="approver">Approver</label>
                           <?php if (hasFieldError('approver', $errors)): ?>
                              <div class="invalid-feedback">
                                 <?php echo getFieldError('approver', $errors); ?>
                              </div>
                           <?php endif; ?>
                        </div>
                     </div>
                     <div class="card-footer d-none border-top-0">

                     </div>
                  </div>
               </div>
               <div class="col-xl-6">
                  <div class="card custom-card">
                     <div class="card-header justify-content-between">
                        <div class="card-title">
                           REQUESTER
                        </div>
                     </div>
                     <div class="card-body">
                        <!-- Requester -->
                        <div class="form-floating">
                           <input type="text" class="form-control<?php echo hasFieldError('requester', $errors) ? ' is-invalid' : ''; ?>" 
                              id="requester" name="requester" placeholder="Requester Name" 
                              value="<?php echo getFieldValue('requester', $formData); ?>">
                           <label for="requester">Name of Requester (if different from traveller)</label>
                           <div class="form-text">Leave blank if you are the traveller</div>
                           <?php if (hasFieldError('requester', $errors)): ?>
                              <div class="invalid-feedback">
                                 <?php echo getFieldError('requester', $errors); ?>
                              </div>
                           <?php endif; ?>
                        </div>
                     </div>
                     <div class="card-footer d-none border-top-0">

                     </div>
                  </div>
               </div>
            </div>
            <!-- End:: row-3 -->

            <!-- Start:: Submit and Reset Buttons Row -->
            <div class="row row-sm mt-4 mb-5">
               <div class="col-xl-12">
                  <div class="d-flex justify-content-center gap-3">
                     <button type="submit" class="btn btn-success btn-lg px-5" id="submitBtn">
                        <i class="fe fe-check me-2"></i>Submit Request
                     </button>
                     <button type="button" class="btn btn-danger btn-lg px-5" data-bs-toggle="modal" data-bs-target="#resetModal">
                        <i class="fe fe-refresh-cw me-2"></i>Reset Form
                     </button>
                  </div>
               </div>
            </div>
            <!-- End:: Submit and Reset Buttons Row -->

            </form>
            <!-- End Travel Request Form -->
            </div>
            <!-- End Form Container -->

         </div>
      </div>
      <!--APP-CONTENT CLOSE-->

      <!-- Submit Confirmation Modal -->
      <div class="modal fade" id="submitModal" tabindex="-1" aria-labelledby="submitModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
               <div class="modal-header bg-success text-white">
                  <h5 class="modal-title" id="submitModalLabel">
                     <i class="fe fe-check me-2"></i>Confirm Submission
                  </h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
               </div>
               <div class="modal-body">
                  <div class="text-center">
                     <i class="fe fe-check-circle text-success" style="font-size: 3rem;"></i>
                     <h4 class="mt-3">Submit Travel Request?</h4>
                     <p class="text-muted">Are you sure you want to submit this travel request? Once submitted, you may not be able to edit the details.</p>
                  </div>
               </div>
               <div class="modal-footer">
                  <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                     <i class="fe fe-x me-2"></i>Cancel
                  </button>
                  <button type="button" class="btn btn-success" onclick="submitForm()">
                     <i class="fe fe-check me-2"></i>Yes, Submit
                  </button>
               </div>
            </div>
         </div>
      </div>

      <!-- Reset Confirmation Modal -->
      <div class="modal fade" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
               <div class="modal-header bg-danger text-white">
                  <h5 class="modal-title" id="resetModalLabel">
                     <i class="fe fe-alert-triangle me-2"></i>Confirm Reset
                  </h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
               </div>
               <div class="modal-body">
                  <div class="text-center">
                     <i class="fe fe-alert-triangle text-danger" style="font-size: 3rem;"></i>
                     <h4 class="mt-3">Reset Form?</h4>
                     <p class="text-muted">Are you sure you want to reset the form? All entered data will be lost and cannot be recovered.</p>
                  </div>
               </div>
               <div class="modal-footer">
                  <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                     <i class="fe fe-x me-2"></i>Cancel
                  </button>
                  <button type="button" class="btn btn-danger" onclick="resetForm()">
                     <i class="fe fe-refresh-cw me-2"></i>Yes, Reset
                  </button>
               </div>
            </div>
         </div>
      </div>

       
      <!-- Footer Start -->
      <footer class="footer mt-auto py-3 bg-white text-center">
         <div class="container">
            <span class="text-muted"> Copyright © <span id="year"></span> <a
                  href="javascript:void(0);" class="text-dark fw-semibold">Spruha</a>.
               Designed with <span class="bi bi-heart-fill text-danger"></span> by <a href="javascript:void(0);">
                  <span class="fw-semibold text-primary text-decoration-underline">Spruko</span>
               </a> All
               rights
               reserved
            </span>
         </div>
      </footer>
      <!-- Footer End -->
       



   </div>


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

   <!-- Prism JS -->
   <script src="../assets/libs/prismjs/prism.js"></script>
   <script src="../assets/js/prism-custom.js"></script>

   <!-- Custom JS -->
   <script src="../assets/js/custom.js"></script>

   <!-- Travel Request Form JS -->
   <script>
      // Form validation and submission
      document.getElementById('travelRequestForm').addEventListener('submit', function(e) {
         e.preventDefault(); // Prevent default submission initially
         
         // Check if form is valid
         if (this.checkValidity()) {
            // Show confirmation modal if validation passes
            var submitModal = new bootstrap.Modal(document.getElementById('submitModal'));
            submitModal.show();
         } else {
            // Show validation messages
            this.classList.add('was-validated');
         }
      });

      function submitForm() {
         // Close the modal
         var submitModal = bootstrap.Modal.getInstance(document.getElementById('submitModal'));
         submitModal.hide();

         // Submit the form normally to the PHP processing page
         document.getElementById('travelRequestForm').submit();
      }

      function resetForm() {
         // Close the modal
         var resetModal = bootstrap.Modal.getInstance(document.getElementById('resetModal'));
         resetModal.hide();

         // Reset all form fields
         var form = document.getElementById('travelRequestForm');
         form.reset();
         form.classList.remove('was-validated');

         // Show success message
         alert('Form has been reset successfully!');
      }
   </script>

</body>

</html>