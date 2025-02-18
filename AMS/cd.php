<?php 
include 'Includes/dbcon.php';
session_start();
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
  <title>AMS - Register</title>
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-login" style="background-image: url('img/logo/loral1.jpeg');">
  <!-- Registration Content -->
  <div class="container-login">
    <div class="row justify-content-center">
      <div class="col-xl-10 col-lg-12 col-md-9">
        <div class="card shadow-sm my-5">
          <div class="card-body p-0">
            <div class="row">
              <div class="col-lg-12">
                <div class="login-form">
                  <h5 align="center">STUDENT ATTENDANCE SYSTEM</h5>
                  <div class="text-center">
                    <img src="img/logo/attnlg.jpg" style="width:100px;height:100px">
                    <br><br>
                    <h1 class="h4 text-gray-900 mb-4">Admin Registration Panel</h1>
                  </div>
                  <form class="user" method="POST" action="">
                    <div class="form-group">
                      <input type="text" class="form-control" required name="username" placeholder="Enter Email Address">
                    </div>
                    <div class="form-group">
                      <input type="password" name="password" required class="form-control" placeholder="Enter Password">
                    </div>
                    <div class="form-group">
                      <input type="text" class="form-control" required name="institute_name" placeholder="Enter Institute Name">
                    </div>
                    <div class="form-group">
                      <input type="submit" class="btn btn-success btn-block" value="Register" name="register" />
                    </div>
                  </form>

<?php
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $instituteName = $_POST['institute_name']; // Use this as the first name
    $lastName = ""; // Leave last name blank

    // Uncomment the line below to hash passwords before storing
    // $password = password_hash($password, PASSWORD_BCRYPT);

    // Check if the user already exists
    $checkQuery = "SELECT * FROM tbladmin WHERE emailAddress = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<div class='alert alert-danger' role='alert'>
              User already exists with this email address!
              </div>";
    } else {
        // Insert the new user into the tbladmin table
        $insertQuery = "INSERT INTO tbladmin (emailAddress, password, firstName, lastName) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ssss", $username, $password, $instituteName, $lastName); // Insert blank last name
        if ($stmt->execute()) {
            echo "<div class='alert alert-success' role='alert'>
                  Registration completed successfully!<br>
                  <a href='index.php' class='btn btn-primary mt-3'>Continue to Login</a>
                  </div>";
        } else {
            echo "<div class='alert alert-danger' role='alert'>
                  Error: " . $stmt->error . "
                  </div>";
        }
    }
    $stmt->close();
}
?>

                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
</body>
</html>