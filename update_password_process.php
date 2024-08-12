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

function validatePassword($password) {
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain at least one uppercase letter.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        return "Password must contain at least one lowercase letter.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "Password must contain at least one number.";
    }
    return true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_SESSION['verified_id'];
    $password = sanitizeInput($_POST['password']);
    $confirmPassword = sanitizeInput($_POST['confirm_password']);

    if ($password !== $confirmPassword) {
        setError("Password and Confirm Password do not match.");
    } else {
        $validationResult = validatePassword($password);
        if ($validationResult !== true) {
            setError($validationResult);
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $studentQuery = "SELECT * FROM student WHERE StudentMatricNum = '$id'";
            $studentResult = mysqli_query($conn, $studentQuery);

            $teacherQuery = "SELECT * FROM teacher WHERE TeacherUsername = '$id'";
            $teacherResult = mysqli_query($conn, $teacherQuery);

            if (mysqli_num_rows($studentResult) > 0) {
                $updateStudentQuery = "UPDATE student SET StudentPassword = '$hashedPassword' WHERE StudentMatricNum = '$id'";
                mysqli_query($conn, $updateStudentQuery);
                setSuccess("Password updated successfully for student!");
            } elseif (mysqli_num_rows($teacherResult) > 0) {
                $updateTeacherQuery = "UPDATE teacher SET TeacherPassword = '$hashedPassword' WHERE TeacherUsername = '$id'";
                mysqli_query($conn, $updateTeacherQuery);
                setSuccess("Password updated successfully for teacher!");
            } else {
                setError("Invalid ID.");
            }

            unset($_SESSION['verified_id']);
        }
    }
}

mysqli_close($conn);
header("Location: login.php");
exit();
?>
