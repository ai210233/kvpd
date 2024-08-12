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

// Fetch and display classes from the database
$searchClassID = "";
$errorMessage = "";

if (isset($_POST['currentSearch']) && $_POST['currentSearch'] == 1) {
    if (!empty($_POST['searchClassID'])) {
        $searchClassID = $_POST['searchClassID'];
        $queryClasses = "SELECT ClassID, ClassName, SKM FROM class WHERE ClassID = '$searchClassID'";
        $resultClasses = $conn->query($queryClasses);

        // Check if any results found
        if ($resultClasses->num_rows == 0) {
            $errorMessage = "Class with ID $searchClassID not found";
        }
    }
} else {
    $queryClasses = "SELECT ClassID, ClassName, SKM FROM class";
    $resultClasses = $conn->query($queryClasses);
}

// Handle update process
if (isset($_POST['editClassID'])) {
    $editClassID = $_POST['editClassID'];
    $editClassName = $_POST['editClassName'];
    $editSKM = $_POST['editSKM'];

    // Check if the new ClassName is unique
    $checkQuery = "SELECT ClassID FROM class WHERE ClassName = '$editClassName' AND ClassID != '$editClassID'";
    $checkResult = $conn->query($checkQuery);

    if ($checkResult->num_rows > 0) {
        // Display error if ClassName is not unique
        echo "Error: ClassName '$editClassName' already exists. Choose a different ClassName.";
    } else {
        // Update class details in the class table
        $updateQuery = "UPDATE class SET ClassName = '$editClassName', SKM = '$editSKM' WHERE ClassID = '$editClassID'";
        
        if ($conn->query($updateQuery) === TRUE) {
            // Redirect after successful update
            header("Location: edit_class.php?id=" . $editClassID . "&updated=1");
            exit();
        } else {
            echo "Error updating record: " . $conn->error;
        }
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
    <link rel="stylesheet" href="style/admin/class.css"> <!-- Updated style reference -->
    <script src="script/admin/navbar.js"></script>
</head>
<body>
<?php include 'include/adminheader.php'; ?>
<h2>EDIT CLASS</h2> <!-- Updated heading -->
<div class="custom-line"></div>

<div class="class-container"> <!-- Updated class-container class -->
    <?php
    if (isset($_GET['id'])) {
        $editClassID = $_GET['id'];

        // Query to get Class details based on ClassID
        $queryEditClass = "SELECT ClassName, SKM FROM class WHERE ClassID = '$editClassID'";
        $resultEditClass = $conn->query($queryEditClass);

        if ($resultEditClass->num_rows == 1) {
            $rowEditClass = $resultEditClass->fetch_assoc();
            $editClassName = $rowEditClass['ClassName'];
            $editSKM = $rowEditClass['SKM'];
            ?>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                <input type="hidden" name="editClassID" value="<?php echo $editClassID; ?>">
                
                <!-- Allow editing of Class Name -->
                <label for="editClassName">Class Name:</label>
                <input type="text" name="editClassName" id="editClassName" value="<?php echo $editClassName; ?>">

                <!-- Allow editing of SKM -->
                <label for="editSKM">SKM:</label>
                <select name="editSKM" id="editSKM">
                    <option value="1" <?php echo ($editSKM == '1') ? 'selected' : ''; ?>>1</option>
                    <option value="2" <?php echo ($editSKM == '2') ? 'selected' : ''; ?>>2</option>
                    <option value="3" <?php echo ($editSKM == '3') ? 'selected' : ''; ?>>3</option>
                </select>

                <button type="submit">Update Class</button>
            </form>
            <?php
        } else {
            echo "<p style='color: red;'>Class not found</p>";
        }
    }
    ?>
</div>

<div class="class-container"> <!-- Updated class-container class -->
    <a class="add-button" href="adminclass.php">Back</a> <!-- Updated link to adminclass.php -->
</div>

<script>
    
</script>

<?php include 'include/footer.php'; ?>
</body>
</html>
