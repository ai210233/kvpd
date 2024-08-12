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

$searchCourseTitle = "";
$message = "";

if (isset($_GET['added']) && $_GET['added'] == 1) {
    $message = "<p style='color: green;'>Course added successfully</p>";
}

if (isset($_POST['searchCourseTitle']) && !empty($_POST['searchCourseTitle'])) {
    $searchCourseTitle = $_POST['searchCourseTitle'];
    $queryCourses = "SELECT CourseTitle FROM course WHERE CourseTitle = '$searchCourseTitle'";
    $resultCourses = $conn->query($queryCourses);

    if ($resultCourses->num_rows == 0) {
        $errorMessage = "Course with Title '$searchCourseTitle' not found";
    } else {
        header("Location: ".$_SERVER['PHP_SELF']."?searched=1&searchCourseTitle=".urlencode($searchCourseTitle));
        exit();
    }
} else {
    $queryCourses = "SELECT CourseTitle, CourseDepartment, SKM FROM course";
    $resultCourses = $conn->query($queryCourses);
}

/// Handle adding new course
if (isset($_POST['addCourse'])) {
    $newCourseTitle = $_POST['newCourseTitle'];
    $courseDepartment = $_POST['courseDepartment'];
    $skm = $_POST['skm'];

    // Get the maximum CourseID from the course table
    $maxCourseIDQuery = "SELECT MAX(CourseID) AS maxCourseID FROM course";
    $resultMaxCourseID = $conn->query($maxCourseIDQuery);

    if ($resultMaxCourseID && $rowMaxCourseID = $resultMaxCourseID->fetch_assoc()) {
        // Increment the maximum CourseID by 1
        $newCourseID = $rowMaxCourseID['maxCourseID'] + 1;

        // Use prepared statement to insert the new course
        $insertQuery = $conn->prepare("INSERT INTO course (CourseID, CourseTitle, CourseDepartment, SKM) VALUES (?, ?, ?, ?)");
        $insertQuery->bind_param("isss", $newCourseID, $newCourseTitle, $courseDepartment, $skm);

        if ($insertQuery->execute()) {
            header("Location: ".$_SERVER['PHP_SELF']."?added=1");
            exit();
        } else {
            $message = "Error adding record: " . $conn->error;
        }

        $insertQuery->close();
    } else {
        $message = "Error retrieving maximum CourseID: " . $conn->error;
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
    <link rel="stylesheet" href="style/admin/course.css">
    <script src="script/admin/navbar.js"></script>
</head>
<body>
    <?php include 'include/adminheader.php'; ?>
    <h2>ADD COURSE</h2>
    <div class="custom-line"></div>

    <div class="course-container">
        <?php
        if (isset($_GET['added']) && $_GET['added'] == 1) {
            $message = "<p style='color: green;'>Course added successfully</p>";
        }
        ?>

        <form action="" method="post">
            <label for="newCourseTitle">Subject Title:</label>
            <input type="text" name="newCourseTitle" id="newCourseTitle" required>

            <label for="courseDepartment">Course:</label>
            <select name="courseDepartment" id="courseDepartment" required>
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

            <label for="skm">SKM:</label>
            <select name="skm" id="skm" required>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
            </select>

            <button type="submit" name="addCourse">Add Course</button>
        </form>

        <label><?php echo $message ?></label>
    </div>

    <div class="course-container">
        <a class="add-button" href="admincourse.php">Back</a>
    </div>

    <div class="course-container">
        <h3>Course List</h3>

        <table>
            <thead>
                <tr>
                    <th>Subject Title</th>
                    <th>Course</th>
                    <th>SKM</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $uniqueCourseTitles = array();
                while ($rowCourse = $resultCourses->fetch_assoc()) {
                    $courseTitle = $rowCourse['CourseTitle'];
                    if (!in_array($courseTitle, $uniqueCourseTitles)) {
                        echo "<tr>";
                        echo "<td>{$courseTitle}</td>";
                        echo "<td>{$rowCourse['CourseDepartment']}</td>";
                        echo "<td>{$rowCourse['SKM']}</td>";
                        echo "</tr>";

                        $uniqueCourseTitles[] = $courseTitle;
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <?php include 'include/footer.php'; ?>
</body>
</html>
