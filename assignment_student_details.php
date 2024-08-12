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

$assignteacher_id = intval($_GET['assignteacher_id']); 
$AAselect_id = intval($_GET['AAselect_id']); 

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
            $courseID = $courseRow['CourseID'];
            $courseTitle = $courseRow['CourseTitle'];

            // Get AssignteacherID based on ClassID and CourseID
            $assignteacherQuery = "SELECT AssignteacherID FROM assignteacher WHERE ClassID = '$classID' AND CourseID = '$courseID'";
            $assignteacherResult = $conn->query($assignteacherQuery);

            if ($assignteacherResult->num_rows == 1) {
                $assignteacherRow = $assignteacherResult->fetch_assoc();
                $assignteacherID = $assignteacherRow['AssignteacherID'];
            } else {
                $assignteacherID = null;
            }

            $courses[] = [
                'CourseID' => $courseID,
                'CourseTitle' => $courseTitle,
                'AssignteacherID' => $assignteacherID
            ];
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

// Retrieve ClassID, CourseID, and TeacherID based on $assignteacherID
$assignteacherQuery = "SELECT ClassID, CourseID, TeacherID FROM assignteacher WHERE AssignteacherID = $assignteacher_id";
$assignteacherResult = $conn->query($assignteacherQuery);

if ($assignteacherResult->num_rows == 1) {
    $assignteacherRow = $assignteacherResult->fetch_assoc();
    $assignclassID = $assignteacherRow['ClassID'];
    $assigncourseID = $assignteacherRow['CourseID'];
    $assignteacherID = $assignteacherRow['TeacherID'];

    // Retrieve ClassName based on ClassID
    $classQuery = "SELECT ClassName FROM class WHERE ClassID = $assignclassID";
    $classResult = $conn->query($classQuery);
    if ($classResult->num_rows == 1) {
        $classRow = $classResult->fetch_assoc();
        $assignclassName = $classRow['ClassName'];
    } else {
        // Handle error if class name not found
        $assignclassName = "Unknown";
    }

    // Retrieve CourseTitle based on CourseID
    $courseQuery = "SELECT CourseTitle FROM course WHERE CourseID = $assigncourseID";
    $courseResult = $conn->query($courseQuery);
    if ($courseResult->num_rows == 1) {
        $courseRow = $courseResult->fetch_assoc();
        $assigncourseTitle = $courseRow['CourseTitle'];
    } else {
        // Handle error if course title not found
        $assigncourseTitle = "Unknown";
    }
} else {
    // Handle error if assignteacher details not found
    $assignclassID = "Unknown";
    $assigncourseID = "Unknown";
    $assignteacherID = "Unknown";
    $assignclassName = "Unknown";
    $assigncourseTitle = "Unknown";
}

// Query to get learning materials
$learningMaterialQuery = "SELECT * FROM aafolder WHERE AssignteacherID = $assignteacher_id AND SelectionAA = $AAselect_id";
$learningMaterialResult = $conn->query($learningMaterialQuery);

// Check for errors in query execution
if (!$learningMaterialResult) {
    echo "Error: " . $conn->error;
}

$learningMaterials = [];

