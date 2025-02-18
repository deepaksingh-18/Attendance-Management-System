<?php 
include 'Includes/dbcon.php';  // Database connection
session_start();

// Initialize error message variable
$error_message = "";
$success_message = ''; 
// Login Logic
if (isset($_POST['login'])) {
    $userType = $_POST['userType'];

    if ($userType == "Administrator") {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Securely prepare the query using parameterized statements
        $query = "SELECT * FROM tbladmin WHERE emailAddress = ? AND password = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $rows = $result->fetch_assoc();
            $_SESSION['userId'] = $rows['Id'];
            $_SESSION['firstName'] = $rows['firstName'];
            $_SESSION['lastName'] = $rows['lastName'];
            $_SESSION['emailAddress'] = $rows['emailAddress'];
            header("Location: Admin/index.php");
            exit();
        } else {
            $error_message = "Invalid Username/Password!";
        }
    } elseif ($userType == "ClassTeacher") {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Securely prepare the query using parameterized statements
        $query = "SELECT * FROM tblclassteacher WHERE emailAddress = ? AND password = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $rows = $result->fetch_assoc();
            $_SESSION['userId'] = $rows['Id'];
            $_SESSION['firstName'] = $rows['firstName'];
            $_SESSION['lastName'] = $rows['lastName'];
            $_SESSION['emailAddress'] = $rows['emailAddress'];
            $_SESSION['classId'] = $rows['classId'];
            $_SESSION['classArmId'] = $rows['classArmId'];
            header("Location: ClassTeacher/index.php");
            exit();
        } else {
            $error_message = "Invalid Username/Password!";
        }
    } elseif ($userType == "Student") {
        $studentName = $_POST['studentName'];
        $admissionNo = $_POST['admissionNo'];

        // Securely prepare the query using parameterized statements
        $query = "SELECT * FROM tblstudents WHERE admissionnumber = ? AND CONCAT(firstName, ' ', lastName) = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $admissionNo, $studentName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            $_SESSION['studentName'] = $student['firstName'] . " " . $student['lastName'];
            $_SESSION['admissionNumber'] = $student['admissionnumber'];
            $_SESSION['classId'] = $student['classId'];
            $_SESSION['classArmId'] = $student['classArmId'];

            // Update the admission number in stlogin table where id = 1
            $updateQuery = "UPDATE stlogin SET admissionNo = ? WHERE id = 1";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("s", $admissionNo);
            if ($updateStmt->execute()) {
                // Redirect to student.php page
                header("Location: Student/index.php");  // Fixed redirection URL
                exit();
            } else {
                $error_message = "Error updating the login information.";
            }
        } else {
            $error_message = "Name and Admission Number do not match!";
        }
    }
}

// Registration Logic
// Registration Logic


