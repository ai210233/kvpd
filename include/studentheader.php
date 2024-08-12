<header>
    <div class="menu-icon" onclick="toggleMenu()">☰</div>
    <div class="student-info">
        <a href="student_editprofile.php">
            <img src="<?php echo $studentImage; ?>" class="student-image">
        </a>
    </div>
    <a href="login.php" class="logout-link">Logout</a>
</header>

<nav id="sidebar">
    <div id="close-btn" onclick="toggleMenu()">✕</div>
    <ul>
        <li>
            <div class="student-info-sidebar">
                <div class="student-details">
                    <p class="student-name"><?php echo $studentName; ?></p>
                    <p class="student-id">Matric Number: <?php echo $studentMatricNum; ?></p>
                    <p class="student-skm">SKM: <?php echo $studentSKM; ?></p>
                </div>
            </div>
        </li>
        <li><a href="student_dashboard.php">Dashboard</a></li>
        <li><a href="student_editprofile.php">Edit Profile</a></li>
        <li><a href="student_courses.php">Course</a></li>
        <li><a href="student_material.php">Learning Material</a></li>
        <li><a href="student_assignment.php">Assignment</a></li>
        <li><a href="student_assessment.php">Assessment</a></li>
        <li><a href="student_grade.php">Grade</a></li>
    </ul>
</nav>
