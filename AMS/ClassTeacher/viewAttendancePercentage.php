<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../Includes/dbcon.php';
include '../Includes/session.php';
use Twilio\Rest\Client;


// Debugging: Check if the script is executing
// echo "Script loaded successfully.<br>"; 

// Step 1: Fetch attendance data when 'View Attendance' is clicked
if (isset($_POST['view'])) {
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];

    // Fetch attendance data based on date range for all students
    $query = "SELECT tblattendance.status, tblattendance.dateTimeTaken, tblstudents.firstName, tblstudents.lastName, 
              tblstudents.gphone, tblstudents.admissionNumber, tblclass.className, tblclassarms.classArmName
              FROM tblattendance
              INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
              INNER JOIN tblclass ON tblclass.Id = tblattendance.classId
              INNER JOIN tblclassarms ON tblclassarms.Id = tblattendance.classArmId
              WHERE tblattendance.classId = '$_SESSION[classId]'
              AND tblattendance.classArmId = '$_SESSION[classArmId]'
              AND tblattendance.dateTimeTaken BETWEEN '$startDate' AND '$endDate'";

    $result = $conn->query($query);
    $attendanceData = [];

    // Collect data for each student
    while ($row = $result->fetch_assoc()) {
        $attendanceData[$row['admissionNumber']][] = $row; // Group by admission number
    }

    // Calculate attendance percentage for each student
    $attendancePercentageData = [];
    foreach ($attendanceData as $admissionNumber => $data) {
        $totalClasses = count($data);
        $presentCount = 0;
        foreach ($data as $attendance) {
            if ($attendance['status'] == '1') {
                $presentCount++;
            }
        }
        $attendancePercentage = ($totalClasses > 0) ? round(($presentCount / $totalClasses) * 100, 2) : 0;
        $attendancePercentageData[$admissionNumber] = [
            'percentage' => $attendancePercentage,
            'studentName' => $data[0]['firstName'] . ' ' . $data[0]['lastName'], // Combine first and last name
            'guardianPhone' => $data[0]['gphone'],
            'admissionNumber' => $admissionNumber
        ];

        // Step 2: Insert or update data in tblpercentage
        $studentName = $data[0]['firstName'] . ' ' . $data[0]['lastName'];
        $guardianPhone = $data[0]['gphone'];
        $percentage = $attendancePercentage;

        // Check if admission number exists
        $checkQuery = "SELECT admissionNumber FROM tblpercentage WHERE admissionNumber = '$admissionNumber'";
        $checkResult = $conn->query($checkQuery);

        if ($checkResult->num_rows > 0) {
            // Update the record if admission number exists
            $updateQuery = "UPDATE tblpercentage SET studentName = '$studentName', guardianPhone = '$guardianPhone', attendancePercentage = '$percentage'
                            WHERE admissionNumber = '$admissionNumber'";
            $conn->query($updateQuery);
        } else {
            // Insert new record if admission number doesn't exist
            $insertQuery = "INSERT INTO tblpercentage (admissionNumber, studentName, guardianPhone, attendancePercentage) 
                            VALUES ('$admissionNumber', '$studentName', '$guardianPhone', '$percentage')";
            $conn->query($insertQuery);
        }
    }

    // Sort the data by attendance percentage in descending order
    usort($attendancePercentageData, function($a, $b) {
        return $b['percentage'] <=> $a['percentage']; // Descending order
    });
}

// Step 2: Send notifications to guardians of students with attendance < 75% when 'Notify' is clicked
if (isset($_POST['notify'])) {
    // Debugging: Notify button is clicked
 // Add this for debugging

    // No need for dates here, we are just notifying parents of students with low attendance
    $absentStudents = mysqli_query($conn, "SELECT guardianPhone 
        FROM tblpercentage 
        WHERE attendancePercentage < 75");

    $guardianNumbers = [];
    while ($row = $absentStudents->fetch_assoc()) {
        $phone = $row['guardianPhone'];
        if (strpos($phone, '+91') !== 0) {
            $phone = '+91' . $phone; // Ensure the phone number is in the correct format
        }
        $guardianNumbers[] = $phone;
    }

    if (count($guardianNumbers) > 0) {
        // Include Twilio's autoload file
        require __DIR__ . '/vendor/autoload.php';

        // Your Twilio SID, Token, and Phone Number
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
                        "body" => "Dear Parent, your ward’s attendance is below 75%. Regular attendance is mandatory to appear in exams. Please ensure they attend regularly.."
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
                $statusMsg = "<div class='alert alert-success'>Notifications sent successfully to all guardians!</div>";
            }
        } catch (Exception $e) {
            $statusMsg = "<div class='alert alert-danger'>Error sending notifications: " . $e->getMessage() . "</div>";
        }
    } else {
        $statusMsg = "<div class='alert alert-warning'>No students with attendance below 75% found.</div>";
    }
}

