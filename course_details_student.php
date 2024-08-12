<?php
session_start();

// Check if StudentID is set in the session
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kvpd_elearning"; // Replace with your actual database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get StudentID from session
$studentID = $_SESSION['user_id'];

$course_id = intval($_GET['course_id']);
$class_id = intval($_GET['class_id']);

// Query the database to get Student details
$query = "SELECT * FROM student WHERE StudentID = $studentID";
$result = $conn->query($query);

if ($result->num_rows == 1) {
    // Fetch Student details
    $row = $result->fetch_assoc();
    $studentName = $row['StudentName'];
    $studentImage = $row['StudentImage'];
    $studentMatricNum = $row['StudentMatricNum'];
    $studentSKM = $row['SKM'];
    $classID = $row['ClassID'];
    $courseDepartment = $row['CourseDepartment'];
} else {
    // Handle error if Student details not found
    $studentName = "Unknown";
    $studentImage = "default_student_image.jpg"; // Replace with a default image path
    $studentMatricNum = "N/A";
    $studentSKM = "N/A";
    $classID = "N/A";
    $courseDepartment = "N/A";
}

// Query to get all CourseID values with the same CourseDepartment
$courses = [];
if ($courseDepartment !== "N/A") {
    $courseQuery = "SELECT CourseID, CourseTitle FROM course WHERE CourseDepartment = '$courseDepartment' AND SKM = '$studentSKM'";
    $courseResult = $conn->query($courseQuery);

    if ($courseResult->num_rows > 0) {
        while ($courseRow = $courseResult->fetch_assoc()) {
            $courses[] = $courseRow;
        }
    }
}

// Get the class name based on the class ID
$className = "";
if ($classID !== "N/A") {
    $classQuery = "SELECT ClassName FROM class WHERE ClassID = $classID";
    $classResult = $conn->query($classQuery);
    if ($classResult->num_rows == 1) {
        $classRow = $classResult->fetch_assoc();
        $className = $classRow['ClassName'];
    }
}

// Initialize variables to store AssignteacherID, CourseView, and TeacherID
$assignteacherID = "";
$courseOverview = "";
$teacherID = "";

// Query to get AssignteacherID, CourseView, and TeacherID based on $course_id and $class_id
$assignTeacherQuery = "SELECT AssignteacherID, CourseOverview, TeacherID FROM assignteacher WHERE CourseID = $course_id AND ClassID = $class_id";
$assignTeacherResult = $conn->query($assignTeacherQuery);

if ($assignTeacherResult->num_rows == 1) {
    $assignTeacherRow = $assignTeacherResult->fetch_assoc();
    $assignteacherID = $assignTeacherRow['AssignteacherID'];
    $courseOverview = $assignTeacherRow['CourseOverview'];
    $teacherID = $assignTeacherRow['TeacherID'];
}

// Fetch teacher details
$teacherName = "";
$teacherImage = "";
$teacherNumphone = "";
$teacherAddress = "";

if (!empty($teacherID)) {
    $teacherQuery = "SELECT * FROM teacher WHERE TeacherID = $teacherID";
    $teacherResult = $conn->query($teacherQuery);

    if ($teacherResult->num_rows == 1) {
        $teacherRow = $teacherResult->fetch_assoc();
        $teacherName = $teacherRow['TeacherName'];
        $teacherImage = $teacherRow['TeacherImage'];
        $teacherNumphone = $teacherRow['TeacherNumphone'];
        $teacherAddress = $teacherRow['TeacherAddress'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style/student/header.css">
    <link rel="stylesheet" href="style/student/course.css">
    <script src="script/admin/navbar.js"></script>
    <style>
        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .course-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .edit-course-container {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }

        .edit-course-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .course-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .course-detail {
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .course-detail h3 {
            margin-top: 0;
        }

        .course-detail p {
            margin: 0;
            color: #555;
        }

        .course-container h2{
            margin-bottom: 20px;
        }
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            justify-items: center;
        }
        .course-card {
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .course-card img {
            width: 100%;
            height: auto;
            border-bottom: 1px solid #ccc;
        }
        .course-card .content {
            padding: 15px;
        }
        .course-card h4 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .course-card p {
            margin: 0;
            color: #666;
        }
        .add-course-card {
            border: 2px dashed #aaa;
            background-color: #d1ffd6;
            cursor: pointer;
        }
        .course-card a {
            text-decoration: none; /* Remove underline */
            color: #333;
        }
        .teacher-detail-container {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }

        .teacher-detail-container img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
        }

        .teacher-details {
            flex: 1;
        }

        .teacher-details h3 {
            margin: 0;
        }

        .teacher-details p {
            margin: 0;
            color: #555;
        }
        /* Search input style */
        #searchInput {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            width: 40%;
            box-sizing: border-box; 
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<?php include 'include/studentheader.php'; ?>
<h2>COURSE</h2>
<div class="custom-line"></div>

<div class="container">
    <div class="course-container">
        <h2>Select Course</h2>
        <input type="text" id="searchInput" placeholder="Search course title...">
        <div id="courseGrid" class="course-grid">
            <?php if (count($courses) > 0): ?>
                <?php foreach ($courses as $course): ?>
                    <div class="course-card">
                        <a href="course_details_student.php?course_id=<?php echo htmlspecialchars($course['CourseID']); ?>&class_id=<?php echo htmlspecialchars($classID); ?>">
                            <img src="image/3.jpg" alt="Course Image">
                            <div class="content">
                                <h4><?php echo htmlspecialchars($course['CourseTitle']); ?></h4>
                                <p><?php echo $className; ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No courses available in this department.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="edit-course-container">
        <h2>COURSE</h2>
        <div class="course-details">
            <div class="course-detail">
                <h3>COURSE TITLE:</h3>
                <p><?php echo htmlspecialchars($course['CourseTitle']); ?></p>
            </div>
            <div class="course-detail">
                <h3>CLASS NAME:</h3>
                <p><?php echo $className; ?></p>
            </div>
            <div class="course-detail">
                <h3>Course Overview:</h3>
                <p><?php echo $courseOverview; ?></p>
            </div>
        </div>
    </div>   

    <?php if (!empty($teacherName)): ?>
    <div class="teacher-detail-container">
        <img src="<?php echo $teacherImage; ?>" alt="Teacher Image">
        <div class="teacher-details">
            <h3><?php echo $teacherName; ?></h3>
            <p>Phone: <?php echo $teacherNumphone; ?></p>
            <p>Address: <?php echo $teacherAddress; ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'include/footer.php'; ?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var input = document.getElementById("searchInput");

        input.addEventListener("input", function() {
            searchCourses();
        });
    });

    function searchCourses() {
        var input, filter, cards, cardContainer, title, i;
        input = document.getElementById("searchInput");
        filter = input.value.toUpperCase();
        cardContainer = document.getElementById("courseGrid");
        cards = cardContainer.getElementsByClassName("course-card");
        for (i = 0; i < cards.length; i++) {
            title = cards[i].querySelector(".content h4");
            if (title.innerText.toUpperCase().includes(filter)) {
                cards[i].style.display = "";
            } else {
                cards[i].style.display = "none";
            }
        }
    }
</script>

</body>
</html>