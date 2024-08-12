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

// Query the database to get StudentName and StudentImage
$query = "SELECT * FROM student WHERE StudentID = $studentID";
$result = $conn->query($query);

if ($result->num_rows == 1) {
    // Fetch StudentName and StudentImage
    $row = $result->fetch_assoc();
    $studentName = $row['StudentName'];
    $studentImage = $row['StudentImage'];
    $studentEmail = $row['StudentEmail'];
    $studentICnum = $row['StudentICnum'];
    $studentMatricNum = $row['StudentMatricNum'];
    $studentNumphone = $row['StudentNumphone'];
    $studentAddress = $row['StudentAddress'];
    $studentReligion = $row['StudentReligion'];
    $studentRace = $row['StudentRace'];
    $studentNationality = $row['StudentNationality'];
    $studentSKM = $row['SKM'];
} else {
    // Handle error if Student details not found
    $studentName = "Unknown";
    $studentImage = "images/default.png"; 
    $studentEmail = "";
    $studentICnum = "";
    $studentNumphone = "";
    $studentAddress = "";
    $studentReligion = "";
    $studentRace = "";
    $studentNationality = "";
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = "images/"; // Adjust the directory path according to your project structure

    // Create the directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uploadFile = $uploadDir . basename($_FILES['profile_picture']['name']);

    // Move the uploaded file to the specified directory
    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadFile)) {
        // Update the database with the image URL
        $updateQuery = "UPDATE student SET StudentImage = '$uploadFile' WHERE StudentID = $studentID";
        $conn->query($updateQuery);

        // Update the $studentImage variable to display the new image
        $studentImage = $uploadFile;
    } else {
        echo "Error uploading file.";
    }
}

// Handle delete picture
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_picture'])) {
    // Update the database with the default image path
    $updateQuery = "UPDATE student SET StudentImage = 'images/default.png' WHERE StudentID = $studentID";
    $conn->query($updateQuery);

    // Update the $studentImage variable to display the default image
    $studentImage = 'images/default.png';
}

$message ="";
$changePasswordMessage = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle updating student details
    if (isset($_POST['save_student_details'])) {
        $studentName = $_POST['student_name'];
        $studentEmail = $_POST['student_email'];
        $studentICnum = $_POST['student_icnum'];
        $studentNumphone = $_POST['student_numphone'];

        $updateQuery = "UPDATE student SET 
                        StudentName = '$studentName', 
                        StudentEmail = '$studentEmail', 
                        StudentICnum = '$studentICnum', 
                        StudentNumphone = '$studentNumphone' 
                        WHERE StudentID = $studentID";

        if ($conn->query($updateQuery) === TRUE) {
            $message = "Updated successfully!";
        } else {
            $message = "Error updating student details: " . $conn->error;
        }
    }

    // Handle updating "Others" details
    if (isset($_POST['save_others_details'])) {
        $studentAddress = $_POST['student_address'];
        $studentReligion = $_POST['student_religion'];
        $studentRace = $_POST['student_race'];
        $studentNationality = $_POST['student_nationality'];

        $updateOthersQuery = "UPDATE student SET 
                              StudentAddress = '$studentAddress', 
                              StudentReligion = '$studentReligion', 
                              StudentRace = '$studentRace', 
                              StudentNationality = '$studentNationality' 
                              WHERE StudentID = $studentID";

        if ($conn->query($updateOthersQuery) === TRUE) {
            $message = "Updated successfully!";
        } else {
            $message = "Error updating others details: " . $conn->error;
        }
    }
}