// Step 3: Export data when 'Export to XLS' button is clicked
if (isset($_POST['export'])) {
    // Fetch class and session details
    $classId = $_SESSION['classId'];
    $classArmId = $_SESSION['classArmId'];
    $className = isset($_SESSION['className']) ? $_SESSION['className'] : 'Unknown Class';
    $classArmName = isset($_SESSION['classArmName']) ? $_SESSION['classArmName'] : 'Unknown Section';
    $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : 'N/A';
    $endDate = isset($_POST['endDate']) ? $_POST['endDate'] : 'N/A';

    // Query to fetch and order attendance percentage data
    $query = "SELECT admissionNumber, studentName, guardianPhone, attendancePercentage 
              FROM tblpercentage 
              WHERE admissionNumber IN (
                  SELECT DISTINCT admissionNo 
                  FROM tblattendance 
                  WHERE classId = '$classId' AND classArmId = '$classArmId' 
                  AND dateTimeTaken BETWEEN '$startDate' AND '$endDate'
              )
              ORDER BY attendancePercentage DESC"; // Order by attendance percentage in descending order

    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        // Set headers for downloading as an Excel file
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=attendance_data.xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        // Write class and date range details
        echo "$className - $classArmName\n";
        echo "Date Range: $startDate to $endDate\n\n";

        // Write table headers
        echo "Sr. No.\tAdmission No\tStudent Name\tAttendance Percentage\n";

        // Loop through the result set and write rows
        $srNo = 1;
        while ($row = $result->fetch_assoc()) {
            echo $srNo . "\t" . $row['admissionNumber'] . "\t" . $row['studentName'] . "\t" . $row['attendancePercentage'] . "%\n";
            $srNo++;
        }

        // Terminate the script to ensure no extra output
        exit;
    } else {
        echo "<script>alert('No attendance data found for the selected date range.');</script>";
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
                        <h1 class="h3 mb-0 text-gray-800">View Class Attendance</h1>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">View Class Attendance</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Start Date<span class="text-danger ml-2">*</span></label>
                                                <input type="date" name="startDate" class="form-control mb-3" required>
                                            </div>

                                            <div class="col-xl-6">
                                                <label class="form-control-label">End Date<span class="text-danger ml-2">*</span></label>
                                                <input type="date" name="endDate" class="form-control mb-3" required>
                                            </div>
                                        </div>

                                        <button type="submit" name="view" class="btn btn-primary">View Attendance</button>
                                        <button type="submit" name="export" class="btn btn-success">Export to XLS</button>
                                    </form>
                                    <form method="post">
                                        <!-- Notify Guardians button -->
                                        <button type="submit" name="notify" class="btn btn-danger mt-3">Notify Guardians</button>
                                    </form>
                                    <?php if(isset($statusMsg)) echo $statusMsg; ?> <!-- Display status message -->
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
                                                        <th>Sr. No.</th>
                                                        <th>Admission No</th>
                                                        <th>Student Name</th>
                                                        <th>Attendance Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $srNo = 1;
                                                    if (!empty($attendancePercentageData)) {
                                                        foreach ($attendancePercentageData as $data) {
                                                            echo "
                                                                <tr>
                                                                    <td>{$srNo}</td>
                                                                    <td>{$data['admissionNumber']}</td>
                                                                    <td>{$data['studentName']}</td>
                                                                    <td>{$data['percentage']}%</td>
                                                                </tr>";
                                                            $srNo++;
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='4'>No records found!</td></tr>";
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
        </div>
    </div>
</body>
</html>
