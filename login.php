<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style/login.css">
    <link rel="stylesheet" href="style/headerfooter.css">
</head>
<body>

<?php include 'include/header.php'; ?>

<div class="login-container">
    <!-- Your login form goes here -->
    <img class="login-image" src="image/gambar kvpd.jpg" alt="Login Image">
    
    <h2>Login</h2>
    <form action="login_process.php" method="post">
        <div class="form-group">
            <label for="username">ID:</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Login</button>

        <div class="message-container">
            <?php
            if (isset($_SESSION['error_message'])) {
                echo '<div class="error-message">' . $_SESSION['error_message'] . '</div>';
                unset($_SESSION['error_message']); // Clear the session variable
            }
            if (isset($_SESSION['success_message'])) {
                echo '<div class="success-message">' . $_SESSION['success_message'] . '</div>';
                unset($_SESSION['success_message']); // Clear the session variable
            }
            ?>
        </div>
    </form>

    

    <div class="register-link">
        <p>Don't remember password? <a href="forgot_password.php">Forget Password</a></p>
    </div>
</div>

<?php include 'include/footer.php'; ?>

</body>
</html>
