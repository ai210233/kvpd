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

function setSuccess($successMessage) {
    $_SESSION['success_message'] = $successMessage;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = sanitizeInput($_POST['id']);
    $email = sanitizeInput($_POST['email']);

    $studentQuery = "SELECT * FROM student WHERE StudentMatricNum = '$id' AND StudentEmail = '$email'";
    $studentResult = mysqli_query($conn, $studentQuery);

    $teacherQuery = "SELECT * FROM teacher WHERE TeacherUsername = '$id' AND TeacherEmail = '$email'";
    $teacherResult = mysqli_query($conn, $teacherQuery);

    if (mysqli_num_rows($studentResult) > 0 || mysqli_num_rows($teacherResult) > 0) {
        setSuccess("Verification successful. Please set your new password.");
        $_SESSION['verified_id'] = $id;
        header("Location: update_password.php");
        exit();
    } else {
        setError("Invalid ID or Email.");
    }
}

mysqli_close($conn);
header("Location: forgot_password.php");
exit();
?>
