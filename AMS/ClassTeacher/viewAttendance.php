<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (isset($_POST['export'])) {
    // Export the data to XLS
    $dateTaken = $_POST['dateTaken'];

    // Get class name and class arm for the header
    $classQuery = "SELECT tblclass.className, tblclassarms.classArmName 
                   FROM tblclass
                   INNER JOIN tblclassarms ON tblclassarms.Id = '$_SESSION[classArmId]'
                   WHERE tblclass.Id = '$_SESSION[classId]'";

    $classResult = $conn->query($classQuery);
    $classRow = $classResult->fetch_assoc();
    $className = $classRow['className'];
    $classArmName = $classRow['classArmName'];
    
    // Query to fetch attendance data
    $query = "SELECT tblstudents.firstName, tblstudents.lastName, tblstudents.admissionNumber, tblattendance.status
              FROM tblattendance
              INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
              WHERE tblattendance.dateTimeTaken = '$dateTaken' 
              AND tblattendance.classId = '$_SESSION[classId]' 
              AND tblattendance.classArmId = '$_SESSION[classArmId]'
              ORDER BY tblstudents.admissionNumber";

    $result = $conn->query($query);

    // Generate the XLS file
    if ($result->num_rows > 0) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="attendance_report.xls"');
        
        // Print class and class arm with date as header
        echo "Class: $className (Class Arm: $classArmName) [Date: $dateTaken]\n\n";
        
        // Print table headers
        echo "Admission No\tStudent Name\tStatus\n";
        
        // Print table data
        while ($row = $result->fetch_assoc()) {
            $status = $row['status'] == '1' ? "Present" : "Absent";
            echo $row['admissionNumber'] . "\t" . $row['firstName'] . " " . $row['lastName'] . "\t" . $status . "\n";
        }
    } else {
        echo "No data found for the selected date.";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">
  <link href="img/logo/attnlg.jpg" rel="icon">
  <title>Dashboard</title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>

<body id="page-top">
  <div id="wrapper">
    <!-- Sidebar -->
    <?php include "Includes/sidebar.php";?>
    <!-- Sidebar -->
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <!-- TopBar -->
        <?php include "Includes/topbar.php";?>
        <!-- Topbar -->

        <!-- Container Fluid-->
        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">View Class Attendance</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">View Class Attendance</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Form Basic -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">View Class Attendance</h6>
                  <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                    <div class="form-group row mb-3">
                        <div class="col-xl-6">
                        <label class="form-control-label">Select Date<span class="text-danger ml-2">*</span></label>
                            <input type="date" class="form-control" name="dateTaken" id="exampleInputFirstName" placeholder="Class Arm Name">
                        </div>
                    </div>
                    <button type="submit" name="view" class="btn btn-primary">View Attendance</button>
                    <button type="submit" name="export" class="btn btn-success">Export as XLS</button>  <!-- Export Button -->
                  </form>
                </div>
              </div>

              <!-- Input Group -->
              <div class="row">
                <div class="col-lg-12">
                  <div class="card mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                      <h6 class="m-0 font-weight-bold text-primary">Class Attendance</h6>
                    </div>
                    <div class="table-responsive p-3">
                      <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                        <thead class="thead-light">
                          <tr>
                            <th>Admission No</th>
                            <th>Student Name</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                       
                        <tbody>

                          <?php

                            if(isset($_POST['view'])){

                              $dateTaken =  $_POST['dateTaken'];

                              $query = "SELECT tblattendance.Id, tblattendance.status, tblattendance.dateTimeTaken, 
                                        tblstudents.firstName, tblstudents.lastName, tblstudents.admissionNumber
                                        FROM tblattendance
                                        INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
                                        WHERE tblattendance.dateTimeTaken = '$dateTaken' 
                                        AND tblattendance.classId = '$_SESSION[classId]' 
                                        AND tblattendance.classArmId = '$_SESSION[classArmId]'
                                        ORDER BY tblstudents.admissionNumber";  // Order by admission number
                              $rs = $conn->query($query);
                              $num = $rs->num_rows;
                              $sn = 0;
                              $status = "";
                              
                              if($num > 0) { 
                                while ($rows = $rs->fetch_assoc()) {
                                    $status = $rows['status'] == '1' ? "Present" : "Absent";
                                    $colour = $rows['status'] == '1' ? "#00FF00" : "#FF0000";
                                    $sn++;
                                    echo "
                                      <tr>
                                        <td>".$rows['admissionNumber']."</td>
                                        <td>".$rows['firstName']." ".$rows['lastName']."</td>
                                        <td style='background-color:".$colour."'>".$status."</td>
                                      </tr>";
                                }
                              } else {
                                echo "<div class='alert alert-danger' role='alert'>No Record Found!</div>";
                              }
                            }
                          ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!--Row-->
          </div>
          <!---Container Fluid-->
        </div>
        <!-- Footer -->
        <?php include "Includes/footer.php";?>
        <!-- Footer -->
      </div>
    </div>

    <!-- Scroll to top -->
    <a class="scroll-to-top rounded" href="#page-top">
      <i class="fas fa-angle-up"></i>
    </a>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>
    <!-- Page level plugins -->
    <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script>
      $(document).ready(function () {
        $('#dataTable').DataTable(); // ID From dataTable 
        $('#dataTableHover').DataTable(); // ID From dataTable with Hover
      });
    </script>
  </body>

</html>
