<header>
    <div class="menu-icon" onclick="toggleMenu()">☰</div>
    <div class="admin-name"><?php echo $administratorName; ?></div>
    <a href="adminlogin.php" class="logout-link">Logout</a>
</header>

<!-- Navigation sidebar -->
<nav id="sidebar">
    <div id="close-btn" onclick="toggleMenu()">✕</div>
    <ul>
        <li><a href="admin_dashboard.php">Dashboard</a></li>
        <li><a href="adminteacher.php">Teacher</a></li>
        <li><a href="adminstudent.php">Student</a></li>
        <li><a href="admincourse.php">Course</a></li>
        <li><a href="adminclass.php">Class</a></li>
    </ul>
</nav>