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

$searchStudentID = "";
$searchMatricNumber = "";
$errorMessage = "";
$studentsData = array(); // Initialize $studentsData


if (isset($_POST['currentSearch']) && $_POST['currentSearch'] == 1) {
    if (!empty($_POST['searchMatricNumber'])) {
        $searchMatricNumber = $_POST['searchMatricNumber'];
        $queryStudents = "SELECT StudentID, StudentMatricNum, StudentICnum, StudentName FROM student WHERE StudentMatricNum LIKE '%$searchMatricNumber%'";
        $resultStudents = $conn->query($queryStudents);

        // Check for query execution success
        if (!$resultStudents) {
            die("Query failed: " . $conn->error);
        }

        // Check if any results found
        if ($resultStudents->num_rows == 0) {
            $errorMessage = "Student with Matric Number $searchMatricNumber not found";
        }
    } else {
        $queryStudents = "SELECT StudentID, StudentMatricNum, StudentICnum, StudentName FROM student";
        $resultStudents = $conn->query($queryStudents);

        // Check for query execution success
        if (!$resultStudents) {
            die("Query failed: " . $conn->error);
        }
    }
} else {
    $queryStudents = "SELECT StudentID, StudentMatricNum, StudentICnum, StudentName FROM student";
    $resultStudents = $conn->query($queryStudents);

    // Check for query execution success
    if (!$resultStudents) {
        die("Query failed: " . $conn->error);
    }
}

// Handle Student Deletion
if (isset($_POST['deleteStudentID'])) {
    $deleteStudentID = $_POST['deleteStudentID'];

    // Perform deletion from the database
    $deleteQuery = "DELETE FROM student WHERE StudentID = '$deleteStudentID'";
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
    <link rel="stylesheet" href="style/admin/student.css"> <!-- Update the CSS file for students -->
    <script src="script/admin/navbar.js"></script>
</head>
<body>
<?php include 'include/adminheader.php'; ?>
<h2>STUDENT</h2>
<div class="custom-line"></div>

<div class="student-container">
    <a class="add-button" href="addstudent.php">Add Student</a>
</div>

<div class="student-container">
    <h3>Student List</h3>

    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <input type="hidden" name="currentSearch" value="1">
        <label for="searchMatricNumber">Search by Matric Number:</label>
        <input type="text" name="searchMatricNumber" id="searchMatricNumber" value="<?php echo $searchMatricNumber; ?>">
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
        echo "<p style='color: green;'>Student deleted successfully</p>";
    }
    ?>

    <?php if ($resultStudents && $resultStudents->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Matric Number</th>
                    <th>IC Number</th>
                    <th>Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($rowStudent = $resultStudents->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $rowStudent['StudentMatricNum']; ?></td>
                        <td><?php echo $rowStudent['StudentICnum']; ?></td>
                        <td><?php echo $rowStudent['StudentName']; ?></td>
                        <td>
                            <a href='edit_student.php?id=<?php echo $rowStudent['StudentID']; ?>'>Edit</a>
                            <a href='javascript:void(0);' onclick='confirmDelete(<?php echo $rowStudent['StudentID']; ?>)'>Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No students found.</p>
    <?php endif; ?>
</div>

<script>
    function confirmDelete(studentID) {
        var confirmDelete = confirm("Are you sure you want to delete this student?");
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
            xhr.send("deleteStudentID=" + studentID);
        }
    }
</script>

<?php include 'include/footer.php'; ?>
</body>
</html>
