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

$Query = "SELECT COUNT(DISTINCT StudentID) AS totalStudents FROM student";
$Result = $conn->query($Query);
$Data = $Result->fetch_assoc();
$totalStudents = $Data['totalStudents'];

$QueryTeachers = "SELECT COUNT(DISTINCT TeacherID) AS totalTeachers FROM teacher";
$ResultTeachers = $conn->query($QueryTeachers);
$DataTeachers = $ResultTeachers->fetch_assoc();
$totalTeachers = $DataTeachers['totalTeachers'];

$skmLevel1QueryCourses = "SELECT COUNT(DISTINCT CourseTitle) AS totalCourses FROM course WHERE SKM = 1 AND CourseTitle IS NOT NULL AND CourseTitle <> ''";
$skmLevel1ResultCourses = $conn->query($skmLevel1QueryCourses);
$skmLevel1DataCourses = $skmLevel1ResultCourses->fetch_assoc();
$totalCoursesSkmLevel1 = $skmLevel1DataCourses['totalCourses'];

$skmLevel1QueryClasses = "SELECT COUNT(DISTINCT ClassName) AS totalClasses FROM class WHERE SKM = 1 AND ClassName IS NOT NULL AND ClassName <> ''";
$skmLevel1ResultClasses = $conn->query($skmLevel1QueryClasses);
$skmLevel1DataClasses = $skmLevel1ResultClasses->fetch_assoc();
$totalClassesSkmLevel1 = $skmLevel1DataClasses['totalClasses'];

// Similarly, fetch data for total teachers, courses, and classes for SKM Level 2

// Fetch data for SKM Level 2
$skmLevel2QueryCourses = "SELECT COUNT(DISTINCT CourseTitle) AS totalCourses FROM course WHERE SKM = 2 AND CourseTitle IS NOT NULL AND CourseTitle <> ''";
$skmLevel2ResultCourses = $conn->query($skmLevel2QueryCourses);
$skmLevel2DataCourses = $skmLevel2ResultCourses->fetch_assoc();
$totalCoursesSkmLevel2 = $skmLevel2DataCourses['totalCourses'];

$skmLevel2QueryClasses = "SELECT COUNT(DISTINCT ClassName) AS totalClasses FROM class WHERE SKM = 2 AND ClassName IS NOT NULL AND ClassName <> ''";
$skmLevel2ResultClasses = $conn->query($skmLevel2QueryClasses);
$skmLevel2DataClasses = $skmLevel2ResultClasses->fetch_assoc();
$totalClassesSkmLevel2 = $skmLevel2DataClasses['totalClasses'];

// Similarly, fetch data for total teachers, courses, and classes for SKM Level 3

// Fetch data for SKM Level 3
$skmLevel3QueryCourses = "SELECT COUNT(DISTINCT CourseTitle) AS totalCourses FROM course WHERE SKM = 3 AND CourseTitle IS NOT NULL AND CourseTitle <> ''";
$skmLevel3ResultCourses = $conn->query($skmLevel3QueryCourses);
$skmLevel3DataCourses = $skmLevel3ResultCourses->fetch_assoc();
$totalCoursesSkmLevel3 = $skmLevel3DataCourses['totalCourses'];

$skmLevel3QueryClasses = "SELECT COUNT(DISTINCT ClassName) AS totalClasses FROM class WHERE SKM = 3 AND ClassName IS NOT NULL AND ClassName <> ''";
$skmLevel3ResultClasses = $conn->query($skmLevel3QueryClasses);
$skmLevel3DataClasses = $skmLevel3ResultClasses->fetch_assoc();
$totalClassesSkmLevel3 = $skmLevel3DataClasses['totalClasses'];

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
    <link rel="stylesheet" href="style/admin/dashboard.css">
    <script src="script/admin/navbar.js"></script>
</head>
<body>
<?php include 'include/adminheader.php'; ?>
<h2>DASHBOARD</h2>
<div class="custom-line"></div>
<div class="dashboard-container">
    <h3>Student and Teacher</h3>
    <div class="dashboard-box">
        <h4>Total Students</h4>
        <span class="total-number"><?php echo $totalStudents; ?></span>
    </div>
    <div class="dashboard-box">
        <h4>Total Teachers</h4>
        <span class="total-number"><?php echo $totalTeachers; ?></span>
    </div>
</div>
<div class="custom-line"></div>
<!-- Container 1: SKM Level 1 -->
<div class="dashboard-container">
    <h3>SKM Level 1</h3>
    <div class="dashboard-box">
        <h4>Total Courses</h4>
        <span class="total-number"><?php echo $totalCoursesSkmLevel1; ?></span>
    </div>
    <div class="dashboard-box">
        <h4>Total Classes</h4>
        <span class="total-number"><?php echo $totalClassesSkmLevel1; ?></span>
    </div>
</div>

<!-- Container 2: SKM Level 2 -->
<div class="dashboard-container">
    <h3>SKM Level 2</h3>
    <div class="dashboard-box">
        <h4>Total Courses</h4>
        <span class="total-number"><?php echo $totalCoursesSkmLevel2; ?></span>
    </div>
    <div class="dashboard-box">
        <h4>Total Classes</h4>
        <span class="total-number"><?php echo $totalClassesSkmLevel2; ?></span>
    </div>
</div>

<!-- Container 3: SKM Level 3 -->
<div class="dashboard-container">
    <h3>SKM Level 3</h3>
    <div class="dashboard-box">
        <h4>Total Courses</h4>
        <span class="total-number"><?php echo $totalCoursesSkmLevel3; ?></span>
    </div>
    <div class="dashboard-box">
        <h4>Total Classes</h4>
        <span class="total-number"><?php echo $totalClassesSkmLevel3; ?></span>
    </div>
</div>


<?php include 'include/footer.php'; ?>
</body>
</html>
