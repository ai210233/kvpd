<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();

// Check if AdministratorID is set in the session
if (!isset($_SESSION['AdministratorID'])) {
    // Redirect to login page if not logged in
    header("Location: adminlogin.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kvpd_elearning";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get AdministratorID from session
$administratorID = $_SESSION['AdministratorID'];

// Query the database to get AdministratorName
$query = "SELECT AdministratorName FROM administrator WHERE AdministratorID = $administratorID";
$result = $conn->query($query);

if ($result->num_rows == 1) {
    // Fetch AdministratorName
    $row = $result->fetch_assoc();
    $administratorName = $row['AdministratorName'];
} else {
    // Handle error if AdministratorName not found
    $administratorName = "Unknown";
}

// Fetch and display teachers from the database
$searchTeacherID = "";
$message = "";

if(isset($_GET['added']) && $_GET['added'] == 1) {
    $message= "<p style='color: green;'>Teacher added successfully</p>";
}

if (isset($_POST['searchTeacherID']) && !empty($_POST['searchTeacherID'])) {
    $searchTeacherID = $_POST['searchTeacherID'];
    $queryTeachers = "SELECT TeacherID, TeacherUsername, TeacherEmail, TeacherICnum, TeacherName FROM teacher WHERE TeacherID = '$searchTeacherID'";
    $resultTeachers = $conn->query($queryTeachers);

    // Check if any results found
    if ($resultTeachers->num_rows == 0) {
        $errorMessage = "Teacher with ID $searchTeacherID not found";
    } else {
        // Redirect after successful search
        header("Location: ".$_SERVER['PHP_SELF']."?searched=1&searchTeacherID=".urlencode($searchTeacherID));
        exit();
    }
} else {
    $queryTeachers = "SELECT TeacherID, TeacherUsername, TeacherEmail, TeacherICnum, TeacherName FROM teacher";
    $resultTeachers = $conn->query($queryTeachers);
}

// Handle manual addition of teacher
if (isset($_POST['manual_add_teacher'])) {
    $teacherUsername = $_POST['teacher_username'];
    $teacherName = $_POST['teacher_name'];
    $teacherIC = $_POST['teacher_IC'];
    $teacherPhoneNum = $_POST['teacher_phonenum'];
    $teacherEmail = $_POST['teacher_email'];

    // Check for existing data based on username and IC number
    $checkExistingQuery = "SELECT * FROM teacher WHERE TeacherICnum = ? OR TeacherUsername = ?";
    $stmt = $conn->prepare($checkExistingQuery);
    $stmt->bind_param("ss", $teacherIC, $teacherUsername);
    $stmt->execute();
    $resultCheckExisting = $stmt->get_result();

    if ($resultCheckExisting->num_rows > 0) {
        // Duplicate record found, handle accordingly
        $message = "<p style='color: red;'>Duplicate record found for Teacher with IC $teacherIC or Username $teacherUsername</p>";
    } else {
        // Hash IC number for password
        $hashedPassword = password_hash($teacherIC, PASSWORD_DEFAULT);

        // Get the highest TeacherID from the database
        $maxTeacherIDQuery = "SELECT MAX(TeacherID) AS max_id FROM teacher";
        $resultMaxTeacherID = $conn->query($maxTeacherIDQuery);
        $rowMaxTeacherID = $resultMaxTeacherID->fetch_assoc();
        $newTeacherID = $rowMaxTeacherID['max_id'] + 1;

        // Perform insertion into the database
        $insertQuery = "INSERT INTO teacher (TeacherID, TeacherUsername, TeacherEmail, TeacherName, TeacherPassword, TeacherICnum, TeacherNumphone) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("issssssss", $newTeacherID, $teacherUsername, $teacherEmail, $teacherName, $hashedPassword, $teacherIC, $teacherPhoneNum);
        
        if ($stmt->execute()) {
            // Redirect after successful insertion
            header("Location: " . $_SERVER['PHP_SELF'] . "?added=1");
            exit();
        } else {
            $message = "Error adding record: " . $conn->error;
        }
    }
}

if(isset($_POST['save_excel_data_teacher'])) {
    $fileName = $_FILES['import_file_teacher']['name'];
    $file_ext = pathinfo($fileName, PATHINFO_EXTENSION);

    $allowed_ext = ['cls','csv','xlsx'];

    if(in_array($file_ext, $allowed_ext)) {
        $inputFileNamePath = $_FILES['import_file_teacher']['tmp_name'];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileNamePath);
        $data = $spreadsheet->getActiveSheet()->toArray();

        $count = 0; // Start count from 0
        foreach($data as $row) {
            if($count > 0) {
                $teacherusername = $row['0'];
                $teachername = $row['1'];
                $teacherIC = $row['2'];
                $teacheremail = $row['3'];
                $teacherreligion = $row['4'];
                $teacherrace = $row['5'];
                $teachernophone = $row['6'];


                $maxTeacherIDQuery = "SELECT MAX(TeacherID) AS max_id FROM teacher";
                $result = $conn->query($maxTeacherIDQuery);
                $row = $result->fetch_assoc();
                $newTeacherID = $row['max_id'] + 1;

                // Check for duplicate records
                $checkExistingQuery = "SELECT * FROM teacher WHERE TeacherICnum = '$teacherIC' OR TeacherUsername = '$teacherusername'";
                $resultCheckExisting = $conn->query($checkExistingQuery);

                if($resultCheckExisting->num_rows > 0) {
                    // Duplicate record found, handle accordingly
                    $message = "<p style='color: red;'>Duplicate record found for Teacher with IC $teacherIC or Username $teacherusername</p>";
                } else {
                    // No duplicate found, proceed with insertion
                    $hashedPassword = password_hash($teacherIC, PASSWORD_DEFAULT); // Hash IC as password
                    $teacherQuery = "INSERT INTO teacher (TeacherID, TeacherUsername, TeacherEmail, TeacherName, TeacherPassword, TeacherICnum, TeacherReligion, TeacherRace, TeacherNumphone) 
                                    VALUES ($newTeacherID, '$teacherusername', '$teacheremail', '$teachername', '$hashedPassword', '$teacherIC', '$teacherreligion', '$teacherrace', '$teachernophone')";
                    $result = $conn->query($teacherQuery);
                    $msg = true;
                }
            } else {
                $count++; // Increment count after the header row
            }
        }

        if(isset($msg)) {
            $message =  "Successfully Imported " . $conn->error;
        } else {
            $message =  "Unsuccessfully Imported " . $conn->error;
        }
    } else {
        $message =  "Invalid File " . $conn->error;
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style/admin/header.css">
    <link rel="stylesheet" href="style/admin/teacher.css">
    <script src="script/admin/navbar.js"></script>
</head>
<body>
<?php include 'include/adminheader.php'; ?>
<h2>ADD TEACHER</h2>
<div class="custom-line"></div>

<div class="teacher-container">
    <?php
    // Display addition success message
    if(isset($_GET['added']) && $_GET['added'] == 1) {
        $message= "<p style='color: green;'>Teacher added successfully</p>";
    }
    ?>

    <form action="" method="post" enctype="multipart/form-data" >
        <label for="import_file_teacher" style="color: #333; font-family: 'Arial', sans-serif;">Select File:</label>
        <input type="file" name="import_file_teacher" id="import_file_teacher" class="form-control" required style="margin-top: 10px; border: 2px solid #4CAF50; padding: 10px; border-radius: 5px;">

        <button type="submit" name="save_excel_data_teacher" class="btn btn-primary mt-3" style="background-color: #4CAF50; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px; font-family: 'Arial', sans-serif;">Import</button>
    </form>

    <p style="color: #4CAF50; font-family: 'Arial', sans-serif;"><?php echo $message ?></p>
</div>

<div class="teacher-container">
    <a class="add-button" id="showManualAddForm">Manual Add</a>

    <form id="manualAddForm" action="" method="post" style="display: none;">
        <h3>Manually Add Student</h3>

        <div style="margin-bottom: 10px;">
            <label for="teacher_name" style="display: inline-block; width: 150px; color: #333; font-family: 'Arial', sans-serif;">Name:</label>
            <input type="text" name="teacher_name" id="teacher_name" class="form-control" required style="display: inline-block; width: calc(100% - 170px); margin-left: 10px; border: 2px solid #4CAF50; padding: 10px; border-radius: 5px;">
        </div>

        <div style="margin-bottom: 10px;">
            <label for="teacher_username" style="display: inline-block; width: 150px; color: #333; font-family: 'Arial', sans-serif;">Username:</label>
            <input type="text" name="teacher_username" id="teacher_username" class="form-control" required style="display: inline-block; width: calc(100% - 170px); margin-left: 10px; border: 2px solid #4CAF50; padding: 10px; border-radius: 5px;">
        </div>

        <div style="margin-bottom: 10px;">
            <label for="teacher_IC" style="display: inline-block; width: 150px; color: #333; font-family: 'Arial', sans-serif;">IC Number:</label>
            <input type="text" name="teacher_IC" id="teacher_IC" class="form-control" required style="display: inline-block; width: calc(100% - 170px); margin-left: 10px; border: 2px solid #4CAF50; padding: 10px; border-radius: 5px;">
        </div>

        <div style="margin-bottom: 10px;">
            <label for="teacher_email" style="display: inline-block; width: 150px; color: #333; font-family: 'Arial', sans-serif;">Email:</label>
            <input type="text" name="teacher_email" id="teacher_email" class="form-control" required style="display: inline-block; width: calc(100% - 170px); margin-left: 10px; border: 2px solid #4CAF50; padding: 10px; border-radius: 5px;">
        </div>

        <div style="margin-bottom: 10px;">
            <label for="teacher_phonenum" style="display: inline-block; width: 150px; color: #333; font-family: 'Arial', sans-serif;">Phone Number:</label>
            <input type="text" name="teacher_phonenum" id="teacher_phonenum" class="form-control" required style="display: inline-block; width: calc(100% - 170px); margin-left: 10px; border: 2px solid #4CAF50; padding: 10px; border-radius: 5px;">
        </div>

        <button type="submit" name="manual_add_teacher" class="btn btn-primary mt-3" style="background-color: #4CAF50; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px; font-family: 'Arial', sans-serif;">Add Teacher</button>
    </form>
</div>

<div class="teacher-container">
    <a class="add-button" href="adminteacher.php">Back</a>
</div>

<script>
    document.getElementById('showManualAddForm').addEventListener('click', function() {
        document.getElementById('manualAddForm').style.display = 'block';
    });
</script>

<div class="teacher-container">
    <h3>Teacher List</h3>

    <table>
        <thead>
            <tr>
                <th>Teacher Username</th> 
                <th>Teacher IC</th>
                <th>Teacher Name</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($rowTeacher = $resultTeachers->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . (isset($rowTeacher['TeacherUsername']) ? $rowTeacher['TeacherUsername'] : 'N/A') . "</td>";
                echo "<td>" . (isset($rowTeacher['TeacherICnum']) ? $rowTeacher['TeacherICnum'] : 'N/A') . "</td>";
                echo "<td>" . (isset($rowTeacher['TeacherName']) ? $rowTeacher['TeacherName'] : 'N/A') . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php include 'include/footer.php'; ?>
</body>
</html>
