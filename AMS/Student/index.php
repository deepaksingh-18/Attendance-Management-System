<?php
error_reporting(0);
include '../Includes/dbcon.php';

// Fetch admissionNumber from stlogin table where id = 1
$loginQuery = "SELECT admissionNo FROM stlogin WHERE id = 1";
$loginResult = $conn->query($loginQuery);
if ($loginResult->num_rows > 0) {
    $loginData = $loginResult->fetch_assoc();
    $admissionNumber = $loginData['admissionNo'];
}

// Fetch student details along with class and class arm
$studentQuery = "SELECT tblstudents.*, tblclass.className, tblclassarms.classArmName 
                 FROM tblstudents 
                 LEFT JOIN tblclass ON tblclass.Id = tblstudents.classId 
                 LEFT JOIN tblclassarms ON tblclassarms.Id = tblstudents.classArmId
                 WHERE tblstudents.admissionNumber = '$admissionNumber'";
$studentResult = $conn->query($studentQuery);
$studentData = $studentResult->fetch_assoc();

if (isset($_POST['view'])) {
    $type = $_POST['type'];
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';

    // Prepare the WHERE condition based on the selected type and date filter
    $dateFilter = '';
    if ($type == 2 && !empty($startDate)) {
        $dateFilter = "AND tblattendance.dateTimeTaken = '$startDate'";
    } elseif ($type == 3 && !empty($startDate) && !empty($endDate)) {
        $dateFilter = "AND tblattendance.dateTimeTaken BETWEEN '$startDate' AND '$endDate'";
    }

    // Fetch attendance data
    $query = "SELECT tblattendance.Id, tblattendance.status, tblattendance.dateTimeTaken, tblclass.className,
                tblclassarms.classArmName
              FROM tblattendance
              INNER JOIN tblclass ON tblclass.Id = tblattendance.classId
              INNER JOIN tblclassarms ON tblclassarms.Id = tblattendance.classArmId
              WHERE tblattendance.admissionNo = '$admissionNumber'
              $dateFilter";

    $result = $conn->query($query);
    $attendanceData = [];
    $presentCount = 0;
    $totalClasses = 0;

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $attendanceData[] = $row;
            $totalClasses++;
            if ($row['status'] == '1') {
                $presentCount++;
            }
        }
    }

    // Calculate attendance percentage
    $attendancePercentage = ($totalClasses > 0) ? round(($presentCount / $totalClasses) * 100, 2) : 0;
}

