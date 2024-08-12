<?php
session_start();

if (!isset($_SESSION['verified_id'])) {
    header("Location: forgot_password.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
    <link rel="stylesheet" href="style/login.css">
    <link rel="stylesheet" href="style/headerfooter.css">
</head>
<body>

<?php include 'include/header.php'; ?>

<div class="login-container">
    <img class="login-image" src="image/gambar kvpd.jpg" alt="Login Image">
    
    <h2>Update Password</h2>
    <form action="update_password_process.php" method="post">
        <div class="form-group">
            <label for="password">New Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>

        <button type="submit">Update Password</button>

        <div class="message-container">
            <?php
            if (isset($_SESSION['error_message'])) {
                echo '<div class="error-message">' . $_SESSION['error_message'] . '</div>';
                unset($_SESSION['error_message']);
            }
            if (isset($_SESSION['success_message'])) {
                echo '<div class="success-message">' . $_SESSION['success_message'] . '</div>';
                unset($_SESSION['success_message']);
            }
            ?>
        </div>
    </form>
</div>

<?php include 'include/footer.php'; ?>

</body>
</html>
