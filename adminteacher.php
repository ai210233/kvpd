<?php
session_start();

// Check if AdministratorID is set in the session
if (!isset($_SESSION['AdministratorID'])) {
    // Redirect to login page if not logged in
    header("Location: adminlogin.php");
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

// Get AdministratorID from session
$administratorID = $_SESSION['AdministratorID'];

// Query the database to get AdministratorName
$query = "SELECT AdministratorName FROM administrator WHERE AdministratorID = $administratorID";
$result = $conn->query($query);

if ($result->num_rows == 1) {
    // Fetch AdministratorName
    $row = $result->fetch_assoc();
    $administratorName = $row['AdministratorName'];
} else {
    // Handle error if AdministratorName not found
    $administratorName = "Unknown";
}

$searchTerm = "";
$errorMessage = "";
$teachersData = array(); // Initialize $teachersData

if (isset($_POST['currentSearch']) && $_POST['currentSearch'] == 1) {
    if (!empty($_POST['searchTerm'])) {
        $searchTerm = $_POST['searchTerm'];
        $queryTeachers = "SELECT TeacherID, TeacherUsername, TeacherEmail, TeacherICnum, TeacherName, TeacherNumphone FROM teacher 
                          WHERE TeacherID LIKE '%$searchTerm%' 
                          OR TeacherUsername LIKE '%$searchTerm%' 
                          OR TeacherICnum LIKE '%$searchTerm%'";
    } else {
        // If search input is empty, fetch all teachers
        $queryTeachers = "SELECT TeacherID, TeacherUsername, TeacherEmail, TeacherICnum, TeacherName, TeacherNumphone FROM teacher";
    }
} else {
    // If search input is empty, fetch all teachers
    $queryTeachers = "SELECT TeacherID, TeacherUsername, TeacherEmail, TeacherICnum, TeacherName, TeacherNumphone FROM teacher";
}

$resultTeachers = $conn->query($queryTeachers);

// Check for query execution success
if (!$resultTeachers) {
    die("Query failed: " . $conn->error);
}

// Handle Teacher Deletion
if (isset($_POST['deleteTeacherID'])) {
    $deleteTeacherID = $_POST['deleteTeacherID'];

    // Perform deletion from the database
    $deleteQuery = "DELETE FROM teacher WHERE TeacherID = '$deleteTeacherID'";
    if ($conn->query($deleteQuery) === TRUE) {
        // Redirect after successful deletion
        header("Location: ".$_SERVER['PHP_SELF']."?deleted=1");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style/admin/header.css">
    <link rel="stylesheet" href="style/admin/teacher.css">
    <script src="script/admin/navbar.js"></script>
</head>
<body>
<?php include 'include/adminheader.php'; ?>
<h2>TEACHER</h2>
<div class="custom-line"></div>

<div class="teacher-container">
    <a class="add-button" href="addteacher.php">Add Teacher</a>
</div>

<div class="teacher-container">
    <h3>Teacher List</h3>

    <!-- Search Form -->
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <input type="hidden" name="currentSearch" value="1">
        <label for="searchTerm">Search by Username or IC:</label>
        <input type="text" name="searchTerm" id="searchTerm" value="<?php echo $searchTerm; ?>">
        <button type="submit">Search</button>
    </form>

    <?php
    if (!empty($errorMessage)) {
        echo "<p style='color: red;'>$errorMessage</p>";
    }
    // Display search success message
    if(isset($_GET['searched']) && $_GET['searched'] == 1) {
        echo "<p style='color: green;'>Search successful</p>";
    }
    // Display deletion success message
    if(isset($_GET['deleted']) && $_GET['deleted'] == 1) {
        echo "<p style='color: green;'>Teacher deleted successfully</p>";
    }
    ?>

<?php if ($resultTeachers && $resultTeachers->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>IC Number</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($rowTeacher = $resultTeachers->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $rowTeacher['TeacherUsername']; ?></td>
                    <td><?php echo $rowTeacher['TeacherICnum']; ?></td>
                    <td><?php echo $rowTeacher['TeacherName']; ?></td>
                    <td><?php echo $rowTeacher['TeacherEmail']; ?></td>
                    <td><?php echo $rowTeacher['TeacherNumphone']; ?></td>
                    <td>
                        <a href='edit_teacher.php?id=<?php echo $rowTeacher['TeacherID']; ?>'>Assign</a>
                        <a href='javascript:void(0);' onclick='confirmDelete(<?php echo $rowTeacher['TeacherID']; ?>)'>Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No teachers found.</p>
<?php endif; ?>
</div>

<script>
    function confirmDelete(teacherID) {
        var confirmDelete = confirm("Are you sure you want to delete this teacher?");
        if (confirmDelete) {
            // Send an AJAX request to handle the deletion
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    // Handle the response
                    alert(xhr.responseText);
                    // Refresh the page after successful deletion
                    if (xhr.responseText.includes("successful")) {
                        window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>';
                    }
                }
            };
            xhr.send("deleteTeacherID=" + teacherID);
        }
    }
</script>

<?php include 'include/footer.php'; ?>
</body>
</html>
