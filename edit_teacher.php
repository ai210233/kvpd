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

$id = intval($_GET['id']); 

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
$errorMessage = "";

if (isset($_POST['currentSearch']) && $_POST['currentSearch'] == 1) {
    if (!empty($_POST['searchTeacherID'])) {
        $searchTeacherID = $_POST['searchTeacherID'];
        $queryTeachers = "SELECT TeacherID, TeacherEmail FROM teacher WHERE TeacherID = '$searchTeacherID'";
        $resultTeachers = $conn->query($queryTeachers);

        // Check if any results found
        if ($resultTeachers->num_rows == 0) {
            $errorMessage = "Teacher with ID $searchTeacherID not found";
        }
    }
} else {
    $queryTeachers = "SELECT TeacherID, TeacherEmail FROM teacher";
    $resultTeachers = $conn->query($queryTeachers);
}

if (isset($_POST['deleteAssignteacherID'])) {
    $deleteAssignteacherID = $_POST['deleteAssignteacherID'];

    // Perform deletion from the assignteacher table
    $deleteAssignteacherQuery = "DELETE FROM assignteacher WHERE AssignteacherID = '$deleteAssignteacherID'";
    if ($conn->query($deleteAssignteacherQuery) === TRUE) {
        // Redirect back to the same page after successful deletion
        header("Location: edit_teacher.php?id=$id");
        exit();
    } else {
        echo "Error deleting assignment: " . $conn->error;
    }
}

if(isset($_POST['assign_teacher']))
{
    
    $fileName = $_FILES['import_file_assign_teacher']['name'];
    $file_ext = pathinfo($fileName, PATHINFO_EXTENSION);

    $allowed_ext = ['cls','csv','xlsx'];
    
    if(in_array($file_ext, $allowed_ext))
    {
        $inputFileNamePath = $_FILES['import_file_assign_teacher']['tmp_name'];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileNamePath);
        $data = $spreadsheet->getActiveSheet()->toArray();

        $count = "0";
        foreach($data as $row)
        {
            if($count > 0)
            {
                $teachername = $row['0'];
                $teachercourse = $row['1'];
                $teacherclass = $row['2'];
                $teacherintake = $row['3'];
                $teacherSem = $row['4'];

                // Retrieve CourseID based on CourseTitle
                $getnameQuery = "SELECT TeacherID FROM teacher WHERE TeacherUsername = '$teachername'";
                $nameResult = $conn->query($getnameQuery);

                if ($nameResult->num_rows > 0) {
                    $nameRow = $nameResult->fetch_assoc();
                    $teacherID = $nameRow['TeacherID'];

                    // Retrieve CourseID based on CourseTitle
                    $getCourseIDQuery = "SELECT CourseID FROM course WHERE CourseTitle = '$teachercourse'";
                    $courseResult = $conn->query($getCourseIDQuery);

                    if ($courseResult->num_rows > 0) {
                        $courseRow = $courseResult->fetch_assoc();
                        $courseID = $courseRow['CourseID'];

                        // Retrieve ClassID based on ClassName
                        $getClassIDQuery = "SELECT ClassID FROM class WHERE ClassName = '$teacherclass'";
                        $classResult = $conn->query($getClassIDQuery);

                        if ($classResult->num_rows > 0) {
                            $classRow = $classResult->fetch_assoc();
                            $classID = $classRow['ClassID'];

                            $maxAssignteacherIDQuery = "SELECT MAX(AssignteacherID) AS max_id FROM assignteacher";
                            $result = $conn->query($maxAssignteacherIDQuery);
                            $row = $result->fetch_assoc();
                            $newAssignteacherID = $row['max_id'] + 1;

                            $checkExistingQuery = "SELECT * FROM assignteacher WHERE CourseID = '$courseID' AND ClassID = '$classID' AND SessionIntake = '$teacherintake' AND Semester = '$teacherSem'";
                            $resultCheckExisting = $conn->query($checkExistingQuery);

                            if($resultCheckExisting->num_rows > 0) {
                                // Duplicate record found, handle accordingly
                                $message = "<p style='color: red;'>Duplicate record found ";
                            } else {
                                // No duplicate found, proceed with insertion
                                $assignteacherQuery = "INSERT INTO assignteacher (AssignteacherID, TeacherID, CourseID, ClassID, SessionIntake, Semester) 
                                                VALUES ('$newAssignteacherID', '$teacherID', '$courseID', '$classID', '$teacherintake', '$teacherSem')";
                                $result = $conn->query($assignteacherQuery);
                                $msg = true;

                            }
                        }
                        else {
                            // Class not found, handle accordingly
                            $message = "<p style='color: red;'>Class not found for name: $teacherclass</p>";
                        }
                    }
                    else {
                        // Course not found, handle accordingly
                        $message = "<p style='color: red;'>Course not found for title: $teachercourse</p>";
                    }
                }
                else {
                    // Course not found, handle accordingly
                    $message = "<p style='color: red;'>Username not found for title: $teachername</p>";
                }                
            }
            else
            {
                $count = "1";
            }
            
        }

        if(isset($msg))
        {
            $message =  "Successfully Imported " . $conn->error;
        }
        else
        {
            $message =  "Unsuccessfully Imported " . $conn->error;
        }
    }
    else
    {
        $message =  "Invalid File " . $conn->error;
    }

}



