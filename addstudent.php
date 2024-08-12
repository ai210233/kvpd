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

// Fetch and display students from the database
$searchStudentID = "";
$message = "";

if(isset($_GET['added']) && $_GET['added'] == 1) {
    $message= "<p style='color: green;'>Student added successfully</p>";
}

if (isset($_POST['searchStudentID']) && !empty($_POST['searchStudentID'])) {
    $searchStudentID = $_POST['searchStudentID'];
    $queryStudents = "SELECT StudentID FROM student WHERE StudentID = '$searchStudentID'";
    $resultStudents = $conn->query($queryStudents);

    // Check if any results found
    if ($resultStudents->num_rows == 0) {
        $errorMessage = "Student with ID $searchStudentID not found";
    } else {
        // Redirect after successful search
        header("Location: ".$_SERVER['PHP_SELF']."?searched=1&searchStudentID=".urlencode($searchStudentID));
        exit();
    }
} else {
    $queryStudents = "SELECT StudentID FROM student";
    $resultStudents = $conn->query($queryStudents);
}

// Handle manual addition of student
if (isset($_POST['manual_add_student'])) {
    $studentMatricNum = $_POST['student_matric_num'];
    $studentName = $_POST['student_name'];
    $studentIC = $_POST['student_IC'];
    $skm = $_POST['skm'];
    $courseDepartment = $_POST['course_department'];
    $className = $_POST['class']; // Assuming class name is submitted

    // Check for existing data based on matric number and IC number
    $checkExistingQuery = "SELECT * FROM student WHERE StudentMatricNum = '$studentMatricNum' OR StudentICnum = '$studentIC'";
    $resultCheckExisting = $conn->query($checkExistingQuery);

    if ($resultCheckExisting->num_rows > 0) {
        // Duplicate record found, handle accordingly
        $message = "<p style='color: red;'>Duplicate record found for Student with Matric Number $studentMatricNum or IC Number $studentIC</p>";
    } else {
        // Get the highest StudentID from the database
        $maxStudentIDQuery = "SELECT MAX(StudentID) AS max_id FROM student";
        $resultMaxStudentID = $conn->query($maxStudentIDQuery);
        $rowMaxStudentID = $resultMaxStudentID->fetch_assoc();
        $newStudentID = $rowMaxStudentID['max_id'] + 1;

        // Hash IC number for password
        $hashedPassword = password_hash($studentIC, PASSWORD_DEFAULT);

        // Get the ClassID based on ClassName
        $getClassIDQuery = "SELECT ClassID FROM class WHERE ClassName = '$className'";
        $resultClassID = $conn->query($getClassIDQuery);

        if ($resultClassID->num_rows > 0) {
            $rowClassID = $resultClassID->fetch_assoc();
            $classID = $rowClassID['ClassID'];

            // Perform insertion into the database
            $insertQuery = "INSERT INTO student (StudentID, StudentMatricNum, StudentName, StudentICnum, StudentPassword, SKM, CourseDepartment, ClassID, RegisterStatus) 
                            VALUES ('$newStudentID', '$studentMatricNum', '$studentName', '$studentIC', '$hashedPassword', '$skm', '$courseDepartment', '$classID', 1)";
            
            if ($conn->query($insertQuery) === TRUE) {
                // Redirect after successful insertion
                header("Location: ".$_SERVER['PHP_SELF']."?added=1");
                exit();
            } else {
                $message =  "Error adding record: " . $conn->error;
            }
        } else {
            // Handle case where class name is not found
            $message = "<p style='color: red;'>Class '$className' not found in the database.</p>";
        }
    }
}