// Fetch and store data in $learningMaterials array
if ($learningMaterialResult->num_rows > 0) {
    while ($materialRow = $learningMaterialResult->fetch_assoc()) {
        $learningMaterials[] = $materialRow;
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
        .assignment-container {
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
            margin-top: 30px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .assignment-options {
            display: flex;
            gap: 20px;
            justify-content: center;
            width: 100%;
        }

        .assignment-option {
            padding: 15px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            color: #fff;
            transition: background-color 0.3s ease, transform 0.3s;
            flex: 1;
            text-align: center;
        }

        .individual {
            background-color: #000000;
        }

        .group {
            background-color: #000000;
        }

        .assignment-option:hover {
            background-color: #969494;
        }

        .assignment-option:active {
            transform: translateY(2px);
        }

        .selected-options {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            border-radius: 12px;
            text-align: center;
        }

        .selected-option {
            padding: 15px 25px;
            border-radius: 10px;
            background-color: #d26d0a;
            margin-bottom: 10px;
            color: #fff;
            font-size: 18px;
            transition: background-color 0.3s ease;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .selected-option p {
            margin: 0;
            font-weight: bold;
        }

        .view-assignment-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
        }

        .course-container h2 {
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
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            transition: background-color 0.3s;
        }

        th {
            background-color: #000000;
            color: #fff;
        }

        tr:hover {
            background-color: #f5f5f5;
            cursor: pointer;
        }

        /* Specific widths for columns */
        th:nth-child(1), td:nth-child(1) { /* Checkbox column */
            width: 40px;
        }

        th:nth-child(2), td:nth-child(2) { /* No. column */
            width: 50%;
        }

        th:nth-child(3), td:nth-child(3) { /* Folder Name column */
            width: 20%;
        }

        @media screen and (max-width: 600px) {
            table {
                border-collapse: collapse;
                width: 100%;
            }
            th, td {
                padding: 8px;
            }
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
<h2>ASSIGNMENT</h2>
<div class="custom-line"></div>

<div class="container">
    <div class="course-container">
        <h2>Select Course</h2>
        <input type="text" id="searchInput" placeholder="Search course title...">
        <div id="courseGrid" class="course-grid">
            <?php if (count($courses) > 0): ?>
                <?php foreach ($courses as $course): ?>
                    <div class="course-card">
                        <a href="choose_assigment_student.php?assignteacher_id=<?php echo htmlspecialchars($course['AssignteacherID']); ?>">
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

    <div class="assignment-container">
        <div class="selected-options">
            <div class="selected-option">
                <p>Selected Course: <?php echo htmlspecialchars($assigncourseTitle); ?></p>
            </div>
            <div class="selected-option">
                <p>Selected Class: <?php echo htmlspecialchars($assignclassName); ?></p>
            </div>
        </div>
        <div class="assignment-options">
            <a class="assignment-option individual <?php echo $AAselect_id == 0 ? 'disabled' : ''; ?>" 
            href="assignment_student_details.php?assignteacher_id=<?php echo $assignteacher_id; ?>&AAselect_id=0"
            <?php echo $AAselect_id == 0 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                Individual Assignment
            </a>
            <a class="assignment-option group <?php echo $AAselect_id == 1 ? 'disabled' : ''; ?>" 
            href="assignment_student_details.php?assignteacher_id=<?php echo $assignteacher_id; ?>&AAselect_id=1"
            <?php echo $AAselect_id == 1 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                Group Assignment
            </a>
        </div>
    </div>  

    <div class="view-assignment-container">
        <h2>Assignment</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Assignment Name</th>
                        <th>Due Date</th>
                        <th>Date Added</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($learningMaterials) > 0): ?>
                        <?php foreach ($learningMaterials as $index => $material): ?>
                            <?php if ($material['ShowFolder'] == 0): ?>
                                <tr style="cursor: pointer;">
                                    <td onclick="window.location='student_assignment_material_details.php?folder_id=<?php echo $material['AAfolderID']; ?>&student_id=<?php echo $studentID; ?>&assignteacher_id=<?php echo $assignteacher_id; ?>';"><?php echo $index + 1; ?></td>
                                    <td onclick="window.location='student_assignment_material_details.php?folder_id=<?php echo $material['AAfolderID']; ?>&student_id=<?php echo $studentID; ?>&assignteacher_id=<?php echo $assignteacher_id; ?>';"><?php echo htmlspecialchars($material['FolderName']); ?></td>
                                    <td onclick="window.location='student_assignment_material_details.php?folder_id=<?php echo $material['AAfolderID']; ?>&student_id=<?php echo $studentID; ?>&assignteacher_id=<?php echo $assignteacher_id; ?>';"><?php echo htmlspecialchars($material['DueDate']); ?></td>
                                    <td onclick="window.location='student_assignment_material_details.php?folder_id=<?php echo $material['AAfolderID']; ?>&student_id=<?php echo $studentID; ?>&assignteacher_id=<?php echo $assignteacher_id; ?>';"><?php echo htmlspecialchars($material['FolderDate']); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No assignments added yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
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
