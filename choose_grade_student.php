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

$choose_id = isset($_GET['choose_id']) ? intval($_GET['choose_id']) : 0;
$assignteacher_id = intval($_GET['assignteacher_id']); 

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

// Fetch assignment and assessment details along with marks
$assignments = [];
$assessments = [];

if (isset($assignteacher_id) && $assignteacher_id) {

    // Fetch AAfolder details
    $aafolderQuery = "SELECT AAfolderID, FolderName, ShowFolder FROM aafolder WHERE AssignteacherID = '$assignteacher_id'";
    $aafolderResult = $conn->query($aafolderQuery);

    if ($aafolderResult->num_rows > 0) {
        while ($aafolderRow = $aafolderResult->fetch_assoc()) {
            $aafolderID = $aafolderRow['AAfolderID'];
            $folderName = $aafolderRow['FolderName'];
            $showfolder = $aafolderRow['ShowFolder'];

            // Fetch marks from mark table
            $markQuery = "SELECT Marks FROM mark WHERE AAfolderID = '$aafolderID' AND StudentID = '$studentID'";
            $markResult = $conn->query($markQuery);
            $marks = $markResult->num_rows == 1 ? $markResult->fetch_assoc()['Marks'] : '-';

            $assignments[] = [
                'FolderName' => $folderName,
                'Marks' => $marks,
                'ShowFolder' => $showfolder
            ];
        }
    }

    // Fetch Assessmentfolder details
    $assessmentfolderQuery = "SELECT AssessmentfolderID, FolderName FROM assessmentfolder WHERE AssignteacherID = '$assignteacher_id'";
    $assessmentfolderResult = $conn->query($assessmentfolderQuery);

    if ($assessmentfolderResult->num_rows > 0) {
        while ($assessmentfolderRow = $assessmentfolderResult->fetch_assoc()) {
            $assessmentfolderID = $assessmentfolderRow['AssessmentfolderID'];
            $folderName = $assessmentfolderRow['FolderName'];

            // Fetch marks from mark table
            $markQuery = "SELECT Marks FROM mark WHERE AssessmentfolderID = '$assessmentfolderID' AND StudentID = '$studentID'";
            $markResult = $conn->query($markQuery);
            $marks = $markResult->num_rows == 1 ? $markResult->fetch_assoc()['Marks'] : '-';

            $assessments[] = [
                'FolderName' => $folderName,
                'Marks' => $marks
            ];
        }
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
    .table,
    .grade-container {
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
    .grade-container {
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

    /* Style for grade table */
    .skm-table {
        width: 100%;
        background-color: #fff;
        padding: 20px;
        margin-top: 30px;
    }

    .skm-table h2 {
        margin-bottom: 20px;
    }

    .skm-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .skm-table th, .skm-table td {
        padding: 15px;
        border: 1px solid #ccc;
        text-align: left;
        border-collapse: collapse;
    }

    .skm-table th {
        background-color: #f2f2f2;
    }

    .skm-table tbody tr:hover {
        background-color: #f9f9f9;
    }

    .skm-table button {
        background-color: #4CAF50;
        border: none;
        color: white;
        padding: 10px 20px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .skm-table button:hover {
        background-color: #45a049;
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

  <?php if ($choose_id == 1): ?>
    <div class="container">
      <div class="learn-container">
        <h2>Select Course</h2>
        <input type="text" id="searchInput" placeholder="Search course title...">
        <div id="learnGrid" class="learn-grid">
          <?php if (count($courses) > 0): ?>
            <?php foreach ($courses as $course): ?>
              <div class="learn-card">
                <a href="choose_grade_student.php?assignteacher_id=<?php echo htmlspecialchars($course['AssignteacherID']); ?>&choose_id=<?php echo $choose_id; ?>">
                  <img src="image/3.jpg" alt="Course Image">
                  <div class="content">
                    <h4><?php echo htmlspecialchars($course['CourseTitle']); ?></h4>
                    <p><?php echo $className; ?></p>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p>No courses assigned yet.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="grade-container">
          <h2>Grade</h2>
          <table class="skm-table">
            <thead>
                <tr>
                    <th>Folder Name</th>
                    <th>Marks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignments as $assignment): ?>
                  <?php if($assignment['ShowFolder'] == 0): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($assignment['FolderName']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['Marks']); ?></td>
                    </tr>
                  <?php endif; ?>
                <?php endforeach; ?>
                <?php foreach ($assessments as $assessment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($assessment['FolderName']); ?></td>
                        <td><?php echo htmlspecialchars($assessment['Marks']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
      </div>
    </div>
  <?php else: ?>
    <div class="table">
      <div class="skm-table">
        <h2>Grade</h2>

      </div>
    </div>
  <?php endif; ?>
</div>

  <?php include 'include/footer.php'; ?>

  <script>
    function navigateToPage(chooseId) {
          window.location.href = `student_grades.php?choose_id=${chooseId}`;
        }

    document.addEventListener("DOMContentLoaded", function () {
      var input = document.getElementById("searchInput");

      input.addEventListener("input", function () {
        searchCourses();
      });
    });

    function searchCourses() {
      var input, filter, cards, cardContainer, title, i;
      input = document.getElementById("searchInput");
      filter = input.value.toUpperCase();
      cardContainer = document.getElementById("learnGrid");
      cards = cardContainer.getElementsByClassName("learn-card");
      for (i = 0; i < cards.length; i++) {
        title = cards[i].querySelector(".content h4");
        if (title.innerText.toUpperCase().includes(filter)) {
          cards[i].style.display = "";
        } else {
          cards[i].style.display = "none";
        }
      }
    }
  </script>
</body>
</html>
