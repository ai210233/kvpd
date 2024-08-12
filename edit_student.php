<?php
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
$errorMessage = "";

if (isset($_POST['currentSearch']) && $_POST['currentSearch'] == 1) {
    if (!empty($_POST['searchStudentID'])) {
        $searchStudentID = $_POST['searchStudentID'];
        $queryStudents = "SELECT StudentID, StudentEmail FROM student WHERE StudentID = '$searchStudentID'";
        $resultStudents = $conn->query($queryStudents);

        // Check if any results found
        if ($resultStudents->num_rows == 0) {
            $errorMessage = "Student with ID $searchStudentID not found";
        }
    }
} else {
    $queryStudents = "SELECT StudentID, StudentEmail FROM student";
    $resultStudents = $conn->query($queryStudents);
}

// Handle update process
if (isset($_POST['editStudentID'])) {
    $editStudentID = $_POST['editStudentID'];
    $selectedCourse = $_POST['courseDropdown'];
    $selectedClass = $_POST['classDropdown'];

    // Retrieve ClassID based on ClassName
    $getClassIDQuery = "SELECT ClassID FROM class WHERE ClassName = '$selectedClass'";
    $classIDResult = $conn->query($getClassIDQuery);

    if ($classIDResult->num_rows > 0) {
        // Fetch ClassID
        $classIDRow = $classIDResult->fetch_assoc();
        $classID = $classIDRow['ClassID'];

        // Update student's ClassID and CourseID
        $updateStudentQuery = "UPDATE student SET ClassID = '$classID', CourseDepartment = '$selectedCourse' WHERE StudentID = '$editStudentID'";
        if ($conn->query($updateStudentQuery) !== TRUE) {
            echo "Error updating student details: " . $conn->error;
        }

        // Redirect after successful update
        header("Location: edit_student.php?id=" . $editStudentID . "&updated=1");
        exit();
    } else {
        echo "Error: Class not found for ClassName: $selectedClass";
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
<h2>ASSIGN STUDENT</h2>
<div class="custom-line"></div>

<div class="student-container">
    <?php
    if (isset($_GET['id'])) {
        $editStudentID = $_GET['id'];

        // Query to get Student details based on StudentID
        $queryEditStudent = "SELECT StudentID, StudentEmail, StudentName FROM student WHERE StudentID = '$editStudentID'";
        $resultEditStudent = $conn->query($queryEditStudent);

        if ($resultEditStudent->num_rows == 1) {
            $rowEditStudent = $resultEditStudent->fetch_assoc();
            $editStudentID = $rowEditStudent['StudentID'];
            $editStudentEmail = $rowEditStudent['StudentEmail'];
            $editStudentName = $rowEditStudent['StudentName'];
            ?>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                <input type="hidden" name="editStudentID" value="<?php echo $editStudentID; ?>">

                <!-- Display Student ID as read-only -->
                <label for="editStudentID">Student ID:</label>
                <input type="text" name="editStudentID" id="editStudentID" value="<?php echo $editStudentID; ?>" readonly>

                <!-- Display Student Email as read-only -->
                <label for="editStudentEmail">Student Email:</label>
                <input type="email" name="editStudentEmail" id="editStudentEmail" value="<?php echo $editStudentEmail; ?>" readonly>

                <!-- Allow editing of Student Name -->
                <label for="editStudentName">Student Name:</label>
                <input type="text" name="editStudentName" id="editStudentName" value="<?php echo $editStudentName; ?>" readonly>

                <!-- Dropdown for Courses -->
                <label for="courseDropdown">Select Course:</label>
                <select name="courseDropdown" id="courseDropdown" required>
                    <option value="" selected disabled>Select Course</option>
                    <?php
                    // Query to get distinct courses from the database
                    $queryCourses = "SELECT DISTINCT CourseDepartment FROM course";
                    $resultCourses = $conn->query($queryCourses);

                    while ($rowCourse = $resultCourses->fetch_assoc()) {
                        echo "<option value='" . $rowCourse['CourseDepartment'] . "'>" . $rowCourse['CourseDepartment'] . "</option>";
                    }
                    ?>
                </select>

                <!-- Dropdown for Classes -->
                <label for="classDropdown">Select Class:</label>
                <select name="classDropdown" id="classDropdown" required>
                    <option value="" selected disabled>Select Class</option>
                    <?php
                    // Query to get distinct classes from the database
                    $queryClasses = "SELECT DISTINCT ClassName FROM class";
                    $resultClasses = $conn->query($queryClasses);

                    while ($rowClass = $resultClasses->fetch_assoc()) {
                        echo "<option value='" . $rowClass['ClassName'] . "'>" . $rowClass['ClassName'] . "</option>" ;
                    }
                    ?>
                </select>

                <button type="submit">Update</button>
            </form>
            <?php
        } else {
            echo "<p style='color: red;'>Student not found</p>";
        }
    }
    ?>
</div>

<div class="student-container">
    <a class="add-button" href="adminstudent.php">Back</a>
</div>

<div class="student-container">
    <table>
        <thead>
            <tr>
                <th>Class Name</th>
                <th>Course Department</th>
            </tr>
        </thead>
        <?php
        // Query to retrieve ClassName and CourseDepartment based on StudentID
        $queryStudentDetails = "SELECT s.StudentID, c.ClassName, s.CourseDepartment
                                FROM student s
                                INNER JOIN class c ON s.ClassID = c.ClassID
                                WHERE s.StudentID = '$editStudentID'";
        $resultStudentDetails = $conn->query($queryStudentDetails);

        if ($resultStudentDetails->num_rows > 0) {
            while ($rowStudent = $resultStudentDetails->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $rowStudent['ClassName'] . "</td>";
                echo "<td>" . $rowStudent['CourseDepartment'] . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3'>No student details found.</td></tr>";
        }
        ?>
    </table>
</div>

<?php include 'include/footer.php'; ?>
</body>
</html>