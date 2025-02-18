<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

//------------------------SAVE--------------------------------------------------

if(isset($_POST['save'])){
    
    $firstName=$_POST['firstName'];
    $lastName=$_POST['lastName'];
    $gphone=$_POST['gphone'];

    // Get the next available Admission Number
    $admissionNumber = getNextAdmissionNumber($conn);
    $classId=$_POST['classId'];
    $classArmId=$_POST['classArmId'];
    $dateCreated = date("Y-m-d");
   
    // Check if Admission Number already exists
    $query=mysqli_query($conn,"select * from tblstudents where admissionNumber ='$admissionNumber'");
    $ret=mysqli_fetch_array($query);

    if($ret > 0){ 
        $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>This Admission Number Already Exists!</div>";
    }
    else{
        // Insert new student record
        $query=mysqli_query($conn,"insert into tblstudents(firstName,lastName,gphone,admissionNumber,password,classId,classArmId,dateCreated) 
        value('$firstName','$lastName','$gphone','$admissionNumber','12345','$classId','$classArmId','$dateCreated')");

        if ($query) {
            $statusMsg = "<div class='alert alert-success'  style='margin-right:700px;'>Created Successfully!</div>";
        }
        else {
            $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
        }
    }
}

//------------------Get the next available admission number---------------
function getNextAdmissionNumber($conn) {
    // Get the highest admission number
    $query = "SELECT MAX(admissionNumber) AS maxAdmission FROM tblstudents";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    // If no records found, start from 1001
    if ($row['maxAdmission'] == NULL) {
        return '1001';
    } else {
        return $row['maxAdmission'] + 1;
    }
}

//---------------------------------------EDIT------------------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "edit") {
    $Id= $_GET['Id'];
    $query=mysqli_query($conn,"select * from tblstudents where Id ='$Id'");
    $row=mysqli_fetch_array($query);

    //------------UPDATE-----------------------------
    if(isset($_POST['update'])){
        $firstName=$_POST['firstName'];
        $lastName=$_POST['lastName'];
        $gphone=$_POST['gphone'];

        // Use the existing admission number, don't update it
        $admissionNumber = $row['admissionNumber'];  // Ensure admission number is not updated
        $classId=$_POST['classId'];
        $classArmId=$_POST['classArmId'];
        $dateCreated = date("Y-m-d");

        // Update the student record, excluding admissionNumber
        $query=mysqli_query($conn,"update tblstudents set firstName='$firstName', lastName='$lastName',
        gphone='$gphone', classId='$classId', classArmId='$classArmId' where Id='$Id'");

        if ($query) {
            echo "<script type = \"text/javascript\">
            window.location = (\"createStudents.php\")
            </script>";
        } else {
            $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
        }
    }
}

//--------------------------------DELETE------------------------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "delete") {
    $Id= $_GET['Id'];
    $classArmId= $_GET['classArmId'];

    $query = mysqli_query($conn,"DELETE FROM tblstudents WHERE Id='$Id'");

    if ($query == TRUE) {
        echo "<script type = \"text/javascript\">
        window.location = (\"createStudents.php\")
        </script>";
    } else {
        $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>"; 
    }
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
    <?php include 'includes/title.php';?>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">

    <script>
        function classArmDropdown(str) {
            if (str == "") {
                document.getElementById("txtHint").innerHTML = "";
                return;
            } else { 
                if (window.XMLHttpRequest) {
                    // code for IE7+, Firefox, Chrome, Opera, Safari
                    xmlhttp = new XMLHttpRequest();
                } else {
                    // code for IE6, IE5
                    xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
                }
                xmlhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        document.getElementById("txtHint").innerHTML = this.responseText;
                    }
                };
                xmlhttp.open("GET","ajaxClassArms2.php?cid="+str,true);
                xmlhttp.send();
            }
        }
    </script>
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
                        <h1 class="h3 mb-0 text-gray-800">Create Students</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Create Students</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Form Basic -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Create Students</h6>
                                    <?php echo $statusMsg; ?>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Firstname<span class="text-danger ml-2">*</span></label>
                                                <input type="text" class="form-control" name="firstName" value="<?php echo $row['firstName'];?>" id="exampleInputFirstName" required>
                                            </div>
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Lastname<span class="text-danger ml-2">*</span></label>
                                                <input type="text" class="form-control" name="lastName" value="<?php echo $row['lastName'];?>" id="exampleInputFirstName" required >
                                            </div>
                                        </div>
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Guardian Phone No.<span class="text-danger ml-2">*</span></label>
                                                <input type="text" class="form-control" name="gphone" value="<?php echo $row['gphone']; ?>" 
                                                    id="exampleInputFirstName" 
                                                    pattern="\d{10}" 
                                                    title="Phone number must be exactly 10 digits" 
                                                    required>
                                            </div>
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Admission Number<span class="text-danger ml-2">*</span></label>
                                                <input type="text" class="form-control" readonly value="<?php echo ($row['admissionNumber'] ? $row['admissionNumber'] : getNextAdmissionNumber($conn)); ?>" id="nextAdmissionNo" >
                                                <small class="text-muted">Next Available Admission Number</small>
                                            </div>
                                        </div>
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Select Class<span class="text-danger ml-2">*</span></label>
                                                <?php
                                                    $qry= "SELECT * FROM tblclass ORDER BY className ASC";
                                                    $result = $conn->query($qry);
                                                    if ($result->num_rows > 0){
                                                        echo ' <select required name="classId" onchange="classArmDropdown(this.value)" class="form-control mb-3">';
                                                        echo'<option value="">--Select Class--</option>';
                                                        while ($rows = $result->fetch_assoc()){
                                                            echo'<option value="'.$rows['Id'].'" '.($rows['Id'] == $row['classId'] ? 'selected' : '').'>'.$rows['className'].'</option>';
                                                        }
                                                        echo '</select>';
                                                    }
                                                ?>  
                                            </div>
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Class Arm<span class="text-danger ml-2">*</span></label>
                                                <div id='txtHint'>
                                                    <!-- Dynamic Class Arm will load here -->
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                            if (isset($Id)) {
                                        ?>
                                        <button type="submit" name="update" class="btn btn-warning">Update</button>
                                        <?php
                                            } else {
                                        ?>
                                        <button type="submit" name="save" class="btn btn-primary">Save</button>
                                        <?php
                                            }         
                                        ?>
                                    </form>
                                </div>
                            </div>
                            <!-- Input Group -->
                        </div>
                    </div>

                    <!-- All Students Table -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">All Student</h6>
                                </div>
                                <div class="table-responsive p-3">
                                    <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>First Name</th>
                                                <th>Last Name</th>
                                                <th>Guardian Phone No.</th>
                                                <th>Admission No</th>
                                                <th>Class</th>
                                                <th>Class Arm</th>
                                                <th>Date Created</th>
                                                <th>Edit</th>
                                                <th>Delete</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                $query = "SELECT tblstudents.Id, tblclass.className, tblclassarms.classArmName, tblclassarms.Id AS classArmId, tblstudents.firstName,
                                                tblstudents.lastName, tblstudents.gphone, tblstudents.admissionNumber, tblstudents.dateCreated
                                                FROM tblstudents
                                                INNER JOIN tblclass ON tblclass.Id = tblstudents.classId
                                                INNER JOIN tblclassarms ON tblclassarms.Id = tblstudents.classArmId";
                                                
                                                $rs = $conn->query($query);
                                                if($rs->num_rows > 0) {
                                                    $sn = 0;
                                                    while ($rows = $rs->fetch_assoc()) {
                                                        $sn++;
                                                        echo "<tr>
                                                                <td>$sn</td>
                                                                <td>{$rows['firstName']}</td>
                                                                <td>{$rows['lastName']}</td>
                                                                <td>{$rows['gphone']}</td>
                                                                <td>{$rows['admissionNumber']}</td>
                                                                <td>{$rows['className']}</td>
                                                                <td>{$rows['classArmName']}</td>
                                                                <td>{$rows['dateCreated']}</td>
                                                                <td><a href='?action=edit&Id={$rows['Id']}'><i class='fas fa-fw fa-edit'></i></a></td>
                                                                <td><a href='?action=delete&Id={$rows['Id']}'><i class='fas fa-fw fa-trash'></i></a></td>
                                                              </tr>";
                                                    }
                                                } else {
                                                    echo "<div class='alert alert-danger' role='alert'>No Record Found!</div>";
                                                }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>  <!-- End of All Students Table -->

                </div> <!-- Container Fluid -->
            </div> <!-- Content -->
        </div> <!-- Content Wrapper -->
    </div> <!-- Wrapper -->

    <!-- Scroll to top -->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>
    <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#dataTable').DataTable(); 
            $('#dataTableHover').DataTable();
        });
    </script>
</body>
</html>
