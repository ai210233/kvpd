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
    $name = sanitizeInput($_POST['name']);
    $phone = sanitizeInput($_POST['phone']);
    $password = sanitizeInput($_POST['password']);
    $confirmPassword = sanitizeInput($_POST['confirm_password']);

    if ($password !== $confirmPassword) {
        setError("Password and Confirm Password do not match.");
    } else {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $studentQuery = "SELECT * FROM student WHERE StudentID = '$id'";
        $studentResult = mysqli_query($conn, $studentQuery);

        $teacherQuery = "SELECT * FROM teacher WHERE TeacherID = '$id'";
        $teacherResult = mysqli_query($conn, $teacherQuery);

        if (mysqli_num_rows($studentResult) > 0) {
            $studentData = mysqli_fetch_assoc($studentResult);

            if ($studentData['RegisterStatus'] == 1) {
                $updateStudentQuery = "UPDATE student SET StudentName = '$name', StudentEmail = '$email', StudentPassword = '$hashedPassword', StudentNumphone = '$phone', RegisterStatus = 2, StudentImage = 'images/default.png' WHERE StudentID = '$id'";
                mysqli_query($conn, $updateStudentQuery);
                setSuccess("Registration successful for student!");
                $_SESSION['user_id'] = $id;
                $_SESSION['user_type'] = 'student';
                header("Location: student_dashboard.php?studentID=$id");
                exit();
            } elseif ($studentData['RegisterStatus'] == 2) {
                setError("ID has already been registered.");
            }
        } elseif (mysqli_num_rows($teacherResult) > 0) {
            $teacherData = mysqli_fetch_assoc($teacherResult);

            if ($teacherData['TeacherEmail'] == $email) {
                $updateTeacherQuery = "UPDATE teacher SET TeacherName = '$name', TeacherPassword = '$hashedPassword', TeacherNumphone = '$phone', TeacherImage = 'images/default.png' WHERE TeacherID = '$id'";
                mysqli_query($conn, $updateTeacherQuery);
                setSuccess("Registration successful for teacher!");
                $_SESSION['user_id'] = $id;
                $_SESSION['user_type'] = 'teacher';
                header("Location: teacher_dashboard.php?teacherID=$id");
                exit();
            } else {
                setError("Invalid ID or TeacherEmail.");
            }
        } else {
            setError("Invalid ID.");
        }
    }
}

mysqli_close($conn);
header("Location: register.php");
exit();
?>
