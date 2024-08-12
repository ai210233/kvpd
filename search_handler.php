<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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

$studentID = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['courseDropdown'])) {
        $selectedCourse = $_POST['courseDropdown'];

        // Assuming you have a CourseID associated with the selected CourseTitle
        $courseQuery = "SELECT CourseID FROM course WHERE CourseTitle = '$selectedCourse' AND StudentID = $studentID";
        $courseResult = $conn->query($courseQuery);

        if ($courseResult->num_rows == 1) {
            $courseRow = $courseResult->fetch_assoc();
            $courseID = $courseRow['CourseID'];

            // Get ClassName based on StudentID
            $classQuery = "SELECT ClassName FROM class WHERE StudentID = $studentID";
            $classResult = $conn->query($classQuery);

            if ($classResult->num_rows == 1) {
                $classRow = $classResult->fetch_assoc();
                $className = $classRow['ClassName'];

                // Get Learning Material based on CourseID and ClassName
                $materialQuery = "SELECT LearnMateName, LearnMateVideoPath, LearnMateImagePath, LearnMateFilePath, LearnMateDate FROM learningmaterial WHERE CourseID = $courseID AND ClassName = '$className'";
                $materialResult = $conn->query($materialQuery);

                // Display Learning Material
                while ($materialRow = $materialResult->fetch_assoc()) {
                    $learnMateName = $materialRow['LearnMateName'];
                    $learnMateVideoPath = $materialRow['LearnMateVideoPath'];
                    $learnMateImagePath = $materialRow['LearnMateImagePath'];
                    $learnMateFilePath = $materialRow['LearnMateFilePath'];
                    $learnMateDate = $materialRow['LearnMateDate'];

                    echo "<div class='material-item'>";
                    echo "<h4>$learnMateName</h4>";
                    // Display other details and add a Feedback button
                    echo "<button onclick='showFeedbackForm()'>Feedback</button>";
                    echo "</div>";
                }
            }
        }
    }
}

$conn->close();
?>
