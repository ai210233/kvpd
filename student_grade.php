<?php
session_start();

// Check if StudentID is set in the session
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kvpd_elearning"; // Replace with your actual database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get StudentID from session
$studentID = $_SESSION['user_id'];

// Query the database to get Student details
$query = "SELECT * FROM student WHERE StudentID = $studentID";
$result = $conn->query($query);

if ($result->num_rows == 1) {
    // Fetch Student details
    $row = $result->fetch_assoc();
    $studentName = $row['StudentName'];
    $studentImage = $row['StudentImage'];
    $studentMatricNum = $row['StudentMatricNum'];
    $studentSKM = $row['SKM'];
    $classID = $row['ClassID'];
    $courseDepartment = $row['CourseDepartment'];
} else {
    // Handle error if Student details not found
    $studentName = "Unknown";
    $studentImage = "default_student_image.jpg"; // Replace with a default image path
    $studentMatricNum = "N/A";
    $studentSKM = "N/A";
    $classID = "N/A";
    $courseDepartment = "N/A";
}

// Query to get all CourseID values with the same CourseDepartment
$courses = [];
if ($courseDepartment !== "N/A") {
    $courseQuery = "SELECT CourseID, CourseTitle FROM course WHERE CourseDepartment = '$courseDepartment' AND SKM = '$studentSKM'";
    $courseResult = $conn->query($courseQuery);

    if ($courseResult->num_rows > 0) {
        while ($courseRow = $courseResult->fetch_assoc()) {
            $courseID = $courseRow['CourseID'];
            $courseTitle = $courseRow['CourseTitle'];

            // Get AssignteacherID based on ClassID and CourseID
            $assignteacherQuery = "SELECT AssignteacherID FROM assignteacher WHERE ClassID = '$classID' AND CourseID = '$courseID'";
            $assignteacherResult = $conn->query($assignteacherQuery);

            if ($assignteacherResult->num_rows == 1) {
                $assignteacherRow = $assignteacherResult->fetch_assoc();
                $assignteacherID = $assignteacherRow['AssignteacherID'];
            } else {
                $assignteacherID = null;
            }

            $courses[] = [
                'CourseID' => $courseID,
                'CourseTitle' => $courseTitle,
                'AssignteacherID' => $assignteacherID
            ];
        }
    }
}

// Get the class name based on the class ID
$className = "";
if ($classID !== "N/A") {
    $classQuery = "SELECT ClassName FROM class WHERE ClassID = $classID";
    $classResult = $conn->query($classQuery);
    if ($classResult->num_rows == 1) {
        $classRow = $classResult->fetch_assoc();
        $className = $classRow['ClassName'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style/student/header.css">
    <link rel="stylesheet" href="style/student/course.css">
    <script src="script/admin/navbar.js"></script>
    <style>
    .container,
    .table {
      display: none; /* Initially hidden */
      max-width: 1400px;
      margin: 20px auto;
      padding: 0 20px;
      background-color: #fff;
      padding: 20px;
    }

    .choose {
      max-width: 1400px;
      margin: 20px auto;
      padding: 0 20px;
      text-align: center;
    }

    .choose-container {
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      margin: 0 auto;
      display: flex;
      justify-content: space-around; /* Buttons centered horizontally */
    }

    .choose-container button {
      /* Increased button width to fill container */
      width: calc(50% - 20px); /* 50% width minus margins */
      background-color: #4CAF50; /* Green */
      border: none;
      color: white;
      padding: 15px 32px;
      text-align: center;
      text-decoration: none;
      display: inline-block;
      font-size: 16px;
      margin: 4px 2px;
      cursor: pointer;
      border-radius: 8px;
      transition: 0.3s;
    }

    .choose-container button:disabled {
      /* Style for disabled buttons */
      opacity: 0.6;
      cursor: default;
    }

    .choose-container button:hover {
      box-shadow: 0 8px 16px 0 rgba(0, 0, 0, 0.2);
    }

    .learn-container,
    .skm-table {
      background-color: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .grade-container {
      margin-top: 30px;
    }

    .learn-container h2 {
      margin-bottom: 20px;
    }

    .learn-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 20px;
      justify-items: center;
    }

    .learn-card {
      background-color: #fff;
      border: 1px solid #ccc;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s, box-shadow 0.3s;
      overflow: hidden;
    }

    .learn-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .learn-card img {
      width: 100%;
      height: auto;
      border-bottom: 1px solid #ccc;
    }

    .learn-card .content {
      padding: 15px;
    }

    .learn-card h4 {
      margin-top: 0;
      margin-bottom: 10px;
      font-size: 18px;
    }

    .learn-card p {
      margin: 0;
      color: #666;
    }

    .add-learn-card {
      border: 2px dashed #aaa;
      background-color: #d1ffd6;
      cursor: pointer;
    }

    .learn-card a {
      text-decoration: none; /* Remove underline */
      color: #333;
    }

    /* Search input style */
    #searchInput {
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 16px;
      width: 40%;
      box-sizing: border-box;
      margin-bottom: 10px;
    }
    
    </style>
</head>
<body>
<?php include 'include/studentheader.php'; ?>
<h2>GRADE</h2>
<div class="custom-line"></div>

<div class="choose">
    <div class="choose-container">
        <button id="gradeButton" onclick="navigateToPage(1)">View Assignment and Assessment</button>
        <button id="skmButton" onclick="navigateToPage(2)">View Grade</button>
    </div>
</div>

<?php include 'include/footer.php'; ?>

<script>
    function navigateToPage(chooseId) {
      window.location.href = `student_grades.php?choose_id=${chooseId}`;
    }
</script>

</body>
</html>