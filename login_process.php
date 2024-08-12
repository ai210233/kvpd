<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kvpd_elearning";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data));
}

function setError($errorMessage) {
    $_SESSION['error_message'] = $errorMessage;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);

    $studentQuery = "SELECT * FROM student WHERE StudentMatricNum = '$username'";
    $studentResult = mysqli_query($conn, $studentQuery);

    $teacherQuery = "SELECT * FROM teacher WHERE TeacherUsername = '$username'";
    $teacherResult = mysqli_query($conn, $teacherQuery);

    if (mysqli_num_rows($studentResult) > 0) {
        $studentData = mysqli_fetch_assoc($studentResult);

        if (password_verify($password, $studentData['StudentPassword'])) {
            // Successful login for student
            $_SESSION['user_id'] = $studentData['StudentID'];
            $_SESSION['user_type'] = 'student';
            header("Location: student_dashboard.php"); // Redirect to student dashboard
            exit();
        } else {
            setError("Invalid username or password.");
        }
    } elseif (mysqli_num_rows($teacherResult) > 0) {
        $teacherData = mysqli_fetch_assoc($teacherResult);

        if (password_verify($password, $teacherData['TeacherPassword'])) {
            // Successful login for teacher
            $_SESSION['user_id'] = $teacherData['TeacherID'];
            $_SESSION['user_type'] = 'teacher';
            header("Location: teacher_dashboard.php"); // Redirect to teacher dashboard
            exit();
        } else {
            setError("Invalid username or password.");
        }
    } else {
        setError("Invalid username or password.");
    }
}

mysqli_close($conn);
header("Location: login.php"); // Redirect to login page in case of error
exit();
?>