// Handle change password form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Verify if the new password matches the confirm password
    if ($newPassword !== $confirmPassword) {
        $changePasswordMessage = "New password and confirm password do not match.";
    } else {
        // Fetch the current hashed password from the database
        $fetchQuery = "SELECT StudentPassword FROM student WHERE StudentID = $studentID";
        $fetchResult = $conn->query($fetchQuery);

        if ($fetchResult->num_rows == 1) {
            $row = $fetchResult->fetch_assoc();
            $hashedPassword = $row['StudentPassword'];

            // Verify if the current password matches the hashed password
            if (password_verify($currentPassword, $hashedPassword)) {
                // Hash the new password
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                // Update the password and RegisterStatus in the database
                $updatePasswordQuery = "UPDATE student SET StudentPassword = '$hashedNewPassword', RegisterStatus = 2 WHERE StudentID = $studentID";
                if ($conn->query($updatePasswordQuery) === TRUE) {
                    $changePasswordMessage = "Password changed successfully!";
                } else {
                    $changePasswordMessage = "Error updating password: " . $conn->error;
                }
            } else {
                $changePasswordMessage = "Current password is incorrect.";
            }
        } else {
            $changePasswordMessage = "Error fetching password from the database.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style/student/header.css">
    <link rel="stylesheet" href="style/student/editprofile.css">
    <script src="script/admin/navbar.js"></script>
</head>

<body>
<?php include 'include/studentheader.php'; ?>
<h2>STUDENT PROFILE</h2>
<div class="custom-line"></div>

<div class="container">
    <!-- First container for picture -->
    <div class="picture-container">
        <h3><?php echo $studentName ?></h3>
        <!-- Add your form or upload logic here for updating the picture -->
        <form action="" method="post" enctype="multipart/form-data">
            <img src="<?php echo $studentImage; ?>" alt="Student Image">
            <input type="file" name="profile_picture" accept="image/*">
            <button type="submit">Upload Picture</button>
        </form>
        <form id="delete-button" action="" method="post">
            <button type="submit" name="delete_picture">Delete Picture</button>
        </form>
    </div>

    <!-- Second container for details -->
    <div class="details-container">
        <h3>Edit Student Details</h3>
        <div>
            <button onclick="showDetails('student')">Student Details</button>
            <button onclick="showDetails('others')">Others</button>
            <button onclick="showDetails('changePassword')">Change Password</button> <!-- Updated button text -->
        </div>

        <!-- Student Details -->
        <form id="student-details" method="post">
            <label for="student_name">Name:</label>
            <input type="text" id="student_name" name="student_name" value="<?php echo $studentName; ?>">

            <label for="student_email">Email:</label>
            <input type="email" id="student_email" name="student_email" value="<?php echo $studentEmail; ?>">

            <label for="student_icnum">IC Number:</label>
            <input type="text" id="student_icnum" name="student_icnum" value="<?php echo $studentICnum; ?>">

            <label for="student_numphone">Phone Number:</label>
            <input type="text" id="student_numphone" name="student_numphone" value="<?php echo $studentNumphone; ?>">

            <button type="submit" name="save_student_details">Save</button>
            <label><?php echo $message ?></label>
        </form>

        <!-- Others Details -->
        <form id="others-details" style="display: none;" method="post">
            <label for="student_address">Address:</label>
            <input type="text" id="student_address" name="student_address" value="<?php echo $studentAddress; ?>">

            <label for="student_religion">Religion:</label>
            <input type="text" id="student_religion" name="student_religion" value="<?php echo $studentReligion; ?>">

            <label for="student_race">Race:</label>
            <input type="text" id="student_race" name="student_race" value="<?php echo $studentRace; ?>">

            <label for="student_nationality">Nationality:</label>
            <input type="text" id="student_nationality" name="student_nationality" value="<?php echo $studentNationality; ?>">

            <button type="submit" name="save_others_details">Save</button>
            <label><?php echo $message ?></label>
        </form>

        <!-- Change Password Form -->
        <form id="change-password-form" style="display: none;" method="post" onsubmit="return validatePassword()">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>

            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            
            <button type="submit" name="change_password">Change Password</button>
            <label id="password_error"><?php echo $changePasswordMessage ?></label> <!-- Adjust the message variable -->
        </form>
    </div>
    
</div>


<?php include 'include/footer.php'; ?>
</body>
</html>

<script>
    function showDetails(type) {
        var studentDetails = document.getElementById('student-details');
        var othersDetails = document.getElementById('others-details');
        var changePasswordForm = document.getElementById('change-password-form'); // Get the change password form

        if (type === 'student') {
            studentDetails.style.display = 'flex';
            othersDetails.style.display = 'none';
            changePasswordForm.style.display = 'none'; // Hide change password form if shown
        } else if (type === 'others') {
            studentDetails.style.display = 'none';
            othersDetails.style.display = 'flex';
            changePasswordForm.style.display = 'none'; // Hide change password form if shown
        } else if (type === 'changePassword') { // Add condition for 'changePassword'
            studentDetails.style.display = 'none';
            othersDetails.style.display = 'none';
            changePasswordForm.style.display = 'flex'; // Show change password form
        }
    }

    function validatePassword() {
        var newPassword = document.getElementById("new_password").value;
        var confirmPassword = document.getElementById("confirm_password").value;
        var passwordError = document.getElementById("password_error");

        // Check if the passwords match
        if (newPassword !== confirmPassword) {
            passwordError.textContent = "Passwords do not match.";
            return false;
        }

        // Check if the password meets the requirements
        var passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/;
        if (!passwordRegex.test(newPassword)) {
            passwordError.textContent = "Password must contain at least 8 characters, including one uppercase letter, one lowercase letter, and one number.";
            return false;
        }

        // Reset password error message
        passwordError.textContent = "";
        return true;
    }
</script>

<?php
// Close the connection
$conn->close();
?>
