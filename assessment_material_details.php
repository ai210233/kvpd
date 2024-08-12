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
$assessmentfolder_id = intval($_GET['assessmentfolder_id']); 

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
$assessmentFileQuery = "SELECT * FROM assessmentfile WHERE AssessmentfolderID = $assessmentfolder_id";
$assessmentFileResult = $conn->query($assessmentFileQuery);

// Check for errors in query execution
if (!$assessmentFileResult) {
    echo "Error: " . $conn->error;
}

$assessmentFiles = [];

// Fetch and store data in $assessmentFiles array
if ($assessmentFileResult->num_rows > 0) {
    while ($fileRow = $assessmentFileResult->fetch_assoc()) {
        $assessmentFiles[] = $fileRow;
    }
}

$editAssessmentFolderQuery = "SELECT * FROM assessmentfolder WHERE AssignteacherID = $assignteacherID AND AssessmentFolderID = $assessmentfolder_id";
$editAssessmentFolderResult = $conn->query($editAssessmentFolderQuery);
$editAssessmentFolder = $editAssessmentFolderResult->fetch_assoc();
$AAselect_id = $editAssessmentFolder['SelectionAA'];

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

if (isset($_POST['editAssessment'])) {
    // Retrieve form data
    $folderName = $_POST['assessmentName'];
    $folderStatus = $_POST['assessmentStatus'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    $assessmentMark = $_POST['assessmentMark'];

    // Update folder details in the database
    $updateFolderQuery = "UPDATE assessmentfolder SET StartDate = '$startDate', EndDate = '$endDate', FolderName = '$folderName', ShowFolder = '$folderStatus', AssessmentMark = '$assessmentMark' WHERE AssessmentfolderID = $assessmentfolder_id";

    if ($conn->query($updateFolderQuery) === TRUE) {
        // Redirect or perform any other action after successful update
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
}


// Check if form is submitted for deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selectedRows'])) {
    // Get selected folder IDs
    $selectedRows = $_POST['selectedRows'];

    // Create a placeholder string for the prepared statement
    $placeholders = implode(',', array_fill(0, count($selectedRows), '?'));

    // Prepare and bind parameters
    $deleteQuery = "DELETE FROM assessmentfile WHERE AssessmentID IN ($placeholders)";
    $stmt = $conn->prepare($deleteQuery);

    // Dynamically bind parameters to the statement
    $stmt->bind_param(str_repeat('i', count($selectedRows)), ...$selectedRows);

    // Execute the statement
    if ($stmt->execute()) {
        
    } else {
        echo "Error deleting files: " . $conn->error;
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
            margin-bottom: 30px;
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

        .table-container th:nth-child(1),
        .table-container td:nth-child(1) {
            width: 58%; /* Set width for the first column */
        }

        .table-container th:nth-child(2),
        .table-container td:nth-child(2) {
            width: 12%; /* Set width for the second column */
        }
        .table-container th:nth-child(3),
        .table-container td:nth-child(3) {
            width: 12%; /* Set width for the second column */
        }
        .assessment-table-container th:nth-child(1),
        .assessment-table-container td:nth-child(1) {
            width: 10px; /* Set width for the first column */
        }
        .assessment-table-container th:nth-child(2),
        .assessment-table-container td:nth-child(2) {
            width: 5%; /* Set width for the second column */
        }
        .assessment-table-container th:nth-child(3),
        .assessment-table-container td:nth-child(3) {
            width: 60%; /* Set width for the second column */
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

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        input[type="text"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        /* Style for Start Date input */
        #startDate {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        /* Style for End Date input */
        #endDate {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        /* Style for assessment file upload input */
        #assessmentFiles {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 10px;
            box-sizing: border-box;
            background-color: #f9f9f9;
        }

        /* Style for the selected assessment file names display */
        #assessmentFileNamesDisplay {
            margin-top: 5px;
            font-size: 14px;
            color: #666;
        }

        /* Style for the AssessmentMark input */
        #assessmentMark {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        /* Style for the Save button */
        .btn-primary {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #0056b3;
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
    <script>
        function toggleEditForm() {
            var editForm = document.getElementById('editForm');
            var editButton = document.getElementById('editButton');
            
            if (editForm.style.display === 'none') {
                editForm.style.display = 'block';
                editButton.textContent = 'Cancel';
            } else {
                editForm.style.display = 'none';
                editButton.textContent = 'Edit';
            }
        }
    </script>
</head>
<body>
<?php include 'include/teacherheader.php'; ?>
<h2>MANAGE ASSESSMENT</h2>

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

        <div class="action-buttons">
            <button onclick="window.location='assessment_details.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=<?php echo $AAselect_id; ?>';">Back</button>
            <button id="editButton" onclick="toggleEditForm()">Edit</button>
            <button onclick="window.location='view_submission_attachment.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=<?php echo $AAselect_id; ?>&assessmentfolder_id=<?php echo $assessmentfolder_id; ?>';">View Submission</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Assessment Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Mark</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($editAssessmentFolder['FolderName']); ?></td>
                        <td ><?php echo htmlspecialchars($editAssessmentFolder['StartDate']); ?></td>
                        <td><?php echo htmlspecialchars($editAssessmentFolder['EndDate']); ?></td>
                        <td><?php echo htmlspecialchars($editAssessmentFolder['ShowFolder'] == 0) ? 'Unlocked' : 'Locked'; ?></td>
                        <td><?php echo htmlspecialchars($editAssessmentFolder['AssessmentMark']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="editForm" style="display: none;">
            <form method="post" action="">
                <div class="form-group">
                    <label for="assessmentName">Assessment Name:</label>
                    <input type="text" id="assessmentName" name="assessmentName" value="<?php echo htmlspecialchars($editAssessmentFolder['FolderName']); ?>" placeholder="Enter assessment name" required>
                </div>
                <div class="form-group">
                    <label for="startDate">Start Date:</label>
                    <input type="datetime-local" id="startDate" name="startDate" value="<?php echo htmlspecialchars($editAssessmentFolder['StartDate']); ?>">
                </div>
                <div class="form-group">
                    <label for="endDate">End Date:</label>
                    <input type="datetime-local" id="endDate" name="endDate" value="<?php echo htmlspecialchars($editAssessmentFolder['EndDate']); ?>">
                </div>
                <div class="form-group">
                    <label for="assessmentStatus">Status:</label>
                    <select id="assessmentStatus" name="assessmentStatus" required>
                        <option value="0" <?php if ($editAssessmentFolder['ShowFolder'] == 0) echo 'selected'; ?>>Show Assessment</option>
                        <option value="1" <?php if ($editAssessmentFolder['ShowFolder'] == 1) echo 'selected'; ?>>Hide Assessment</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="assessmentMark">Mark:</label>
                    <input type="number" id="assessmentMark" name="assessmentMark" value="<?php echo htmlspecialchars($editAssessmentFolder['AssessmentMark']); ?>" placeholder="Enter mark" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="editAssessment" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>


        <form method="POST" id="deleteForm">
            <!-- Buttons for actions -->
            <div class="action-buttons">
                <button type="button" onclick="window.location='add_file_assessment.php?assignteacher_id=<?php echo $assignteacherID; ?>&assessmentfolder_id=<?php echo $assessmentfolder_id; ?>&AAselect_id=<?php echo $AAselect_id; ?>';">Add File</button>
                <button type="submit" class="delete" id="deleteButton" disabled>Delete</button>
            </div>

            <!-- Table for displaying folders -->
            <div class="assessment-table-container">
                <table>
                    <thead>
                        <tr>
                            <th></th>
                            <th>No.</th>
                            <th>File Name</th>
                            <th>File Size</th>
                            <th>File Type</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($assessmentFiles) > 0): ?>
                            <?php foreach ($assessmentFiles as $index => $file): ?>
                                <tr style="cursor: pointer;">
                                    <td>
                                        <input type="checkbox" name="selectedRows[]" value="<?php echo htmlspecialchars($file['AssessmentID']); ?>" onclick="toggleDeleteButton()">
                                    </td>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($file['FileName']); ?></td>
                                    <td><?php echo htmlspecialchars($file['FileSize']); ?></td>
                                    <td><?php echo htmlspecialchars($file['FileType']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No assessment file added yet.</td>
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

    // Function to display selected file names
    document.getElementById('files').addEventListener('change', function(e) {
        const fileList = e.target.files;
        const fileNames = Array.from(fileList).map(file => file.name);
        const fileNamesDisplay = document.getElementById('fileNamesDisplay');
        fileNamesDisplay.textContent = fileNames.join(', ');
    });
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
