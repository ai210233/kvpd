<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style/login.css">
    <link rel="stylesheet" href="style/headerfooter.css">
</head>
<body>

<?php include 'include/header.php'; ?>

<div class="login-container">
    <img class="login-image" src="image/gambar kvpd.jpg" alt="Login Image">
    
    <h2>Forgot Password</h2>
    <form action="forgot_password_process.php" method="post">
        <div class="form-group">
            <label for="id">ID:</label>
            <input type="text" id="id" name="id" required>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>

        <button type="submit">Verify</button>

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
