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

$teacherID = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['learnMateID'])) {
        $learnMateID = $_POST['learnMateID'];

        // Check if the learning material belongs to the logged-in teacher
        $checkOwnershipQuery = "SELECT * FROM learningmaterial WHERE TeacherID = $teacherID AND LearnMateID = $learnMateID";
        $checkOwnershipResult = $conn->query($checkOwnershipQuery);

        if ($checkOwnershipResult->num_rows > 0) {
            // Delete the learning material
            $deleteQuery = "DELETE FROM learningmaterial WHERE LearnMateID = $learnMateID";
            $deleteResult = $conn->query($deleteQuery);

            if (!$deleteResult) {
                echo "Error deleting learning material: " . $conn->error;
            }
        } else {
            echo "Unauthorized access to delete learning material.";
        }
    } else {
        echo "Missing learnMateID parameter.";
    }
}

// Redirect back to the teacher_learn.php page
header("Location: teacher_learn.php");
exit();

// Close the connection
$conn->close();
?>