if(isset($_POST['save_excel_data_student'])) {
    $fileName = $_FILES['import_file_student']['name'];
    $file_ext = pathinfo($fileName, PATHINFO_EXTENSION);

    $allowed_ext = ['cls','csv','xlsx'];

    if(in_array($file_ext, $allowed_ext)) {
        $inputFileNamePath = $_FILES['import_file_student']['tmp_name'];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileNamePath);
        $data = $spreadsheet->getActiveSheet()->toArray();

        $count = 0; // Start count from 0
        foreach($data as $row) {
            if($count > 0) {
                $studentMatricnum = $row['0'];
                $studentname = $row['1'];
                $studentIC = $row['2'];
                $studentemail = $row['3'];
                $studentclass = $row['4'];
                $studentdepartment = $row['5'];
                $studentSKM = $row['6'];
                $studentreligion = $row['7'];
                $studentrace = $row['8'];

                // Get ClassID based on ClassName
                $getClassIDQuery = "SELECT ClassID FROM class WHERE ClassName = '$studentclass'";
                $resultClassID = $conn->query($getClassIDQuery);
                if($resultClassID->num_rows > 0) {
                    $rowClassID = $resultClassID->fetch_assoc();
                    $classID = $rowClassID['ClassID'];

                    $maxStudentIDQuery = "SELECT MAX(StudentID) AS max_id FROM student";
                    $result = $conn->query($maxStudentIDQuery);
                    $row = $result->fetch_assoc();
                    $newStudentID = $row['max_id'] + 1;

                    // Check for duplicate records
                    $checkExistingQuery = "SELECT * FROM student WHERE StudentICnum = '$studentIC' OR StudentMatricNum = '$studentMatricnum'";
                    $resultCheckExisting = $conn->query($checkExistingQuery);

                    if($resultCheckExisting->num_rows > 0) {
                        // Duplicate record found, handle accordingly
                        $message = "<p style='color: red;'>Duplicate record found for Student with IC $studentIC or Matric Num $studentMatricnum</p>";
                    } else {
                        // No duplicate found, proceed with insertion
                        $hashedPassword = password_hash($studentIC, PASSWORD_DEFAULT); // Hash IC as password
                        $studentQuery = "INSERT INTO student (StudentID, StudentMatricNum, StudentEmail, StudentName, StudentPassword, StudentICnum, StudentReligion, StudentRace, ClassID, CourseDepartment, SKM, RegisterStatus) 
                                        VALUES ($newStudentID, '$studentMatricnum', '$studentemail', '$studentname', '$hashedPassword', '$studentIC', '$studentreligion', '$studentrace', '$classID', '$studentdepartment', '$studentSKM', 1)";
                        $result = $conn->query($studentQuery);
                        $msg = true;
                    }
                } else {
                    // Class not found, handle accordingly
                    $message = "<p style='color: red;'>Class '$studentclass' not found in the database.</p>";
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


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style/admin/header.css">
    <link rel="stylesheet" href="style/admin/student.css">
    <script src="script/admin/navbar.js"></script>
</head>
<body>
<?php include 'include/adminheader.php'; ?>
<h2>ADD STUDENT</h2>
<div class="custom-line"></div>

<div class="student-container">
    <?php
    // Display addition success message
    if(isset($_GET['added']) && $_GET['added'] == 1) {
        $message= "<p style='color: green;'>Student added successfully</p>";
    }
    ?>

    <form action="" method="post" enctype="multipart/form-data" >
        <label for="import_file_student" style="color: #333; font-family: 'Arial', sans-serif;">Select File:</label>
        <input type="file" name="import_file_student" id="import_file_student" class="form-control" required style="margin-top: 10px; border: 2px solid #4CAF50; padding: 10px; border-radius: 5px;">

        <button type="submit" name="save_excel_data_student" class="btn btn-primary mt-3" style="background-color: #4CAF50; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px; font-family: 'Arial', sans-serif;">Import</button>
    </form>

    <p style="color: #4CAF50; font-family: 'Arial', sans-serif;"><?php echo $message ?></p>
</div>

<div class="student-container">
    <a class="add-button" id="showManualAddForm">Manual Add</a>

    <form id="manualAddForm" action="" method="post" style="display: none;">
        <h3>Manually Add Student</h3>

        <div style="margin-bottom: 10px;">
            <label for="student_matric_num" style="display: inline-block; width: 150px; color: #333; font-family: 'Arial', sans-serif;">Matric Number:</label>
            <input type="text" name="student_matric_num" id="student_matric_num" class="form-control" required style="display: inline-block; width: calc(100% - 170px); margin-left: 10px; border: 2px solid #4CAF50; padding: 10px; border-radius: 5px;">
        </div>

        <div style="margin-bottom: 10px;">
            <label for="student_name" style="display: inline-block; width: 150px; color: #333; font-family: 'Arial', sans-serif;">Name:</label>
            <input type="text" name="student_name" id="student_name" class="form-control" required style="display: inline-block; width: calc(100% - 170px); margin-left: 10px; border: 2px solid #4CAF50; padding: 10px; border-radius: 5px;">
        </div>

        <div style="margin-bottom: 10px;">
            <label for="student_IC" style="display: inline-block; width: 150px; color: #333; font-family: 'Arial', sans-serif;">IC Number:</label>
            <input type="text" name="student_IC" id="student_IC" class="form-control" required style="display: inline-block; width: calc(100% - 170px); margin-left: 10px; border: 2px solid #4CAF50; padding: 10px; border-radius: 5px;">
        </div>

        <div style="margin-bottom: 10px;">
            <label for="skm" style="display: inline-block; width: 150px; color: #333; font-family: 'Arial', sans-serif;">SKM:</label>
            <select name="skm" id="skm" class="form-control" required style="display: inline-block; width: calc(100% - 170px); margin-left: 10px; border: 2px solid #4CAF50; padding: 10px; border-radius: 5px;">
                <option value="" selected disabled>Select SKM Level</option>     
                <option value="1">SKM 1</option>
                <option value="2">SKM 2</option>
                <option value="3">SKM 3</option>
            </select>
        </div>

        <div style="margin-bottom: 10px;">
            <label for="course_department" style="display: inline-block; width: 150px; color: #333; font-family: 'Arial', sans-serif;">Course Department:</label>
            <select name="course_department" id="course_department" class="form-control" required style="display: inline-block; width: calc(100% - 170px); margin-left: 10px; border: 2px solid #4CAF50; padding: 10px; border-radius: 5px;">
                <option value="" selected disabled>Select Course Department</option> 
                <option value="Tourism">Tourism</option>
                <option value="Business Management">Business Management</option>
                <option value="Automotive Technology">Automotive Technology</option>
                <option value="Electrical Technology">Electrical Technology</option>
                <option value="Electronic Technology">Electronic Technology</option>
                <option value="Welding Technology">Welding Technology</option>
                <option value="Coaching Technology">Coaching Technology</option>
                <option value="Industrial Machining Technology">Industrial Machining Technology</option>
                <option value="Computer Systems and Circuit Technology">Computer Systems and Circuit Technology</option>
                <option value="Air Cooling & Conditioning Technology">Air Cooling & Conditioning Technology</option>
            </select>
        </div>

        <div style="margin-bottom: 10px;">
            <label for="class" style="display: inline-block; width: 150px; color: #333; font-family: 'Arial', sans-serif;">Class:</label>
            <select name="class" id="class" class="form-control" required style="display: inline-block; width: calc(100% - 170px); margin-left: 10px; border: 2px solid #4CAF50; padding: 10px; border-radius: 5px;">
                <option value="" selected disabled>Select Class</option>    
                <option value="Class 1">Class 1</option>
                <option value="Class 2">Class 2</option>
                <option value="Class 3">Class 3</option>
                <option value="Class 4">Class 4</option>
                <option value="Class 5">Class 5</option>
                <option value="Class 6">Class 6</option>
                <option value="Class 7">Class 7</option>
                <option value="Class 8">Class 8</option>
                <option value="Class 9">Class 9</option>
                <option value="Class 10">Class 10</option>
            </select>
        </div>

        <button type="submit" name="manual_add_student" class="btn btn-primary mt-3" style="background-color: #4CAF50; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px; font-family: 'Arial', sans-serif;">Add Student</button>
    </form>
</div>

<div class="student-container">
    <a class="add-button" href="adminstudent.php">Back</a>
</div>

<script>
    document.getElementById('showManualAddForm').addEventListener('click', function() {
        document.getElementById('manualAddForm').style.display = 'block';
    });
</script>

<div class="student-container">
    <h3>Student List</h3>
    <table>
        <thead>
            <tr>
                <th>Student Matric Number</th>
                <th>Student Name</th>
                <th>IC Number</th>
                <th>Class</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch and display students with matric number, name, IC number, and class
            $queryStudents = "SELECT s.StudentMatricNum, s.StudentName, s.StudentICnum, c.ClassName
                              FROM student s
                              INNER JOIN class c ON s.ClassID = c.ClassID";
            $resultStudents = $conn->query($queryStudents);

            while ($rowStudent = $resultStudents->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$rowStudent['StudentMatricNum']}</td>";
                echo "<td>{$rowStudent['StudentName']}</td>";
                echo "<td>{$rowStudent['StudentICnum']}</td>";
                echo "<td>{$rowStudent['ClassName']}</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php 
// Close the connection after all database operations
$conn->close(); 
include 'include/footer.php'; 
?>
</body>
</html>
