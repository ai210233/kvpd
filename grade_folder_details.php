<?php
session_start();

// Check if TeacherID is set in the session
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

// Get TeacherID from session
$teacherID = $_SESSION['user_id'];

$assignteacherID = intval($_GET['assignteacher_id']); 
$AAselect_id = intval($_GET['AAselect_id']); 
$folder_id = intval($_GET['folder_id']); 

// Query the database to get TeacherName and TeacherImage
$query = "SELECT TeacherName, TeacherImage FROM teacher WHERE TeacherID = $teacherID";
$result = $conn->query($query);

if ($result->num_rows == 1) {
    // Fetch TeacherName and TeacherImage
    $row = $result->fetch_assoc();
    $teacherName = $row['TeacherName'];
    $teacherImage = $row['TeacherImage'];
} else {
    // Handle error if Teacher details not found
    $teacherName = "Unknown";
    $teacherImage = "default_teacher_image.jpg"; // Replace with a default image path
}

// Query to get courses assigned to the teacher with ClassName and CourseTitle
$courseQuery = "
    SELECT assignteacher.AssignteacherID, course.CourseID, course.CourseTitle, class.ClassID, class.ClassName 
    FROM assignteacher 
    JOIN course ON assignteacher.CourseID = course.CourseID 
    JOIN class ON assignteacher.ClassID = class.ClassID 
    WHERE assignteacher.TeacherID = $teacherID";
$courseResult = $conn->query($courseQuery);

$courses = [];
if ($courseResult->num_rows > 0) {
    while ($courseRow = $courseResult->fetch_assoc()) {
        $courses[] = $courseRow;
    }
}

// Retrieve the course details for the selected assignteacher_id
$selectedCourseQuery = "
    SELECT course.CourseTitle, class.ClassName, class.ClassID 
    FROM assignteacher 
    JOIN course ON assignteacher.CourseID = course.CourseID 
    JOIN class ON assignteacher.ClassID = class.ClassID 
    WHERE assignteacher.AssignteacherID = $assignteacherID AND assignteacher.TeacherID = $teacherID";

$selectedCourseResult = $conn->query($selectedCourseQuery);

if ($selectedCourseResult->num_rows == 1) {
    $selectedCourseRow = $selectedCourseResult->fetch_assoc();
    $selectedCourseTitle = $selectedCourseRow['CourseTitle'];
    $selectedClassName = $selectedCourseRow['ClassName'];
    $selectedClassID = $selectedCourseRow['ClassID'];
} else {
    // Handle error if course details not found
    $selectedCourseTitle = "Unknown";
    $selectedClassName = "Unknown";
    $selectedClassID = 0;
}

