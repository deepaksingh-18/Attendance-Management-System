<?php
include '../Includes/dbcon.php';

if (isset($_GET['cid'])) {
    $classId = intval($_GET['cid']); // Get classId from AJAX request

    // Fetch class arms that belong to the selected class
    $query = "SELECT * FROM tblclassarms WHERE classId = '$classId' ORDER BY classArmName ASC";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        echo '<option value="">--Select Class Arm--</option>';
        while ($row = $result->fetch_assoc()) {
            echo '<option value="' . $row['Id'] . '">' . $row['classArmName'] . '</option>';
        }
    } else {
        echo '<option value="">No Class Arms Found</option>';
    }
}
?>