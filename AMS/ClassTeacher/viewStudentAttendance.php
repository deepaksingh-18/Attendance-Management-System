<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (isset($_POST['view'])) {
    $admissionNumber = $_POST['admissionNumber'];
    $type = $_POST['type'];

    // Fetch attendance data
    $query = "SELECT tblattendance.Id, tblattendance.status, tblattendance.dateTimeTaken, tblclass.className,
                tblclassarms.classArmName, tblstudents.firstName, tblstudents.lastName, tblstudents.gphone, tblstudents.admissionNumber
                FROM tblattendance
                INNER JOIN tblclass ON tblclass.Id = tblattendance.classId
                INNER JOIN tblclassarms ON tblclassarms.Id = tblattendance.classArmId
                INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
                WHERE tblattendance.admissionNo = '$admissionNumber'
                AND tblattendance.classId = '$_SESSION[classId]'
                AND tblattendance.classArmId = '$_SESSION[classArmId]'";

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
    $admissionNumber = $_POST['admissionNumber'];

    // Fetch attendance data for export
    $query = "SELECT tblattendance.status, tblattendance.dateTimeTaken, tblstudents.firstName, tblstudents.lastName
              FROM tblattendance
              INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
              WHERE tblattendance.admissionNo = '$admissionNumber'
              AND tblattendance.classId = '$_SESSION[classId]'
              AND tblattendance.classArmId = '$_SESSION[classArmId]'";

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

    // Output student info first
    echo "Name: " . $studentRow['firstName'] . " " . $studentRow['lastName'] . "\n";
    echo "Admission Number: " . $admissionNumber . "\n";
    echo "Attendance Percentage: " . $attendancePercentage . "%\n\n";

    // Output table headers
    echo "Date\tStatus\n";
    foreach ($attendanceData as $data) {
        echo $data['date'] . "\t" . $data['status'] . "\n";
    }

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
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include "Includes/sidebar.php"; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include "Includes/topbar.php"; ?>

                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">View Student Attendance</h1>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">View Student Attendance</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Select Student<span class="text-danger ml-2">*</span></label>
                                                <?php
                                                $qry = "SELECT * FROM tblstudents WHERE classId = '$_SESSION[classId]' AND classArmId = '$_SESSION[classArmId]' ORDER BY firstName ASC";
                                                $result = $conn->query($qry);
                                                if ($result->num_rows > 0) {
                                                    echo '<select required name="admissionNumber" class="form-control mb-3">';
                                                    echo '<option value="">--Select Student--</option>';
                                                    while ($rows = $result->fetch_assoc()) {
                                                        echo '<option value="' . $rows['admissionNumber'] . '">' . $rows['firstName'] . ' ' . $rows['lastName'] . '</option>';
                                                    }
                                                    echo '</select>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <button type="submit" name="view" class="btn btn-primary">View Attendance</button>
                                        <button type="submit" name="export" class="btn btn-success">Export to XLS</button>
                                    </form>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="card mb-4">
                                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                            <h6 class="m-0 font-weight-bold text-primary">Class Attendance</h6>
                                        </div>
                                        <div class="table-responsive p-3">
                                            <table class="table align-items-center table-flush table-hover">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Student Name</th>
                                                        <th>Admission No</th>
                                                        <th>Guardian Phone</th>
                                                        <th>Class</th>
                                                        <th>Class Arm</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?php echo $attendanceData[0]['firstName'] . " " . $attendanceData[0]['lastName']; ?></td>
                                                        <td><?php echo $attendanceData[0]['admissionNumber']; ?></td>
                                                        <td><?php echo $attendanceData[0]['gphone']; ?></td>
                                                        <td><?php echo $attendanceData[0]['className']; ?></td>
                                                        <td><?php echo $attendanceData[0]['classArmName']; ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                            <table class="table align-items-center table-flush table-hover mt-3">
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
                                                    ?>
                                                </tbody>
                                            </table>

                                            <div class="row">
                                                <div class="col-12">
                                                    <strong>Attendance Percentage: </strong> <?php echo $attendancePercentage . "%"; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
