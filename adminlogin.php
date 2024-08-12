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

$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get username and password from the form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query to check if the provided credentials are valid
    $query = "SELECT * FROM administrator WHERE AdministratorUsername = '$username' AND AdministratorPassword = '$password'";
    
    // Perform the query
    $result = $conn->query($query);

    // Check if the query was successful and if a matching record was found
    if ($result && $result->num_rows > 0) {
        // Fetch the AdministratorID from the result
        $row = $result->fetch_assoc();
        $administratorID = $row['AdministratorID'];

        // Store AdministratorID in session
        $_SESSION['AdministratorID'] = $administratorID;

        // Close the database connection
        $conn->close();

        // Redirect to admin dashboard
        header("Location: admin_dashboard.php");
        exit();
    } else {
        // Authentication failed, display an error message
        $errorMessage = "Invalid username or password";
    }
}
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="style/login.css">
    <link rel="stylesheet" href="style/headerfooter.css">
</head>
<body>

<div class="header">
    <a href="login.php">Login</a>
</div>

<div class="login-container">
    <!-- Your login form goes here -->
    <img class="login-image" src="image/gambar kvpd.jpg" alt="Login Image">
    
    <h2>Administrator Login</h2>
    <form action="adminlogin.php" method="post">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Login</button>
        <div class="message-container">
            <?php
            echo  $errorMessage;
            ?>
        </div>
    </form>
</div>

<?php include 'include/footer.php'; ?>

</body>
</html>
