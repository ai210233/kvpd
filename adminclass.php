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

$searchClassID = "";
$errorMessage = "";
$classesData = array(); // Initialize $classesData

if (isset($_POST['currentSearch']) && $_POST['currentSearch'] == 1) {
    if (!empty($_POST['searchClassID'])) {
        $searchClassID = $_POST['searchClassID'];
        $queryClasses = "SELECT DISTINCT ClassID, ClassName, SKM FROM class WHERE ClassID = '$searchClassID'";
        $resultClasses = $conn->query($queryClasses);

        // Check for query execution success
        if (!$resultClasses) {
            die("Query failed: " . $conn->error);
        }

        // Check if any results found
        if ($resultClasses->num_rows == 0) {
            $errorMessage = "Class with ID $searchClassID not found";
        }
    } else {
        // If search input is empty, fetch unique ClassNames
        $queryClasses = "SELECT DISTINCT ClassID, ClassName, SKM FROM class";
        $resultClasses = $conn->query($queryClasses);

        // Check for query execution success
        if (!$resultClasses) {
            die("Query failed: " . $conn->error);
        }
    }
} else {
    // If search input is empty, fetch unique ClassNames
    $queryClasses = "SELECT DISTINCT ClassID, ClassName, SKM FROM class";
    $resultClasses = $conn->query($queryClasses);

    // Check for query execution success
    if (!$resultClasses) {
        die("Query failed: " . $conn->error);
    }
}

// Handle Student Deletion
if (isset($_POST['deleteClassName'])) {
    $deleteClassName = $_POST['deleteClassName'];

    // Perform deletion from the database
    $deleteQuery = "DELETE FROM class WHERE ClassName = '$deleteClassName'";
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
    <link rel="stylesheet" href="style/admin/class.css"> <!-- Update the CSS file for classes -->
    <script src="script/admin/navbar.js"></script>
</head>
<body>
<?php include 'include/adminheader.php'; ?>
<h2>CLASS</h2>
<div class="custom-line"></div>

<div class="class-container">
    <a class="add-button" href="addclass.php">Add Class</a>
</div>

<div class="class-container">
    <h3>Class List</h3>

<?php if ($resultClasses && $resultClasses->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Class Name</th>
                <th>SKM</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php $uniqueClassNames = array(); ?>
            <?php while ($rowClass = $resultClasses->fetch_assoc()): ?>
                <?php if (!in_array($rowClass['ClassName'], $uniqueClassNames)): ?>
                    <?php $uniqueClassNames[] = $rowClass['ClassName']; ?>
                    <tr>
                        <td><?php echo $rowClass['ClassName']; ?></td>
                        <td><?php echo $rowClass['SKM']; ?></td>
                        <td>
                            <a href='edit_class.php?id=<?php echo urlencode($rowClass['ClassID']); ?>'>Edit</a>
                            <a href='javascript:void(0);' onclick='confirmDelete("<?php echo $rowClass['ClassName']; ?>")'>Delete</a>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No classes found.</p>
<?php endif; ?>
</div>

<script>
    function confirmDelete(ClassName) {
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
            xhr.send("deleteClassName=" + ClassName); // Change parameter name here
        }
    }
</script>

<?php include 'include/footer.php'; ?>
</body>
</html>
