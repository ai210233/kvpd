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
$student_id = intval($_GET['student_id']); 
$selectgrade = intval($_GET['selectgrade']); 

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

// Query to get student details based on ClassID
$studentQuery = "
    SELECT student.StudentMatricNum, student.StudentName, student.StudentID, 
    overallgrade.AssignmentMark, overallgrade.QuizMark, overallgrade.TestMark, overallgrade.FinalMark
    FROM student 
    LEFT JOIN overallgrade ON student.StudentID = overallgrade.StudentID AND overallgrade.AssignteacherID = $assignteacherID
    WHERE student.ClassID = $selectedClassID" . ($student_id ? " AND student.StudentID = $student_id" : "");
$studentResult = $conn->query($studentQuery);

$students = [];
if ($studentResult->num_rows > 0) {
    while ($studentRow = $studentResult->fetch_assoc()) {
        $students[] = $studentRow;
    }
}

// Check if the form is submitted to update the grade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_grade'])) {
    $gradeMark = intval($_POST['grade_mark']);
    $student_id = intval($_POST['student_id']);
    $assignteacherID = intval($_POST['assignteacher_id']);
    $selectgrade = intval($_POST['selectgrade']);

    // Determine the column to update based on selectgrade
    $column = '';
    switch ($selectgrade) {
        case 1:
            $column = 'AssignmentMark';
            $maxMark = 40;
            break;
        case 2:
            $column = 'QuizMark';
            $maxMark = 10;
            break;
        case 3:
            $column = 'TestMark';
            $maxMark = 20;
            break;
        case 4:
            $column = 'FinalMark';
            $maxMark = 30;
            break;
    }

    if ($gradeMark >= 0 && $gradeMark <= $maxMark) {
        // Check if the entry exists
        $checkQuery = "
            SELECT * FROM overallgrade 
            WHERE StudentID = $student_id AND AssignteacherID = $assignteacherID";
        $checkResult = $conn->query($checkQuery);

        if ($checkResult->num_rows > 0) {
            // Update the existing entry
            $updateQuery = "
                UPDATE overallgrade 
                SET $column = $gradeMark 
                WHERE StudentID = $student_id AND AssignteacherID = $assignteacherID";
        } else {
            // Insert a new entry
            $updateQuery = "
                INSERT INTO overallgrade (StudentID, AssignteacherID, $column) 
                VALUES ($student_id, $assignteacherID, $gradeMark)";
        }

        if ($conn->query($updateQuery) === TRUE) {
            echo "Grade updated successfully";
        } else {
            echo "Error updating grade: " . $conn->error;
        }
    } else {
        echo "Invalid grade mark. Must be between 0 and $maxMark.";
    }

    // Redirect to the same page to refresh
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
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

        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ccc;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
        .clickable {
            cursor: pointer;
        }
        .clickable:hover {
            background-color: lightgray;
        }

        .edit-grade-container {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .edit-grade-container label {
            display: block;
            margin-bottom: 5px;
            font-size: large;
        }

        .edit-grade-container input {
            width: 98%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .edit-grade-container button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            background-color: #007BFF;
            color: #fff;
            transition: background-color 0.3s ease;
        }

        .edit-grade-container button:hover {
            background-color: #0056b3;
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
                href="grade_details.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=4"
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
        <h2>Edit Grade</h2>
        <div class="action-buttons">
            <button onclick="window.location='grade_overall.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=<?php echo $AAselect_id; ?>';">Back</button>
        </div>
        <input type="hidden" id="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
        <table>
            <thead>
                <tr>
                    <th>Student Matric Number</th>
                    <th>Student Name</th>
                    <th>Assignment</th>
                    <th>Quiz</th>
                    <th>Test</th>
                    <th>Final</th>
                    <th>Total</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['StudentMatricNum']); ?></td>
                            <td><?php echo htmlspecialchars($student['StudentName']); ?></td>
                            <td class="clickable" data-student-id="<?php echo htmlspecialchars($student['StudentID']); ?>" data-select-grade="1"><?php echo htmlspecialchars($student['AssignmentMark']); ?></td>
                            <td class="clickable" data-student-id="<?php echo htmlspecialchars($student['StudentID']); ?>" data-select-grade="2"><?php echo htmlspecialchars($student['QuizMark']); ?></td>
                            <td class="clickable" data-student-id="<?php echo htmlspecialchars($student['StudentID']); ?>" data-select-grade="3"><?php echo htmlspecialchars($student['TestMark']); ?></td>
                            <td class="clickable" data-student-id="<?php echo htmlspecialchars($student['StudentID']); ?>" data-select-grade="4"><?php echo htmlspecialchars($student['FinalMark']); ?></td>
                            <td>
                                <?php
                                if (isset($student['AssignmentMark']) && isset($student['QuizMark']) && isset($student['TestMark']) && isset($student['FinalMark'])) {
                                    $total = $student['AssignmentMark'] + $student['QuizMark'] + $student['TestMark'] + $student['FinalMark'];
                                    echo htmlspecialchars($total);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if (isset($student['AssignmentMark']) && isset($student['QuizMark']) && isset($student['TestMark']) && isset($student['FinalMark'])) {
                                    if ($total >= 80) {
                                        $grade = 'A';
                                    } elseif ($total >= 70) {
                                        $grade = 'B';
                                    } elseif ($total >= 60) {
                                        $grade = 'C';
                                    } elseif ($total >= 50) {
                                        $grade = 'D';
                                    } else {
                                        $grade = 'F';
                                    }
                                    echo htmlspecialchars($grade);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No students found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($selectgrade >= 1 && $selectgrade <= 3): ?>
            <div class="grade-folder-container">
                <h2>Assignment or Assessment Folder and Marks</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Folder Name</th>
                            <th>Mark</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($selectgrade == 1): // If Assignment ?>
                            <?php
                            // Query to fetch assignment folders and marks
                            $folderQuery = "SELECT aafolder.FolderName, mark.Marks 
                                            FROM aafolder 
                                            LEFT JOIN mark ON aafolder.AAfolderID = mark.AAfolderID 
                                            WHERE aafolder.AssignteacherID = $assignteacherID";
                            $folderResult = $conn->query($folderQuery);
                            ?>
                            <?php if ($folderResult->num_rows > 0): ?>
                                <?php while ($folderRow = $folderResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($folderRow['FolderName']); ?></td>
                                        <td><?php echo isset($folderRow['Marks']) ? htmlspecialchars($folderRow['Marks']) : 'NULL'; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2">No folders found.</td>
                                </tr>
                            <?php endif; ?>
                        <?php elseif ($selectgrade == 2 || $selectgrade == 3): // If Quiz or Test ?>
                            <?php
                            // Query to fetch assessment folders and marks
                            $folderQuery = "SELECT assessmentfolder.FolderName, mark.Marks 
                                            FROM assessmentfolder 
                                            LEFT JOIN mark ON assessmentfolder.AssessmentfolderID = mark.AssessmentfolderID 
                                            WHERE assessmentfolder.AssignteacherID = $assignteacherID 
                                            AND assessmentfolder.SelectionAA = $selectgrade";
                            $folderResult = $conn->query($folderQuery);
                            ?>
                            <?php if ($folderResult->num_rows > 0): ?>
                                <?php while ($folderRow = $folderResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($folderRow['FolderName']); ?></td>
                                        <td><?php echo isset($folderRow['Marks']) ? htmlspecialchars($folderRow['Marks']) : 'NULL'; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2">No folders found.</td>
                                </tr>
                            <?php endif; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
            <input type="hidden" name="assignteacher_id" value="<?php echo htmlspecialchars($assignteacherID); ?>">
            <input type="hidden" name="selectgrade" value="<?php echo htmlspecialchars($selectgrade); ?>">
            <div class="edit-grade-container">
                <?php if ($selectgrade == 1): ?>
                    <label for="grade_mark">Edit Assignment Mark (0-40):</label>
                    <input type="number" name="grade_mark" min="0" max="40" required>
                <?php elseif ($selectgrade == 2): ?>
                    <label for="grade_mark">Edit Quiz Mark (0-10):</label>
                    <input type="number" name="grade_mark" min="0" max="10" required>
                <?php elseif ($selectgrade == 3): ?>
                    <label for="grade_mark">Edit Test Mark (0-20):</label>
                    <input type="number" name="grade_mark" min="0" max="20" required>
                <?php elseif ($selectgrade == 4): ?>
                    <label for="grade_mark">Edit Final Mark (0-30):</label>
                    <input type="number" name="grade_mark" min="0" max="30" required>
                <?php endif; ?>
                <button type="submit" name="update_grade">Edit</button>
            </div>
        </form>
    </div>
</div>

<?php include 'include/footer.php'; ?>

<script>

document.addEventListener("DOMContentLoaded", function() {
        var cells = document.querySelectorAll("td.clickable");

        cells.forEach(function(cell) {
            cell.addEventListener("click", function() {
                var studentID = this.getAttribute("data-student-id");
                var selectGrade = this.getAttribute("data-select-grade");
                var assignteacherID = <?php echo json_encode($assignteacherID); ?>;
                var AAselectID = <?php echo json_encode($AAselect_id); ?>;
                
                var url = "overall_grade_student.php?assignteacher_id=" + assignteacherID + "&AAselect_id=" + AAselectID + "&student_id=" + studentID + "&selectgrade=" + selectGrade;
                window.location.href = url;
            });
        });
    });

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