if (isset($_POST['export'])) {
    $type = $_POST['type'];
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';

    // Prepare the WHERE condition based on the selected type and date filter
    $dateFilter = '';
    if ($type == 2 && !empty($startDate)) {
        $dateFilter = "AND tblattendance.dateTimeTaken = '$startDate'";
    } elseif ($type == 3 && !empty($startDate) && !empty($endDate)) {
        $dateFilter = "AND tblattendance.dateTimeTaken BETWEEN '$startDate' AND '$endDate'";
    }

    // Fetch attendance data for export
    $query = "SELECT tblattendance.status, tblattendance.dateTimeTaken
              FROM tblattendance
              WHERE tblattendance.admissionNo = '$admissionNumber'
              $dateFilter";

    $result = $conn->query($query);
    $attendanceData = [];
    $presentCount = 0;
    $totalClasses = 0;

    while ($row = $result->fetch_assoc()) {
        $status = ($row['status'] == '1') ? 'Present' : 'Absent';
        $attendanceData[] = [
            'date' => $row['dateTimeTaken'],
            'status' => $status
        ];
        $totalClasses++;
        if ($status == 'Present') {
            $presentCount++;
        }
    }

    // Calculate attendance percentage
    $attendancePercentage = ($totalClasses > 0) ? round(($presentCount / $totalClasses) * 100, 2) : 0;

    // Generate XLS file
    $studentQuery = "SELECT firstName, lastName FROM tblstudents WHERE admissionNumber = '$admissionNumber'";
    $studentResult = $conn->query($studentQuery);
    $studentRow = $studentResult->fetch_assoc();
    $fileName = $studentRow['firstName'] . "_" . $studentRow['lastName'] . "_ATTN.xls";

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "Date\tStatus\n";
    foreach ($attendanceData as $data) {
        echo $data['date'] . "\t" . $data['status'] . "\n";
    }
    echo "\nAttendance Percentage\t" . $attendancePercentage . "%";

    exit();
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
    <title>Dashboard</title>
    <link href="img/logo/attnlg.jpg" rel="icon">
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    
    <!-- Custom CSS for navbar -->
    
    <style>
    .navbar {
        background-color: #4e73df; /* Custom background color for the navbar */
    }

    .navbar .dropdown-item {
        font-weight: normal; /* Remove boldness for the logout link */
    }

    .navbar .dropdown-toggle {
        font-family: "Arial Black", Gadget, sans-serif; /* Use Arial Black for a bolder font */
        font-weight: 900; /* Use the maximum bold weight */
    }

    .navbar .dropdown-toggle span {
        color: white; /* Set font color of the student name to white */
    }
</style>


    
</head>
<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Navbar -->
                <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <ul class="navbar-nav ml-auto">
                        <!-- Student Name Dropdown -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $studentData['firstName'] . ' ' . $studentData['lastName']; ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>


                <!-- Content -->
                <div class="container-fluid" id="container-wrapper">
                    

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Student Details</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>First Name:</strong> <?php echo $studentData['firstName']; ?></p>
                                    <p><strong>Last Name:</strong> <?php echo $studentData['lastName']; ?></p>
                                    <p><strong>Admission Number:</strong> <?php echo $studentData['admissionNumber']; ?></p>
                                    <p><strong>Guardian Phone No:</strong> <?php echo $studentData['gphone']; ?></p>
                                    <p><strong>Class:</strong> <?php echo $studentData['className']; ?></p>
                                    <p><strong>Class Arm:</strong> <?php echo $studentData['classArmName']; ?></p>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">View Student Attendance</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-4">
                                                <label class="form-control-label">Select Date Range</label>
                                                <input type="date" name="startDate" class="form-control mb-3" value="<?php echo isset($startDate) ? $startDate : ''; ?>">
                                            </div>
                                            <div class="col-xl-4">
                                                <label class="form-control-label">End Date</label>
                                                <input type="date" name="endDate" class="form-control mb-3" value="<?php echo isset($endDate) ? $endDate : ''; ?>">
                                            </div>
                                            <div class="col-xl-4">
                                                <label class="form-control-label">Type</label>
                                                <select required name="type" class="form-control mb-3">
                                                    <option value="1" <?php echo $type == 1 ? 'selected' : ''; ?>>All</option>
                                                    <option value="2" <?php echo $type == 2 ? 'selected' : ''; ?>>By Single Date</option>
                                                    <option value="3" <?php echo $type == 3 ? 'selected' : ''; ?>>By Date Range</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button type="submit" name="view" class="btn btn-primary">View Attendance</button>
                                        <button type="submit" name="export" class="btn btn-success">Export to XLS</button>
                                    </form>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Class Attendance</h6>
                                </div>
                                <div class="table-responsive p-3">
                                    <table class="table align-items-center table-flush table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sn = 0;
                                            if (!empty($attendanceData)) {
                                                foreach ($attendanceData as $data) {
                                                    $status = $data['status'] == '1' ? "Present" : "Absent";
                                                    $colour = $data['status'] == '1' ? "#00FF00" : "#FF0000";
                                                    $sn++;
                                                    echo "
                                                        <tr>
                                                            <td>$sn</td>
                                                            <td>{$data['dateTimeTaken']}</td>
                                                            <td style='background-color: $colour; color: #000;'>$status</td>
                                                        </tr>";
                                                }
                                                echo "
                                                    <tr>
                                                        <td colspan='2'><strong>Attendance Percentage</strong></td>
                                                        <td><strong>{$attendancePercentage}%</strong></td>
                                                    </tr>";
                                            } else {
                                                echo "<tr><td colspan='3'>No records found!</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>
</body>
</html>
