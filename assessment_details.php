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

// Query to get learning materials
$learningMaterialQuery = "SELECT * FROM assessmentfolder WHERE AssignteacherID = $assignteacherID AND SelectionAA = $AAselect_id";
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

// Retrieve the course details for the selected assignteacher_id
$selectedCourseQuery = "
    SELECT course.CourseTitle, class.ClassName 
    FROM assignteacher 
    JOIN course ON assignteacher.CourseID = course.CourseID 
    JOIN class ON assignteacher.ClassID = class.ClassID 
    WHERE assignteacher.AssignteacherID = $assignteacherID AND assignteacher.TeacherID = $teacherID";

$selectedCourseResult = $conn->query($selectedCourseQuery);

if ($selectedCourseResult->num_rows == 1) {
    $selectedCourseRow = $selectedCourseResult->fetch_assoc();
    $selectedCourseTitle = $selectedCourseRow['CourseTitle'];
    $selectedClassName = $selectedCourseRow['ClassName'];
} else {
    // Handle error if course details not found
    $selectedCourseTitle = "Unknown";
    $selectedClassName = "Unknown";
}

// Check if form is submitted for deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selectedRows'])) {
    // Get selected folder IDs
    $selectedRows = $_POST['selectedRows'];

    // Create a placeholder string for the prepared statement
    $placeholders = implode(',', array_fill(0, count($selectedRows), '?'));

    // Prepare and bind parameters
    $deleteQuery = "DELETE FROM assessmentfolder WHERE AssessmentfolderID IN ($placeholders)";
    $stmt = $conn->prepare($deleteQuery);

    // Dynamically bind parameters to the statement
    $stmt->bind_param(str_repeat('i', count($selectedRows)), ...$selectedRows);

    // Execute the statement
    if ($stmt->execute()) {
        
    } else {
        echo "Error deleting folders: " . $conn->error;
    }

    // Close the statement
    $stmt->close();

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
        .assessment-container {
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
            margin-top: 30px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .assessment-options {
            display: flex;
            gap: 20px;
            justify-content: center;
            width: 100%;
        }

        .assessment-option {
            padding: 15px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            color: #fff;
            transition: background-color 0.3s ease, transform 0.3s;
            flex: 1;
            text-align: center;
        }

        .quiz {
            background-color: #000000;
        }

        .test {
            background-color: #000000;
        }

        .assessment-option:hover {
            background-color: #969494;
        }

        .assessment-option:active {
            transform: translateY(2px);
        }

        .learn-container h2{
            margin-bottom: 20px;
        }
        .learn-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px,1fr));
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

        .assessment-folder-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
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

        .action-buttons button.add {
            background-color: #4CAF50; /* Green */
            color: white;
        }

        .action-buttons button.delete {
            background-color: #f44336; /* Red */
            color: white;
        }

        .action-buttons button.add:hover {
            background-color: #45a049; /* Darker Green */
        }

        .action-buttons button.select:hover {
            background-color: #007bb5; /* Darker Blue */
        }

        .action-buttons button.delete:hover {
            background-color: #e53935; /* Darker Red */
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

        /* Specific widths for columns */
        th:nth-child(1), td:nth-child(1) { /* Checkbox column */
            width: 40px;
        }

        th:nth-child(2), td:nth-child(2) { /* No. column */
            width: 60px;
        }

        th:nth-child(3), td:nth-child(3) { /* Folder Name column */
            width: 40%;
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

        /* Additional CSS for checkboxes */
        .checkbox-container {
            display: flex;
            align-items: center;
        }

        .checkbox-container input[type="checkbox"] {
            margin-right: 10px;
        }

        /* Additional CSS for the delete button */
        .action-buttons button.delete[disabled] {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .assessment-folder-container {
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
        
    </style>
</head>
<body>
<?php include 'include/teacherheader.php'; ?>
<h2>MANAGE ASSESSMENTS</h2>

<div class="custom-line"></div>

<div class="container">
    <div class="learn-container">
        <h2>Select Course</h2>
        <input type="text" id="searchInput" placeholder="Search course title...">
        <div id="learnGrid" class="learn-grid">
            <?php if (count($courses) > 0): ?>
                <?php foreach ($courses as $course): ?>
                    <div class="learn-card">
                        <a href="choose_assessment.php?assignteacher_id=<?php echo htmlspecialchars($course['AssignteacherID']); ?>">
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

    <div class="assessment-container">
        <div class="selected-options">
            <div class="selected-option">
                <p>Selected Course: <?php echo htmlspecialchars($selectedCourseTitle); ?></p>
            </div>
            <div class="selected-option">
                <p>Selected Class: <?php echo htmlspecialchars($selectedClassName); ?></p>
            </div>
        </div>
        <div class="assessment-options">
            <a class="assessment-option quiz <?php echo $AAselect_id == 2 ? 'disabled' : ''; ?>" 
            href="assessment_details.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=2"
            <?php echo $AAselect_id == 2 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                Quiz
            </a>
            <a class="assessment-option test <?php echo $AAselect_id == 3 ? 'disabled' : ''; ?>" 
            href="assessment_details.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=3"
            <?php echo $AAselect_id == 3 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                Test
            </a>
        </div>
    </div> 
    
    <div class="assessment-folder-container">
        <h2>Assessment</h2>
        <form method="POST" id="deleteForm">
            <!-- Buttons for actions -->
            <div class="action-buttons">
                <button type="button" onclick="window.location='add_folder_assessment.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=<?php echo $AAselect_id; ?>';">Add Assessment</button>
                <button type="submit" class="delete" id="deleteButton" disabled>Delete</button>
            </div>

            <!-- Table for displaying folders -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th></th>
                            <th>No.</th>
                            <th>Assessment Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Date Added</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($learningMaterials) > 0): ?>
                            <?php foreach ($learningMaterials as $index => $material): ?>
                                <?php
                                    // Determine the URL based on AssessmentType
                                    $detailsPage = $material['AssessmentType'] == 0 ? 'assessment_question_details.php' : 'assessment_material_details.php';
                                ?>
                                <tr style="cursor: pointer;">
                                    <!-- Checkbox for each row -->
                                    <td>
                                        <input type="checkbox" name="selectedRows[]" value="<?php echo htmlspecialchars($material['AssessmentfolderID']); ?>" onclick="toggleDeleteButton()">
                                    </td>
                                    <td onclick="window.location='<?php echo $detailsPage; ?>?assessmentfolder_id=<?php echo $material['AssessmentfolderID']; ?>&assignteacher_id=<?php echo $assignteacherID; ?>';"><?php echo $index + 1; ?></td>
                                    <td onclick="window.location='<?php echo $detailsPage; ?>?assessmentfolder_id=<?php echo $material['AssessmentfolderID']; ?>&assignteacher_id=<?php echo $assignteacherID; ?>';"><?php echo htmlspecialchars($material['FolderName']); ?></td>
                                    <td onclick="window.location='<?php echo $detailsPage; ?>?assessmentfolder_id=<?php echo $material['AssessmentfolderID']; ?>&assignteacher_id=<?php echo $assignteacherID; ?>';"><?php echo htmlspecialchars($material['StartDate']); ?></td>
                                    <td onclick="window.location='<?php echo $detailsPage; ?>?assessmentfolder_id=<?php echo $material['AssessmentfolderID']; ?>&assignteacher_id=<?php echo $assignteacherID; ?>';"><?php echo htmlspecialchars($material['EndDate']); ?></td>
                                    <td onclick="window.location='<?php echo $detailsPage; ?>?assessmentfolder_id=<?php echo $material['AssessmentfolderID']; ?>&assignteacher_id=<?php echo $assignteacherID; ?>';"><?php echo htmlspecialchars($material['FolderDate']); ?></td>
                                    <td onclick="window.location='<?php echo $detailsPage; ?>?assessmentfolder_id=<?php echo $material['AssessmentfolderID']; ?>&assignteacher_id=<?php echo $assignteacherID; ?>';"><?php echo htmlspecialchars($material['ShowFolder'] == 0) ? 'Unlocked' : 'Locked'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No assessment added yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<?php include 'include/footer.php'; ?>

<script>
    // Function to enable/disable delete button based on checkbox selection
    function toggleDeleteButton() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        const deleteButton = document.getElementById('deleteButton');
        let atLeastOneChecked = false;

        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                atLeastOneChecked = true;
                return;
            }
        });

        deleteButton.disabled = !atLeastOneChecked;
    }

    // Add event listener to checkboxes
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', toggleDeleteButton);
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