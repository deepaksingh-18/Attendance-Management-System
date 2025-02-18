<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../Includes/dbcon.php';
include '../Includes/session.php';
use Twilio\Rest\Client;

$statusMsg = "";
$dateTaken = date("Y-m-d");

// Fetch class and class arm for the logged-in class teacher
$query = "SELECT tblclass.className, tblclassarms.classArmName 
FROM tblclassteacher
INNER JOIN tblclass ON tblclass.Id = tblclassteacher.classId
INNER JOIN tblclassarms ON tblclassarms.Id = tblclassteacher.classArmId
WHERE tblclassteacher.Id = '$_SESSION[userId]'";
$rs = $conn->query($query);
$rrw = $rs->fetch_assoc();

// Check if attendance has already been taken today
$qurty = mysqli_query($conn, "SELECT * FROM tblattendance WHERE classId = '$_SESSION[classId]' AND classArmId = '$_SESSION[classArmId]' AND dateTimeTaken='$dateTaken'");
$count = mysqli_num_rows($qurty);

// If no attendance record exists, insert all students as absent (status = 0)
if ($count == 0) {
    $qus = mysqli_query($conn, "SELECT * FROM tblstudents WHERE classId = '$_SESSION[classId]' AND classArmId = '$_SESSION[classArmId]'");
    while ($ros = $qus->fetch_assoc()) {
        mysqli_query($conn, "INSERT INTO tblattendance(admissionNo, classId, classArmId, status, dateTimeTaken) 
        VALUES ('$ros[admissionNumber]', '$_SESSION[classId]', '$_SESSION[classArmId]', '0', '$dateTaken')");
    }
    $statusMsg = "<div class='alert alert-success'>Attendance initialized successfully! Mark attendance now.</div>";
}

if (isset($_POST['save'])) {
  // Check if attendance has already been manually marked today (non-default status)
  $checkAttendance = mysqli_query($conn, "SELECT * FROM tblattendance 
      WHERE classId = '$_SESSION[classId]' 
      AND classArmId = '$_SESSION[classArmId]' 
      AND dateTimeTaken = '$dateTaken' 
      AND status = 1");

  if (mysqli_num_rows($checkAttendance) > 0) {
      // If attendance is already marked as present, show a message
      $statusMsg = "<div class='alert alert-warning'>Attendance has already been taken for today!</div>";
  } else {
      // Process attendance: Update attendance status
      $admissionNo = $_POST['admissionNo'] ?? [];
      $check = $_POST['check'] ?? [];

      foreach ($admissionNo as $index => $admissionNumber) {
          $status = in_array($admissionNumber, $check) ? 1 : 0; // 1 = present, 0 = absent
          mysqli_query($conn, "UPDATE tblattendance SET status = '$status' 
              WHERE admissionNo = '$admissionNumber' 
              AND classId = '$_SESSION[classId]' 
              AND classArmId = '$_SESSION[classArmId]' 
              AND dateTimeTaken = '$dateTaken'");
      }

      $statusMsg = "<div class='alert alert-success'>Attendance marked successfully for today!</div>";
  }
}
if (isset($_POST['notify'])) {
  $absentStudents = mysqli_query($conn, "SELECT tblstudents.gphone 
      FROM tblattendance 
      INNER JOIN tblstudents ON tblattendance.admissionNo = tblstudents.admissionNumber
      WHERE tblattendance.classId = '$_SESSION[classId]' 
      AND tblattendance.classArmId = '$_SESSION[classArmId]' 
      AND tblattendance.dateTimeTaken = '$dateTaken' 
      AND tblattendance.status = '0'");

  $guardianNumbers = [];
  while ($row = $absentStudents->fetch_assoc()) {
      $phone = $row['gphone'];
      if (strpos($phone, '+91') !== 0) {
          $phone = '+91' . $phone; // Ensure the phone number is in the correct format
      }
      $guardianNumbers[] = $phone;
  }

  if (count($guardianNumbers) > 0) {
      require __DIR__ . '/vendor/autoload.php';

      $sid = "ACd093840062e2e5d2756b49789eb2eb63";
      $token = "2802859cfba9b564f290a099bc072ad5";
      $twilio = new Client($sid, $token);

      $failedNumbers = []; // To track numbers that failed to receive the message
      try {
          foreach ($guardianNumbers as $phone) {
              try {
                  // Attempt to send the message
                  $twilio->messages->create($phone, [
                      "from" => "+12294848096", // Your Twilio number
                      "body" => "Dear Parent,Your ward is absent today"
                  ]);
              } catch (Exception $e) {
                  // Log the error for the specific number
                  $failedNumbers[] = $phone;
                  continue; // Continue with the next number even if the current one fails
              }
          }

          if (count($failedNumbers) > 0) {
              $statusMsg = "<div class='alert alert-warning'>Messages failed for the following numbers: " . implode(', ', $failedNumbers) . "</div>";
          } else {
              $statusMsg = "<div class='alert alert-success'>Notifications sent successfully to all absent students' guardians!</div>";
          }
      } catch (Exception $e) {
          $statusMsg = "<div class='alert alert-danger'>Error sending notifications: " . $e->getMessage() . "</div>";
      }
  } else {
      $statusMsg = "<div class='alert alert-warning'>No absent students found for today.</div>";
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
  <title>Dashboard</title>
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
                xmlhttp = new XMLHttpRequest();
            } else {
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xmlhttp.onreadystatechange = function () {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById("txtHint").innerHTML = this.responseText;
                }
            };
            xmlhttp.open("GET", "ajaxClassArms2.php?cid=" + str, true);
            xmlhttp.send();
        }
    }
    </script>
</head>

<body id="page-top">
  <div id="wrapper">
    <?php include "Includes/sidebar.php"; ?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include "Includes/topbar.php"; ?>
        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Take Attendance (Today's Date: <?php echo date("m-d-Y"); ?>)</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">All Students in Class</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <form method="post">
                <div class="row">
                  <div class="col-lg-12">
                    <div class="card mb-4">
                      <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">All Students in (<?php echo $rrw['className'] . ' - ' . $rrw['classArmName']; ?>) Class</h6>
                        <h6 class="m-0 font-weight-bold text-danger">Note: <i>Click on the checkboxes besides each student to take attendance!</i></h6>
                      </div>
                      <div class="table-responsive p-3">
                        <?php echo $statusMsg; ?>
                        <table class="table align-items-center table-flush table-hover">
                          <thead class="thead-light">
                            <tr>
                              <th>#</th>
                              <th>First Name</th>
                              <th>Last Name</th>
                              <th>Guardian Phone No.</th>
                              <th>Admission No</th>
                              <th>Class</th>
                              <th>Class Arm</th>
                              <th>Check</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $query = "SELECT tblstudents.Id, tblstudents.admissionNumber, tblclass.className, tblclass.Id AS classId, tblclassarms.classArmName, tblclassarms.Id AS classArmId, tblstudents.firstName,
                            tblstudents.lastName, tblstudents.gphone, tblstudents.dateCreated
                            FROM tblstudents
                            INNER JOIN tblclass ON tblclass.Id = tblstudents.classId
                            INNER JOIN tblclassarms ON tblclassarms.Id = tblstudents.classArmId
                            WHERE tblstudents.classId = '$_SESSION[classId]' AND tblstudents.classArmId = '$_SESSION[classArmId]'";
                            $rs = $conn->query($query);
                            $num = $rs->num_rows;
                            $sn = 0;
                            if ($num > 0) {
                                while ($rows = $rs->fetch_assoc()) {
                                    $sn++;
                                    echo "
                                    <tr>
                                      <td>$sn</td>
                                      <td>{$rows['firstName']}</td>
                                      <td>{$rows['lastName']}</td>
                                      <td>{$rows['gphone']}</td>
                                      <td>{$rows['admissionNumber']}</td>
                                      <td>{$rows['className']}</td>
                                      <td>{$rows['classArmName']}</td>
                                      <td><input name='check[]' type='checkbox' value={$rows['admissionNumber']} class='form-control'></td>
                                    </tr>";
                                    echo "<input name='admissionNo[]' value={$rows['admissionNumber']} type='hidden' class='form-control'>";
                                }
                            } else {
                                echo "<div class='alert alert-danger' role='alert'>No Record Found!</div>";
                            }
                            ?>
                          </tbody>
                        </table>
                        <br>
                        <button type="submit" name="save" class="btn btn-primary">Take Attendance</button>
                        <button type="submit" name="notify" class="btn btn-warning">Notify</button>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <?php include "Includes/footer.php"; ?>
    </div>
  </div>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
</body>

</html>