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

$searchClassName = "";
$message = "";

if (isset($_GET['added']) && $_GET['added'] == 1) {
    $message = "<p style='color: green;'>Class added successfully</p>";
}

if (isset($_POST['searchClassName']) && !empty($_POST['searchClassName'])) {
    $searchClassName = $_POST['searchClassName'];
    $queryClasses = "SELECT ClassName FROM class WHERE ClassName = '$searchClassName'";
    $resultClasses = $conn->query($queryClasses);

    if ($resultClasses->num_rows == 0) {
        $errorMessage = "Class with Name '$searchClassName' not found";
    } else {
        header("Location: ".$_SERVER['PHP_SELF']."?searched=1&searchClassName=".urlencode($searchClassName));
        exit();
    }
} else {
    $queryClasses = "SELECT ClassName, SKM FROM class";
    $resultClasses = $conn->query($queryClasses);
}

// Handle adding new class
if (isset($_POST['addClass'])) {
    $newClassName = $_POST['newClassName'];
    $skm = $_POST['skm'];

    // Get the maximum ClassID from the class table
    $maxClassIDQuery = "SELECT MAX(ClassID) AS maxClassID FROM class";
    $resultMaxClassID = $conn->query($maxClassIDQuery);

    if ($resultMaxClassID && $rowMaxClassID = $resultMaxClassID->fetch_assoc()) {
        // Increment the maximum ClassID by 1
        $newClassID = $rowMaxClassID['maxClassID'] + 1;

        // Use prepared statement to insert the new class
        $insertQuery = $conn->prepare("INSERT INTO class (ClassID, ClassName, SKM) VALUES (?, ?, ?)");
        $insertQuery->bind_param("iss", $newClassID, $newClassName, $skm);

        if ($insertQuery->execute()) {
            header("Location: ".$_SERVER['PHP_SELF']."?added=1");
            exit();
        } else {
            $message = "Error adding record: " . $conn->error;
        }

        $insertQuery->close();
    } else {
        $message = "Error retrieving maximum ClassID: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style/admin/header.css">
    <link rel="stylesheet" href="style/admin/course.css">
    <script src="script/admin/navbar.js"></script>
</head>
<body>
    <?php include 'include/adminheader.php'; ?>
    <h2>ADD CLASS</h2>
    <div class="custom-line"></div>

    <div class="course-container">
        <?php
        if (isset($_GET['added']) && $_GET['added'] == 1) {
            $message = "<p style='color: green;'>Class added successfully</p>";
        }
        ?>

        <form action="" method="post">
            <label for="newClassName">Class Name:</label>
            <input type="text" name="newClassName" id="newClassName" required>

            <label for="skm">SKM:</label>
            <select name="skm" id="skm" required>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
            </select>

            <button type="submit" name="addClass">Add Class</button>
        </form>

        <label><?php echo $message ?></label>
    </div>

    <div class="course-container">
        <a class="add-button" href="adminclass.php">Back</a>
    </div>

    <div class="course-container">
        <h3>Class List</h3>

        <table>
            <thead>
                <tr>
                    <th>Class Name</th>
                    <th>SKM</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $uniqueClassNames = array();
                while ($rowClass = $resultClasses->fetch_assoc()) {
                    $className = $rowClass['ClassName'];
                    if (!in_array($className, $uniqueClassNames)) {
                        echo "<tr>";
                        echo "<td>{$className}</td>";
                        echo "<td>{$rowClass['SKM']}</td>";
                        echo "</tr>";

                        $uniqueClassNames[] = $className;
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <?php include 'include/footer.php'; ?>
</body>
</html>
