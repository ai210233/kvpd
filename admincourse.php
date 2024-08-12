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

$searchCourseID = "";
$errorMessage = "";
$coursesData = array(); // Initialize $coursesData

if (isset($_POST['currentSearch']) && $_POST['currentSearch'] == 1) {
    if (!empty($_POST['searchCourseID'])) {
        $searchCourseID = $_POST['searchCourseID'];
        $queryCourses = "SELECT DISTINCT CourseID, CourseTitle, CourseDepartment, SKM FROM course WHERE CourseID = '$searchCourseID' AND CourseTitle IS NOT NULL AND CourseTitle <> '' AND CourseDepartment IS NOT NULL AND CourseDepartment <> ''";
        $resultCourses = $conn->query($queryCourses);

        // Check for query execution success
        if (!$resultCourses) {
            die("Query failed: " . $conn->error);
        }

        // Check if any results found
        if ($resultCourses->num_rows == 0) {
            $errorMessage = "Course with ID $searchCourseID not found";
        }
    } else {
        // If search input is empty, fetch unique CourseTitles
        $queryCourses = "SELECT DISTINCT CourseID, CourseTitle, CourseDepartment, SKM FROM course";
        $resultCourses = $conn->query($queryCourses);

        // Check for query execution success
        if (!$resultCourses) {
            die("Query failed: " . $conn->error);
        }
    }
} else {
    // If search input is empty, fetch unique CourseTitles
    $queryCourses = "SELECT DISTINCT CourseID, CourseTitle, CourseDepartment, SKM FROM course WHERE CourseTitle IS NOT NULL AND CourseTitle <> '' AND CourseDepartment IS NOT NULL AND CourseDepartment <> ''";
    $resultCourses = $conn->query($queryCourses);

    // Check for query execution success
    if (!$resultCourses) {
        die("Query failed: " . $conn->error);
    }
}

// Handle Course Deletion
if (isset($_POST['deleteCourseTitle'])) {
    $deleteCourseTitle = $_POST['deleteCourseTitle'];

    // Perform deletion from the database
    $deleteQuery = "DELETE FROM course WHERE CourseTitle = '$deleteCourseTitle'";
    if ($conn->query($deleteQuery) === TRUE) {
        // Redirect after successful deletion
        header("Location: ".$_SERVER['PHP_SELF']."?deleted=1");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
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
    <link rel="stylesheet" href="style/admin/course.css"> <!-- Update the CSS file for courses -->
    <script src="script/admin/navbar.js"></script>
</head>
<body>
<?php include 'include/adminheader.php'; ?>
<h2>COURSE</h2>
<div class="custom-line"></div>

<div class="course-container">
    <a class="add-button" href="addcourse.php">Add Course</a>
</div>

<div class="course-container">
    <h3>Course List</h3>

    <?php if ($resultCourses && $resultCourses->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Subject Title</th>
                    <th>Course</th>
                    <th>SKM</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $uniqueCourseTitles = array(); ?>
                <?php while ($rowCourse = $resultCourses->fetch_assoc()): ?>
                    <?php if (!in_array($rowCourse['CourseTitle'], $uniqueCourseTitles)): ?>
                        <?php $uniqueCourseTitles[] = $rowCourse['CourseTitle']; ?>
                        <tr>
                            <td><?php echo $rowCourse['CourseTitle']; ?></td>
                            <td><?php echo $rowCourse['CourseDepartment']; ?></td>
                            <td><?php echo $rowCourse['SKM']; ?></td>
                            <td>
                                <!-- Modified link to pass CourseID -->
                                <a href='edit_course.php?id=<?php echo $rowCourse['CourseID']; ?>'>Edit</a>
                                <a href='javascript:void(0);' onclick='confirmDelete("<?php echo $rowCourse['CourseTitle']; ?>")'>Delete</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No courses found.</p>
    <?php endif; ?>
</div>

<script>
    function confirmDelete(courseTitle) {
        var confirmDelete = confirm("Are you sure you want to delete this course?");
        if (confirmDelete) {
            // Send an AJAX request to handle the deletion
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    // Handle the response
                    alert(xhr.responseText);
                    // Refresh the page after successful deletion
                    if (xhr.responseText.includes("successful")) {
                        window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>';
                    }
                }
            };
            xhr.send("deleteCourseTitle=" + courseTitle);
        }
    }
</script>

<?php include 'include/footer.php'; ?>
</body>
</html>
