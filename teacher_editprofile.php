<?php
session_start();

// Check if TeacherID is set in the session
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

// Get TeacherID from session
$teacherID = $_SESSION['user_id'];

// Query the database to get TeacherName and TeacherImage
$query = "SELECT * FROM teacher WHERE TeacherID = $teacherID";
$result = $conn->query($query);

if ($result->num_rows == 1) {
    // Fetch TeacherName and TeacherImage
    $row = $result->fetch_assoc();
    $teacherName = $row['TeacherName'];
    $teacherImage = $row['TeacherImage'];
    $teacherEmail = $row['TeacherEmail'];
    $teacherICnum = $row['TeacherICnum'];
    $teacherNumphone = $row['TeacherNumphone'];
    $teacherAddress = $row['TeacherAddress'];
    $teacherReligion = $row['TeacherReligion'];
    $teacherRace = $row['TeacherRace'];
    $teacherNationality = $row['TeacherNationality'];
} else {
    // Handle error if Teacher details not found
    $teacherName = "Unknown";
    $teacherImage = "images/default.png"; 
    $teacherEmail = "";
    $teacherICnum = "";
    $teacherNumphone = "";
    $teacherAddress = "";
    $teacherReligion = "";
    $teacherRace = "";
    $teacherNationality = "";
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
        $updateQuery = "UPDATE teacher SET TeacherImage = '$uploadFile' WHERE TeacherID = $teacherID";
        $conn->query($updateQuery);

        // Update the $teacherImage variable to display the new image
        $teacherImage = $uploadFile;
    } else {
        echo "Error uploading file.";
    }
}

// Handle delete picture
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_picture'])) {
    // Update the database with the default image path
    $updateQuery = "UPDATE teacher SET TeacherImage = 'images/default.png' WHERE TeacherID = $teacherID";
    $conn->query($updateQuery);

    // Update the $teacherImage variable to display the default image
    $teacherImage = 'images/default.png';
}

$message = "";
$changePasswordMessage = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle updating teacher details
    if (isset($_POST['save_teacher_details'])) {
        $teacherName = $_POST['teacher_name'];
        $teacherEmail = $_POST['teacher_email'];
        $teacherICnum = $_POST['teacher_icnum'];
        $teacherNumphone = $_POST['teacher_numphone'];

        $updateQuery = "UPDATE teacher SET 
                        TeacherName = '$teacherName', 
                        TeacherEmail = '$teacherEmail', 
                        TeacherICnum = '$teacherICnum', 
                        TeacherNumphone = '$teacherNumphone' 
                        WHERE TeacherID = $teacherID";

        if ($conn->query($updateQuery) === TRUE) {
            $message = "Updated successfully!";
        } else {
            $message = "Error updating teacher details: " . $conn->error;
        }
    }

    // Handle updating "Others" details
    if (isset($_POST['save_others_details'])) {
        $teacherAddress = $_POST['teacher_address'];
        $teacherReligion = $_POST['teacher_religion'];
        $teacherRace = $_POST['teacher_race'];
        $teacherNationality = $_POST['teacher_nationality'];

        $updateOthersQuery = "UPDATE teacher SET 
                              TeacherAddress = '$teacherAddress', 
                              TeacherReligion = '$teacherReligion', 
                              TeacherRace = '$teacherRace', 
                              TeacherNationality = '$teacherNationality' 
                              WHERE TeacherID = $teacherID";

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
        $fetchQuery = "SELECT TeacherPassword FROM teacher WHERE TeacherID = $teacherID";
        $fetchResult = $conn->query($fetchQuery);

        if ($fetchResult->num_rows == 1) {
            $row = $fetchResult->fetch_assoc();
            $hashedPassword = $row['TeacherPassword'];

            // Verify if the current password matches the hashed password
            if (password_verify($currentPassword, $hashedPassword)) {
                // Hash the new password
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                // Update the password and RegisterStatus in the database
                $updatePasswordQuery = "UPDATE teacher SET TeacherPassword = '$hashedNewPassword', RegisterStatus = 2 WHERE TeacherID = $teacherID";
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
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="style/teacher/header.css">
    <link rel="stylesheet" href="style/teacher/editprofile.css">
    <script src="script/admin/navbar.js"></script>
</head>

<body>
<?php include 'include/teacherheader.php'; ?>
<h2>TEACHER PROFILE</h2>
<div class="custom-line"></div>

<div class="container">
    <!-- First container for picture -->
    <div class="picture-container">
        <h3><?php echo $teacherName ?></h3>
        <!-- Add your form or upload logic here for updating the picture -->
        <form action="" method="post" enctype="multipart/form-data">
            <img src="<?php echo $teacherImage; ?>" alt="Teacher Image">
            <input type="file" name="profile_picture" accept="image/*">
            <button type="submit">Upload Picture</button>
        </form>
        <form id="delete-button" action="" method="post">
            <button type="submit" name="delete_picture">Delete Picture</button>
        </form>
    </div>

    <!-- Second container for details -->
    <div class="details-container">
        <h3>Edit Teacher Details</h3>
        <div>
            <button onclick="showDetails('teacher')">Teacher Details</button>
            <button onclick="showDetails('others')">Others</button>
            <button onclick="showDetails('changePassword')">Change Password</button> <!-- Updated button text -->
        </div>

        <!-- Teacher Details -->
        <form id="teacher-details" method="post">
            <label for="teacher_name">Name:</label>
            <input type="text" id="teacher_name" name="teacher_name" value="<?php echo $teacherName; ?>">

            <label for="teacher_email">Email:</label>
            <input type="email" id="teacher_email" name="teacher_email" value="<?php echo $teacherEmail; ?>">

            <label for="teacher_icnum">IC Number:</label>
            <input type="text" id="teacher_icnum" name="teacher_icnum" value="<?php echo $teacherICnum; ?>">

            <label for="teacher_numphone">Phone Number:</label>
            <input type="text" id="teacher_numphone" name="teacher_numphone" value="<?php echo $teacherNumphone; ?>">

            <button type="submit" name="save_teacher_details">Save</button>
            <label><?php echo $message ?></label>
        </form>

        <!-- Others Details -->
        <form id="others-details" style="display: none;" method="post">
            <label for="teacher_address">Address:</label>
            <input type="text" id="teacher_address" name="teacher_address" value="<?php echo $teacherAddress; ?>">

            <label for="teacher_religion">Religion:</label>
            <input type="text" id="teacher_religion" name="teacher_religion" value="<?php echo $teacherReligion; ?>">

            <label for="teacher_race">Race:</label>
            <input type="text" id="teacher_race" name="teacher_race" value="<?php echo $teacherRace; ?>">

            <label for="teacher_nationality">Nationality:</label>
            <input type="text" id="teacher_nationality" name="teacher_nationality" value="<?php echo $teacherNationality; ?>">

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
        var teacherDetails = document.getElementById('teacher-details');
        var othersDetails = document.getElementById('others-details');
        var changePasswordForm = document.getElementById('change-password-form'); // Get the change password form

        if (type === 'teacher') {
            teacherDetails.style.display = 'flex';
            othersDetails.style.display = 'none';
            changePasswordForm.style.display = 'none'; // Hide change password form if shown
        } else if (type === 'others') {
            teacherDetails.style.display = 'none';
            othersDetails.style.display = 'flex';
            changePasswordForm.style.display = 'none'; // Hide change password form if shown
        } else if (type === 'changePassword') { // Add condition for 'changePassword'
            teacherDetails.style.display = 'none';
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
