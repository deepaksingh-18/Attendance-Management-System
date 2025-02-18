<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

?>
        <table border="1">
        <thead>
            <tr>
            <th>#</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Guardian Phone No.</th>
            <th>Admission No</th>
            <th>Class</th>
            <th>Class Arm</th>
            <th>Status</th>
            <th>Date</th>
            </tr>
        </thead>

<?php 
$filename="Attendance list";
$dateTaken = date("Y-m-d");

$cnt=1;			
$ret = mysqli_query($conn,"SELECT tblattendance.Id,tblattendance.status,tblattendance.dateTimeTaken,tblclass.className,
        tblclassarms.classArmName,tblstudents.firstName,tblstudents.lastName,tblstudents.gphone,tblstudents.admissionNumber
        FROM tblattendance
        INNER JOIN tblclass ON tblclass.Id = tblattendance.classId
        INNER JOIN tblclassarms ON tblclassarms.Id = tblattendance.classArmId
        INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
        WHERE tblattendance.dateTimeTaken = '$dateTaken' 
        AND tblattendance.classId = '$_SESSION[classId]' 
        AND tblattendance.classArmId = '$_SESSION[classArmId]'");

if(mysqli_num_rows($ret) > 0 )
{
    // Send the headers for export before the content starts
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=".$filename."-report.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    while ($row=mysqli_fetch_array($ret)) 
    { 
        if($row['status'] == '1'){
            $status = "Present"; 
            $colour = "#00FF00";
        } else {
            $status = "Absent";
            $colour = "#FF0000";
        }

        echo '  
        <tr>  
        <td>'.$cnt.'</td> 
        <td>'.$row['firstName'].'</td> 
        <td>'.$row['lastName'].'</td> 
        <td>'.$row['gphone'].'</td> 
        <td>'.$row['admissionNumber'].'</td> 
        <td>'.$row['className'].'</td> 
        <td>'.$row['classArmName'].'</td>	
        <td style="background-color:'.$colour.'">'.$status.'</td>	 	
        <td>'.$row['dateTimeTaken'].'</td>	 					
        </tr>  
        ';
        $cnt++;
    }
}
?>
</table>