if($AAselect_id == 1 || $AAselect_id == 0){
    // Query to get student details based on ClassID
    $studentQuery = "
    SELECT student.StudentMatricNum, student.StudentName, student.StudentID, 
    mark.Marks
    FROM student 
    LEFT JOIN mark ON student.StudentID = mark.StudentID AND mark.AAfolderID = $folder_id
    WHERE student.ClassID = $selectedClassID";
    $studentResult = $conn->query($studentQuery);

    $students = [];
    if ($studentResult->num_rows > 0) {
        while ($studentRow = $studentResult->fetch_assoc()) {
            $students[] = $studentRow;
        }
    }

    // Query to get learning materials
    $learningMaterialQuery = "SELECT * FROM aafolder WHERE AssignteacherID = $assignteacherID AND SelectionAA = $AAselect_id AND AAfolderID = $folder_id";
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

}
else{
    // Query to get student details based on ClassID
    $studentQuery = "
    SELECT student.StudentMatricNum, student.StudentName, student.StudentID, 
    mark.Marks
    FROM student 
    LEFT JOIN mark ON student.StudentID = mark.StudentID AND mark.AssessmentfolderID = $folder_id
    WHERE student.ClassID = $selectedClassID";
    $studentResult = $conn->query($studentQuery);

    $students = [];
    if ($studentResult->num_rows > 0) {
    while ($studentRow = $studentResult->fetch_assoc()) {
        $students[] = $studentRow;
    }
    }
    // Query to get learning materials
    $learningMaterialQuery = "SELECT * FROM assessmentfolder WHERE AssignteacherID = $assignteacherID AND SelectionAA = $AAselect_id AND AssessmentfolderID = $folder_id";
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
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="style/teacher/header.css">
    <link rel="stylesheet" href="style/teacher/learn.css">
    <script src="script/admin/navbar.js"></script>
    <style>
        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .learn-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .grade-container {
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
            margin-top: 30px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .grade-options {
            display: flex;
            gap: 20px;
            justify-content: center;
            width: 100%;
        }

        .grade-option {
            padding: 15px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            color: #fff;
            transition: background-color 0.3s ease, transform 0.3s;
            flex: 1;
            text-align: center;
        }

        .grade {
            margin-top: 10px;
            background-color: #006af1;
        }

        .skm {
            margin-top: 10px;
            background-color: #01b940;
        }

        .quiz {
            background-color: #000000;
        }

        .test {
            background-color: #000000;
        }

        .individu {
            background-color: #000000;
        }

        .group {
            background-color: #000000;
        }

        .grade-option:hover {
            background-color: #969494;
        }

        .grade-option:active {
            transform: translateY(2px);
        }

        .learn-container h2{
            margin-bottom: 20px;
        }
        .learn-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            justify-items: center;
        }
        .learn-card {
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
        }
        .learn-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .learn-card img {
            width: 100%;
            height: auto;
            border-bottom: 1px solid #ccc;
        }
        .learn-card .content {
            padding: 15px;
        }
        .learn-card h4 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .learn-card p {
            margin: 0;
            color: #666;
        }
        .add-learn-card {
            border: 2px dashed #aaa;
            background-color: #d1ffd6;
            cursor: pointer;
        }
        .learn-card a {
            text-decoration: none; /* Remove underline */
            color: #333;
        }

        .grade-folder-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
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

        .table-container {
            margin-top: 30px;
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
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #f5f5f5;
            cursor: pointer;
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
        .action-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .action-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

    </style>
</head>
<body>
<?php include 'include/teacherheader.php'; ?>
<h2>MANAGE GRADE</h2>

<div class="custom-line"></div>

<div class="container">
    <div class="learn-container">
        <h2>Select Course</h2>
        <input type="text" id="searchInput" placeholder="Search course title...">
        <div id="learnGrid" class="learn-grid">
            <?php if (count($courses) > 0): ?>
                <?php foreach ($courses as $course): ?>
                    <div class="learn-card">
                        <a href="choose_grade.php?assignteacher_id=<?php echo htmlspecialchars($course['AssignteacherID']); ?>">
                            <img src="image/2.jpg" alt="Course Image">
                            <div class="content">
                                    <h4><?php echo htmlspecialchars($course['CourseTitle']); ?></h4>
                                    <p><?php echo htmlspecialchars($course['ClassName']); ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No courses assigned yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="grade-container">
        <div class="selected-options">
            <div class="selected-option">
                <p>Selected Course: <?php echo htmlspecialchars($selectedCourseTitle); ?></p>
            </div>
            <div class="selected-option">
                <p>Selected Class: <?php echo htmlspecialchars($selectedClassName); ?></p>
            </div>
        </div>
        <div class="grade-options">
                <a class="grade-option individu <?php echo $AAselect_id == 0 ? 'disabled' : ''; ?>" 
                href="grade_details.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=0"
                <?php echo $AAselect_id == 0 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                    Individual Assignment
                </a>
                <a class="grade-option group <?php echo $AAselect_id == 1 ? 'disabled' : ''; ?>" 
                href="grade_details.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=1"
                <?php echo $AAselect_id == 1 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                    Group Assignment
                </a>
                <a class="grade-option quiz <?php echo $AAselect_id == 2 ? 'disabled' : ''; ?>" 
                href="grade_details.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=2"
                <?php echo $AAselect_id == 2 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                    Quiz
                </a>
                <a class="grade-option test <?php echo $AAselect_id == 3 ? 'disabled' : ''; ?>" 
                href="grade_details.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=3"
                <?php echo $AAselect_id == 3 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                    Test
                </a>
        </div>
        <div class="grade-options">
                <a class="grade-option grade <?php echo $AAselect_id == 4 ? 'disabled' : ''; ?>" 
                href="grade_overall.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=4"
                <?php echo $AAselect_id == 4 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                    Overall Grade
                </a>
        </div>
        <div class="grade-options">
                <a class="grade-option skm <?php echo $AAselect_id == 5 ? 'disabled' : ''; ?>" 
                href="manage_skm.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=5"
                <?php echo $AAselect_id == 5 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                    Manage SKM
                </a>
        </div>
    </div> 
    
    <div class="grade-folder-container">
        <h2>Grade</h2>
        <div class="action-buttons">
            <button onclick="window.location='grade_details.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=<?php echo $AAselect_id; ?>';">Back</button>
        </div>
        <?php if($AAselect_id == 1 || $AAselect_id == 0): ?>
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
                                <?php
                                    $detailsfolderid_assignment =  $material['AAfolderID'];
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($material['FolderName']); ?></td>
                                    <td><?php echo htmlspecialchars($material['DueDate']); ?></td>
                                    <td><?php echo htmlspecialchars($material['FolderDate']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No assignment added yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Assessment Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($learningMaterials) > 0): ?>
                            <?php foreach ($learningMaterials as $index => $material): ?>
                                <?php
                                    $detailsfolderid_assessment =  $material['AssessmentfolderID'];
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($material['FolderName']); ?></td>
                                    <td><?php echo htmlspecialchars($material['StartDate']); ?></td>
                                    <td><?php echo htmlspecialchars($material['EndDate']); ?></td>
                                    <td><?php echo htmlspecialchars($material['FolderDate']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No assessment added yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2>Student Mark</h2>
        <table>
            <thead>
                <tr>
                    <th>Student Matric Number</th>
                    <th>Student Name</th>
                    <th>Mark</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $student): ?>
                        <?php
                            if($AAselect_id == 1 || $AAselect_id == 0){
                                $detailsfolderid = $detailsfolderid_assignment;
                            }else{
                                $detailsfolderid = $detailsfolderid_assessment;
                            }
                        ?>
                        <tr onclick="window.location='grade_folder_edit.php?folder_id=<?php echo $detailsfolderid; ?>&assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=<?php echo $AAselect_id; ?>&student_id=<?php echo $student['StudentID']; ?>';">
                            <td><?php echo htmlspecialchars($student['StudentMatricNum']); ?></td>
                            <td><?php echo htmlspecialchars($student['StudentName']); ?></td>
                            <td class="clickable" data-student-id="<?php echo htmlspecialchars($student['StudentID']); ?>" data-select-grade="1"><?php echo htmlspecialchars($student['Marks']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No students found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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
        cardContainer = document.getElementById("learnGrid");
        cards = cardContainer.getElementsByClassName("learn-card");
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

<?php
// Close the connection
$conn->close();
?>
