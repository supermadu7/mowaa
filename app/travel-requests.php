<?php
session_start();
require_once 'includes/auth.php';
require_once '../config/database.php';

// Require user to be logged in
requireLogin();

// Fetch travel requests data
try {
   $db = new Database();
   $connection = $db->getConnection();

   // Fetch all travel requests
   $sql = "SELECT 
                id, request_id, first_name, last_name, email, department, 
                travel_date, requester, estimated_cost, 
                status, created_at 
            FROM travel_requests 
            ORDER BY created_at DESC";

   $stmt = $connection->prepare($sql);
   $stmt->execute();
   $travelRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
   echo "<div class='alert alert-danger'>Error fetching travel requests: " . $e->getMessage() . "</div>";
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
   <title> MOWAA - View Travel Requests </title>

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


   <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
   <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css">
   <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">

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
            <a href="index.php" class="header-logo">
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
                  <h2 class="main-content-title fs-24 mb-1">Travel Requests</h2>
                  <ol class="breadcrumb mb-0">
                     <li class="breadcrumb-item"><a href="javascript:void(0)">Requests</a></li>
                     <li class="breadcrumb-item active" aria-current="page">View Requests</li>
                  </ol>
               </div>

            </div>

            <!-- Page Header Close -->

            <!-- Start:: row-4 -->
            <div class="row">
               <div class="col-xl-12">
                  <div class="card custom-card">
                     <div class="card-header">
                        <div class="card-title"> Requests list</div>
                     </div>
                     <div class="card-body">
                        <div class="table-responsive">
                           <table id="file-export" class="table table-bordered text-nowrap" style="width:100%">
                              <thead>
                                 <tr>
                                    <th>Traveller Name</th>
                                    <th>Department</th>
                                    <th>Travel Date</th>
                                    <th>Estimated Cost</th>
                                    <th>Requester</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                 </tr>
                              </thead>
                              <tbody>
                                 <?php

                                 foreach ($travelRequests as $request) {


                                 ?>
                                    <tr>
                                       <td><?php echo $request['first_name'] . " " . $request['last_name']   ?></td>
                                       <td><?php echo $request['department']  ?></td>
                                       <td><?php echo $request['travel_date']  ?></td>
                                       <td><?php echo $request['estimated_cost']  ?></td>
                                       <td><?php echo $request['requester']  ?></td>
                                       <td>
                                          <?php if ($request['status'] == 'pending') { ?>
                                             <span class="badge bg-warning"><?php echo $request['status']  ?></span>
                                          <?php } elseif ($request['status'] == 'approved') { ?>
                                             <span class="badge bg-success"><?php echo $request['status']  ?></span>
                                          <?php } else { ?>
                                             <span class="badge bg-danger"><?php echo $request['status']  ?></span>
                                          <?php } ?>
                                       <td>
                                          <a href="request-details.php?d=<?php echo $request['id'] ?>" class="btn btn-primary btn-sm">View</a>

                                       </td>
                                    </tr>
                                 <?php }  ?>

                              </tbody>
                           </table>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <!-- End:: row-4 -->



         </div>
      </div>
      <!-- End::app-content -->

      <!-- Footer Start -->
      <? include 'includes/footer.php'; ?>
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

   <!-- Jquery Cdn -->

   <!-- Datatables Cdn -->
   <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
   <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
   <script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
   <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
   <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.6/pdfmake.min.js"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
   <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

   <!-- Internal Datatables JS -->
   <script src="../assets/js/datatables.js"></script>

   <!-- Custom JS -->
   <script src="../assets/js/custom.js"></script>

</body>

</html>