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

// Fetch and display courses from the database
$searchCourseID = "";
$errorMessage = "";

if (isset($_POST['currentSearch']) && $_POST['currentSearch'] == 1) {
    if (!empty($_POST['searchCourseID'])) {
        $searchCourseID = $_POST['searchCourseID'];
        $queryCourses = "SELECT CourseID, CourseTitle, CourseDepartment, SKM FROM course WHERE CourseID = '$searchCourseID'";
        $resultCourses = $conn->query($queryCourses);

        // Check if any results found
        if ($resultCourses->num_rows == 0) {
            $errorMessage = "Course with ID $searchCourseID not found";
        }
    }
} else {
    $queryCourses = "SELECT CourseID, CourseTitle, CourseDepartment, SKM FROM course";
    $resultCourses = $conn->query($queryCourses);
}

// Handle update process
if (isset($_POST['editCourseID'])) {
    $editCourseID = $_POST['editCourseID'];
    $editCourseTitle = $_POST['editCourseTitle'];
    $editCourseDepartment = $_POST['editCourseDepartment'];
    $editSKM = $_POST['editSKM'];

    // Check if the new CourseTitle is unique
    $checkQuery = "SELECT CourseID FROM course WHERE CourseTitle = '$editCourseTitle' AND CourseID != '$editCourseID'";
    $checkResult = $conn->query($checkQuery);

    if ($checkResult->num_rows > 0) {
        // Display error if CourseTitle is not unique
        echo "Error: CourseTitle '$editCourseTitle' already exists. Choose a different CourseTitle.";
    } else {
        // Update course details in the course table
        $updateQuery = "UPDATE course SET CourseTitle = '$editCourseTitle', CourseDepartment = '$editCourseDepartment', SKM = '$editSKM' WHERE CourseID = '$editCourseID'";
        
        if ($conn->query($updateQuery) === TRUE) {
            // Redirect after successful update
            header("Location: edit_course.php?id=" . $editCourseID . "&updated=1");
            exit();
        } else {
            echo "Error updating record: " . $conn->error;
        }
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
<h2>EDIT COURSE</h2>
<div class="custom-line"></div>

<div class="course-container">
    <?php
    if (isset($_GET['id'])) {
        $editCourseID = $_GET['id'];

        // Query to get Course details based on CourseID
        $queryEditCourse = "SELECT CourseTitle, CourseDepartment, SKM FROM course WHERE CourseID = '$editCourseID'";
        $resultEditCourse = $conn->query($queryEditCourse);

        if ($resultEditCourse->num_rows == 1) {
            $rowEditCourse = $resultEditCourse->fetch_assoc();
            $editCourseTitle = $rowEditCourse['CourseTitle'];
            $editCourseDepartment = $rowEditCourse['CourseDepartment'];
            $editSKM = $rowEditCourse['SKM'];
            ?>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                <input type="hidden" name="editCourseID" value="<?php echo $editCourseID; ?>">
                
                <!-- Allow editing of Course Title -->
                <label for="editCourseTitle">Subject Title:</label>
                <input type="text" name="editCourseTitle" id="editCourseTitle" value="<?php echo $editCourseTitle; ?>">

                <!-- Allow editing of Course Department -->
                <label for="editCourseDepartment">Course Department:</label>
                <select name="editCourseDepartment" id="editCourseDepartment">
                <option value="Tourism" <?php echo ($editCourseDepartment == 'Tourism') ? 'selected' : ''; ?>>Tourism</option>
                <option value="Business Management" <?php echo ($editCourseDepartment == 'Business Management') ? 'selected' : ''; ?>>Business Management</option>
                <option value="Automotive Technology" <?php echo ($editCourseDepartment == 'Automotive Technology') ? 'selected' : ''; ?>>Automotive Technology</option>
                <option value="Electrical Engineering" <?php echo ($editCourseDepartment == 'Electrical Engineering') ? 'selected' : ''; ?>>Electrical Engineering</option>
                <option value="Electronic Technology" <?php echo ($editCourseDepartment == 'Electronic Technology') ? 'selected' : ''; ?>>Electronic Technology</option>
                <option value="Welding Technology" <?php echo ($editCourseDepartment == 'Welding Technology') ? 'selected' : ''; ?>>Welding Technology</option>
                <option value="Coaching Technology" <?php echo ($editCourseDepartment == 'Coaching Technology') ? 'selected' : ''; ?>>Coaching Technology</option>
                <option value="Industrial Machining Technology" <?php echo ($editCourseDepartment == 'Industrial Machining Technology') ? 'selected' : ''; ?>>Industrial Machining Technology</option>
                <option value="Computer Systems and Circuit Technology" <?php echo ($editCourseDepartment == 'Computer Systems and Circuit Technology') ? 'selected' : ''; ?>>Computer Systems and Circuit Technology</option>
                <option value="Air Cooling & Conditioning Technology" <?php echo ($editCourseDepartment == 'Air Cooling & Conditioning Technology') ? 'selected' : ''; ?>>Air Cooling & Conditioning Technology</option>

                </select>

                <!-- Allow editing of SKM -->
                <label for="editSKM">SKM:</label>
                <select name="editSKM" id="editSKM">
                    <option value="1" <?php echo ($editSKM == '1') ? 'selected' : ''; ?>>1</option>
                    <option value="2" <?php echo ($editSKM == '2') ? 'selected' : ''; ?>>2</option>
                    <option value="3" <?php echo ($editSKM == '3') ? 'selected' : ''; ?>>3</option>
                </select>

                <button type="submit">Update Course</button>
            </form>
            <?php
        } else {
            echo "<p style='color: red;'>Course not found</p>";
        }
    }
    ?>
</div>

<div class="course-container">
    <a class="add-button" href="admincourse.php">Back</a>
</div>

<script>
    
</script>

<?php include 'include/footer.php'; ?>
</body>
</html>