if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $instituteName = $_POST['institute_name']; // Use as first name
    $registrationCode = $_POST['registration_code']; // Registration code input
    $lastName = ""; // Leave last name blank
    $staticRegistrationCode = "12345"; // Static registration code

    if ($registrationCode !== $staticRegistrationCode) {
        $error_message = "Wrong Registration Code!";
    } else {
        // Database query to check if the user already exists
        $checkQuery = "SELECT * FROM tbladmin WHERE emailAddress = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "User already exists with this email address!";
        } else {
            // Insert the new user into the database
            $insertQuery = "INSERT INTO tbladmin (emailAddress, password, firstName, lastName) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("ssss", $username, $password, $instituteName, $lastName);
            if ($stmt->execute()) {
                // Registration successful
                $success_message = "Registration completed successfully!<br></a>";
            } else {
                $error_message = "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>Modern Login Page | AMS</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background-color: #c9d6ff;
            background: linear-gradient(to right, #e2e2e2, #c9d6ff);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            height: 100vh;
        }

       /* Logo Section */
/* Logo Section */
.logo-container {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0; /* Remove bottom margin to reduce space between logo and form */
    width: 100%; 
    height: 100px;
    top:0;
}

.logo-container img {
    width: auto; /* Maintain aspect ratio */
    height: 130%; /* Adjust the height of the logo (decrease further if needed) */
    object-fit: contain; /* Ensures the image stretches and fits proportionally */
   
    
}

.logo-text {
    font-size: 30px; /* Adjust the font size */
    color: white; /* Text color */
    font-weight: bolder; /* Slightly bolder font weight */
    line-height: 1.2;
    text-align: left;
    font-family: 'Montserrat', sans-serif;
    /* Adding a thin purple outline using text-stroke */
    
}


        .container {
            background-color: #fff;
            border-radius: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.35);
            position: relative;
            overflow: hidden;
            width: 768px;
            max-width: 100%;
            min-height: 480px;
        }

        .container p {
            font-size: 14px;
            line-height: 20px;
            letter-spacing: 0.3px;
            margin: 20px 0;
        }

        .container span {
            font-size: 12px;
        }

        .container a {
            color: #333;
            font-size: 13px;
            text-decoration: none;
            margin: 15px 0 10px;
        }

        .container button {
            background-color: #512da8;
            color: #fff;
            font-size: 12px;
            padding: 10px 45px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 10px;
            cursor: pointer;
        }

        .container button.hidden {
            background-color: transparent;
            border-color: #fff;
        }

        .container form {
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 40px;
            height: 100%;
        }

        .container input {
            background-color: #eee;
            border: none;
            margin: 8px 0;
            padding: 10px 15px;
            font-size: 13px;
            border-radius: 8px;
            width: 100%;
            outline: none;
        }

        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
        }

        .sign-in {
            left: 0;
            width: 50%;
            z-index: 2;
        }

        .container.active .sign-in {
            transform: translateX(100%);
        }

        .sign-up {
            left: 0;
            width: 50%;
            opacity: 0;
            z-index: 1;
        }

        .container.active .sign-up {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
            animation: move 0.6s;
        }

        @keyframes move {
            0%, 49.99% {
                opacity: 0;
                z-index: 1;
            }

            50%, 100% {
                opacity: 1;
                z-index: 5;
            }
        }

        .social-icons {
            margin: 20px 0;
        }

        .social-icons a {
            border: 1px solid #ccc;
            border-radius: 20%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin: 0 3px;
            width: 40px;
            height: 40px;
        }

        .toggle-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: all 0.6s ease-in-out;
            border-radius: 150px 0 0 100px;
            z-index: 1000;
        }

        .container.active .toggle-container {
            transform: translateX(-100%);
            border-radius: 0 150px 100px 0;
        }

        .toggle {
            background-color: #512da8;
            height: 100%;
            background: linear-gradient(to right, #5c6bc0, #512da8);
            color: #fff;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: all 0.6s ease-in-out;
        }

        .container.active .toggle {
            transform: translateX(50%);
        }

        .toggle-panel {
            position: absolute;
            width: 50%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 30px;
            text-align: center;
            top: 0;
            transform: translateX(0);
            transition: all 0.6s ease-in-out;
        }

        .toggle-left {
            transform: translateX(-200%);
        }

        .container.active .toggle-left {
            transform: translateX(0);
        }

        .toggle-right {
            right: 0;
            transform: translateX(0);
        }

        .container.active .toggle-right {
            transform: translateX(200%);
        }

        select {
            background-color: #eee;
            border: 1px solid #ddd;
            margin: 10px 0;
            padding: 10px 15px;
            font-size: 16px;
            border-radius: 8px;
            outline: none;
            width: 100%;
            transition: 0.3s ease;
        }

        select:focus {
            border-color: #5c6bc0;
            background-color: #fff;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #e2e2e2, #c9d6ff);
            text-align: center;
        }

        .container {
            margin-top: 50px;
        }

        input, select, button {
            margin: 5px;
            padding: 10px;
            border: none;
            border-radius: 5px;
        }

        button {
            background-color: #512da8;
            color: white;
        }

        .error {
            color: red;
        }
    </style>
</head>

<body>
    <!-- Logo Section -->
    <div class="logo-container">
    <img src="attnlg.jpg" alt="AMS Logo"> 
    <div class="logo-text">
       Attendance <br> Management <br> System
    </div>
</div>

    

    <div class="container" id="container">
        <!-- Registration Form -->
        <div class="form-container sign-up">
            <form method="POST" action="">
                <h1>Create Account</h1><br><br>
                <input type="email" name="username" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="text" name="institute_name" placeholder="Institute Name" required>
                <input type="text" name="registration_code" placeholder="Registration Code" required>
                <input type="hidden" name="activePage" value="signup">
                <button type="submit" name="register">Sign Up</button>
                <!-- Error message -->
                <div style="color: red; margin-top: 10px; font-size: 14px;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            </form>
        </div>

        <!-- Login Form -->
        <div class="form-container sign-in">
            <form method="POST" action="">
                <h1>Sign In</h1><br><br>
                <select id="userType" name="userType" required>
                    <option value="" disabled selected>Select User Type</option>
                    <option value="Administrator">Admin</option>
                    <option value="ClassTeacher">Teacher</option>
                    <option value="Student">Student</option>
                </select>
                <div id="inputFields">
                    <input type="email" name="username" placeholder="Email Address" required>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" name="login">Sign In</button>
                <!-- Error message for login -->
                <div style="color: red; margin-top: 10px; font-size: 14px;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            </form>
        </div>
      
        <!-- Toggle Panels -->
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Welcome Back!</h1>
                    <p>Sign in to continue</p>
                    <button class="hidden" id="login">Sign In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Hello!</h1>
                    <p>Create an account to get started</p>
                    <button class="hidden" id="register">Sign Up</button>
                </div>
            </div>
        </div>
    </div>
    <?php if (isset($success_message)) : ?>
        <div style="text-align: center; font-size: 18px; color: green; margin-top: 20px;">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <script>
        const container = document.getElementById('container');
        const registerBtn = document.getElementById('register');
        const loginBtn = document.getElementById('login');

        // Toggle Sign In / Sign Up Panels
        registerBtn.addEventListener('click', () => {
            container.classList.add("active");
        });

        loginBtn.addEventListener('click', () => {
            container.classList.remove("active");
        });

        // Dynamic Fields Based on User Type
        const userType = document.getElementById('userType');
        const inputFields = document.getElementById('inputFields');

        userType.addEventListener('change', function () {
            if (this.value === 'Student') {
                inputFields.innerHTML = `
                    <input type="text" name="studentName" placeholder="Student Name" required>
                    <input type="text" name="admissionNo" placeholder="Admission Number" required>
                `;
            } else {
                inputFields.innerHTML = `
                    <input type="email" name="username" placeholder="Email Address" required>
                    <input type="password" name="password" placeholder="Password" required>
                `;
            }
        });

        // Preserve the 'Sign Up' state if there's an error
        <?php if (isset($_POST['register']) && !empty($error_message)): ?>
            document.addEventListener('DOMContentLoaded', () => {
                container.classList.add('active'); // Ensure Sign Up state is active
            });
        <?php endif; ?>
    </script>
</body>

</html>

