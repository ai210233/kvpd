<header>
    <div class="menu-icon" onclick="toggleMenu()">☰</div>
    <div class="teacher-info">
        <a href="teacher_editprofile.php">
            <img src="<?php echo $teacherImage; ?>" class="teacher-image">
        </a>
    </div>
    <a href="login.php" class="logout-link">Logout</a>
</header>

<nav id="sidebar">
    <div id="close-btn" onclick="toggleMenu()">✕</div>
    <ul>
        <li>
            <div class="teacher-info-sidebar">
                <div class="teacher-details">
                    <p class="teacher-id"><?php echo $teacherName; ?></p>
                    <p class="teacher-id">ID: <?php echo $teacherID; ?></p>
                </div>
            </div>
        </li>
        <li><a href="teacher_dashboard.php">Dashboard</a></li>
        <li><a href="teacher_editprofile.php">Edit Profile</a></li>
        <li><a href="teacher_course.php">Course</a></li>
        <li><a href="teacher_learn.php">Learning Material</a></li>
        <li><a href="teacher_assignment.php">Assignment</a></li>
        <li><a href="teacher_assessment.php">Assessment</a></li>
        <li><a href="teacher_grade.php">Grade</a></li>
        <li><a href="teacher_report.php">Report</a></li>
    </ul>
</nav>