if(isset($_POST['manual_assign']))
{

    // Retrieve data from the form
    $teacherName = $_POST['teacherName'];
    $courseTitle = $_POST['courseTitle'];
    $className = $_POST['className'];
    $selectSemester = $_POST['selectSemester'];
    $selectSession = $_POST['selectSession'];

    // Query to get TeacherID based on TeacherName
    $getTeacherIDQuery = "SELECT TeacherID FROM teacher WHERE TeacherID = '$teacherName'";
    $teacherIDResult = $conn->query($getTeacherIDQuery);

    if ($teacherIDResult->num_rows > 0)
    {
        // Fetch TeacherID
        $teacherIDRow = $teacherIDResult->fetch_assoc();
        $teacherID = $teacherIDRow['TeacherID'];

        // Query to get CourseID based on CourseTitle
        $getCourseIDQuery = "SELECT CourseID FROM course WHERE CourseTitle = '$courseTitle'";
        $courseIDResult = $conn->query($getCourseIDQuery);

        if ($courseIDResult->num_rows > 0)
        {
            // Fetch CourseID
            $courseIDRow = $courseIDResult->fetch_assoc();
            $courseID = $courseIDRow['CourseID'];

            // Query to get ClassID based on ClassName
            $getClassIDQuery = "SELECT ClassID FROM class WHERE ClassName = '$className'";
            $classIDResult = $conn->query($getClassIDQuery);

            if ($classIDResult->num_rows > 0)
            {
                // Fetch ClassID
                $classIDRow = $classIDResult->fetch_assoc();
                $classID = $classIDRow['ClassID'];

                $maxAssignteacherIDQuery = "SELECT MAX(AssignteacherID) AS max_id FROM assignteacher";
                $result = $conn->query($maxAssignteacherIDQuery);
                $row = $result->fetch_assoc();
                $newAssignteacherID = $row['max_id'] + 1;

                // Check if the assignment already exists
                $checkExistingQuery = "SELECT * FROM assignteacher WHERE CourseID = '$courseID' AND ClassID = '$classID' AND SessionIntake = '$selectSession' AND Semester = '$selectSemester'";
                $existingResult = $conn->query($checkExistingQuery);

                if ($existingResult->num_rows == 0)
                {
                    // Insert the assignment into the database
                    $insertQuery = "INSERT INTO assignteacher (AssignteacherID, TeacherID, CourseID, ClassID, SessionIntake, Semester) VALUES ('$newAssignteacherID', '$teacherID', '$courseID', '$classID', '$selectSession', '$selectSemester')";
                    if ($conn->query($insertQuery) === TRUE)
                    {
                        // Assignment inserted successfully
                        $message = "Assignment inserted successfully";
                    }
                    else
                    {
                        // Error inserting assignment
                        $message = "Error inserting assignment: " . $conn->error;
                    }
                }
                else
                {
                    // Assignment already exists
                    $message = "Assignment already exists for TeacherID: $teacherID, CourseID: $courseID, ClassID: $classID";
                }
            }
            else
            {
                // Class not found
                $message = "Class not found for ClassName: $className";
            }
        }
        else
        {
            // Course not found
            $message = "Course not found for CourseTitle: $courseTitle";
        }
    }
    else
    {
        // Teacher not found
        $message = "Teacher not found for TeacherName: $teacherName";
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
    <link rel="stylesheet" href="style/admin/teacher.css">
    <script src="script/admin/navbar.js"></script>
</head>
<body>
<?php include 'include/adminheader.php'; ?>
<h2>ASSIGN TEACHER</h2>
<div class="custom-line"></div>

<div class="teacher-container">
    <?php
    // Display assignment success message
    if(isset($_GET['assigned']) && $_GET['assigned'] == 1) {
        $message = "<p style='color: green;'>Teacher assigned successfully</p>";
    }
    ?>

    <form action="" method="post" enctype="multipart/form-data">
        <label for="teacher_id" style="color: #333; font-family: 'Arial', sans-serif;">Select Teacher:</label>
        <input type="file" name="import_file_assign_teacher" id="import_file_assign_teacher" class="form-control" required style="margin-top: 10px; border: 2px solid #4CAF50; padding: 10px; border-radius: 5px;">

        <button type="submit" name="assign_teacher" class="btn btn-primary mt-3" style="background-color: #4CAF50; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px; font-family: 'Arial', sans-serif;">Assign</button>
    </form>

</div>

<div class="teacher-container">
    <?php
    if (isset($_GET['id'])) {
        $editTeacherID = $_GET['id'];

        // Query to get Teacher details based on TeacherID
        $queryEditTeacher = "SELECT * FROM teacher WHERE TeacherID = '$editTeacherID'";
        $resultEditTeacher = $conn->query($queryEditTeacher);

        if ($resultEditTeacher->num_rows == 1) {
            $rowEditTeacher = $resultEditTeacher->fetch_assoc();
            $editTeacherID = $rowEditTeacher['TeacherID'];
            $editTeacherEmail = $rowEditTeacher['TeacherEmail'];
            $editTeacherName = $rowEditTeacher['TeacherName'];
            $editTeacherUsername = $rowEditTeacher['TeacherUsername'];
            ?>
            <form action="" method="post">
                <input type="hidden" name="teacherName" value="<?php echo $editTeacherID; ?>">

                <!-- Display Teacher ID as read-only -->
                <label for="editTeacherID">Teacher Username:</label>
                <input type="text" name="editTeacherID" id="editTeacherID" value="<?php echo $editTeacherUsername; ?>" readonly>

                <!-- Display Teacher Email as read-only -->
                <label for="editTeacherEmail">Teacher Email:</label>
                <input type="email" name="editTeacherEmail" id="editTeacherEmail" value="<?php echo $editTeacherEmail; ?>" readonly>

                <!-- Allow editing of Teacher Name -->
                <label for="editTeacherName">Teacher Name:</label>
                <input type="text" name="editTeacherName" id="editTeacherName" value="<?php echo $editTeacherName; ?>" readonly>

                <label for="selectSession">Select Session:</label>
                <select name="selectSession" id="selectSession" required">
                    <option value="" selected disabled>Select Session</option>     
                    <option value="2022">2022</option>
                    <option value="2023">2023</option>
                    <option value="2024">2024</option>
                </select>

                <label for="selectSemester">Select Semester:</label>
                <select name="selectSemester" id="selectSemester" required">
                    <option value="" selected disabled>Select Semester</option>     
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                </select>

                <!-- Dropdown for Courses -->
                <label for="courseTitle">Select Course:</label>
                <select name="courseTitle" id="courseTitle" required">
                    <option value="" selected disabled>Select Course</option>
                    <?php
                    // Query to get distinct courses from the database
                    $queryCourses = "SELECT DISTINCT CourseTitle FROM course";
                    $resultCourses = $conn->query($queryCourses);

                    while ($rowCourse = $resultCourses->fetch_assoc()) {
                        echo "<option value='" . $rowCourse['CourseTitle'] . "'>" . $rowCourse['CourseTitle'] . "</option>";
                    }
                    ?>
                </select>

                <!-- Dropdown for Classes -->
                <label for="className">Select Class:</label>
                <select name="className" id="className" required">
                    <option value="" selected disabled>Select Class</option>
                    <?php
                    // Query to get distinct classes from the database
                    $queryClasses = "SELECT DISTINCT ClassName FROM class";
                    $resultClasses = $conn->query($queryClasses);

                    while ($rowClass = $resultClasses->fetch_assoc()) {
                        echo "<option value='" . $rowClass['ClassName'] . "'>" . $rowClass['ClassName'] . "</option>";
                    }
                    ?>
                </select>

                <button type="submit" name="manual_assign" class="btn btn-primary mt-3">Assign Teacher</button>
            </form>
            <?php
        } else {
            echo "<p style='color: red;'>Teacher not found</p>";
        }
    }
    ?>
</div>

<div class="teacher-container">
    <a class="add-button" href="adminteacher.php">Back</a>
</div>

<div class="teacher-container">
    <h3>Assigned Courses</h3>
    <table>
        <thead>
            <tr>
                <th>Course Title</th>
                <th>Class Name</th>
                <th>Session</th>
                <th>Semester</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Check if $editTeacherID is defined
            if (isset($editTeacherID)) {
                // Query to get assigned courses based on TeacherID and JOIN with class and course tables
                $queryAssignedCourses = "
                    SELECT assignteacher.AssignteacherID, assignteacher.SessionIntake, assignteacher.Semester, course.CourseTitle, class.ClassName 
                    FROM assignteacher 
                    JOIN course ON assignteacher.CourseID = course.CourseID 
                    JOIN class ON assignteacher.ClassID = class.ClassID 
                    WHERE assignteacher.TeacherID = '$editTeacherID'";
                $resultAssignedCourses = $conn->query($queryAssignedCourses);

                while ($rowAssignedCourse = $resultAssignedCourses->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$rowAssignedCourse['CourseTitle']}</td>";
                    echo "<td>{$rowAssignedCourse['ClassName']}</td>";
                    echo "<td>{$rowAssignedCourse['SessionIntake']}</td>";
                    echo "<td>{$rowAssignedCourse['Semester']}</td>";
                    echo "<td>
                        <form method='post' onsubmit='return confirm(\"Are you sure you want to delete this assigned course?\");'>
                            <input type='hidden' name='deleteAssignteacherID' value='{$rowAssignedCourse['AssignteacherID']}'>
                            <button type='submit'>Delete</button>
                        </form>
                    </td>";
                    echo "</tr>";
                }
            }
            ?>
        </tbody>
    </table>
</div>

<script>

    function confirmDeleteCourse(courseID) {
        var confirmDelete = confirm("Are you sure you want to delete this assigned course?");
        if (confirmDelete) {
            // Send an AJAX request to handle the deletion
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    // Handle the response
                    alert(xhr.responseText);
                    // Refresh the window after successful deletion
                    if (xhr.responseText.includes("successful")) {
                        location.reload();
                    }
                }
            };
            xhr.send("deleteCourseID=" + courseID);
        }
    }
</script>

<?php include 'include/footer.php'; ?>
</body>
</html>
