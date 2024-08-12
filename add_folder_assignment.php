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
    SELECT assignteacher.AssignteacherID, assignteacher.SessionIntake, assignteacher.Semester, course.CourseID, course.CourseTitle, class.ClassID, class.ClassName 
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
$learningMaterialQuery = "SELECT * FROM aafolder WHERE AssignteacherID = $assignteacherID AND SelectionAA = $AAselect_id";
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

if(isset($_POST['addFolder'])) {
    // Retrieve form data
    $folderName = $_POST['folderName'];
    $folderStatus = $_POST['folderStatus'];
    $folderDate = date("Y-m-d H:i:s"); // Current date and time
    $dueDate = $_POST['dueDate']; // New: Retrieve due date from form

    // Insert data into the database
    $insertFolderQuery = "INSERT INTO aafolder (FolderName, FolderDate, ShowFolder, AssignteacherID, SelectionAA, DueDate) 
                          VALUES ('$folderName', '$folderDate', '$folderStatus', $assignteacherID, $AAselect_id, '$dueDate')"; // New: Add DueDate to query

    if ($conn->query($insertFolderQuery) === TRUE) {
        // Get the ID of the inserted folder
        $folderID = $conn->insert_id;

        if (!empty(array_filter($_FILES['files']['name']))) {

            // File upload logic
            $fileCount = count($_FILES['files']['name']); // New: Get the number of files uploaded

            for ($i = 0; $i < $fileCount; $i++) {
                $fileName = $_FILES['files']['name'][$i];
                $fileType = $_FILES['files']['type'][$i];
                $fileSize = $_FILES['files']['size'][$i];
                $fileTmpName = $_FILES['files']['tmp_name'][$i];

                // Move uploaded file to uploads directory
                $uploadPath = 'uploads/' . $fileName;
                move_uploaded_file($fileTmpName, $uploadPath);

                // Insert file details into database
                $insertFileQuery = "INSERT INTO assignmentfile (FileName, FileType, FileSize, FilePath, AAfolderID) 
                                    VALUES ('$fileName', '$fileType', '$fileSize', '$uploadPath', $folderID)";
                $conn->query($insertFileQuery);
            }

        }
        
        // Redirect or perform any other action after successful insertion
    } else {
        // Handle insertion error
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

        .assignment-folder-container {
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
            width: 60px;
        }

        th:nth-child(2), td:nth-child(2) { /* No. column */
            width: 800px;
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
        /* Style for Due Date input */
        #dueDate {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        /* Style for file upload input */
        #files {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 10px;
            box-sizing: border-box;
            background-color: #f9f9f9;
        }

        /* Style for the selected file names display */
        #fileNamesDisplay {
            margin-top: 5px;
            font-size: 14px;
            color: #666;
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
        /* Dropdown styles */
        #sessionDropdown, #semesterDropdown {
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            width: auto;
            box-sizing: border-box;
            background-color: #f9f9f9;
            cursor: pointer;
            transition: border 0.3s ease;
        }

        #sessionDropdown:hover, #semesterDropdown:hover {
            border-color: #aaa;
        }

        /* Ensuring dropdown aligns properly with the search input */
        #searchInput, #sessionDropdown, #semesterDropdown {
            margin-right: 10px;
        }
    </style>
</head>
<body>
<?php include 'include/teacherheader.php'; ?>
<h2>MANAGE ASSIGNMENT</h2>

<div class="custom-line"></div>

<div class="container">
    <div class="learn-container">
        <h2>Select Course</h2>
        <input type="text" id="searchInput" placeholder="Search course title...">
        <div>
            <select id="sessionDropdown">
                <option value="" selected disabled>Select Session</option>
                <option value="2023">2023</option>
                <option value="2024">2024</option>
            </select>
            <select id="semesterDropdown">
                <option value="" selected disabled>Select Semester</option>
                <option value="1">1</option>
                <option value="2">2</option>
            </select>
        </div>
        <div id="learnGrid" class="learn-grid">
            <?php if (count($courses) > 0): ?>
                <?php foreach ($courses as $course): ?>
                    <div class="learn-card" data-session="<?php echo htmlspecialchars($course['SessionIntake']); ?>" data-semester="<?php echo htmlspecialchars($course['Semester']); ?>">
                        <a href="choose_assignment.php?assignteacher_id=<?php echo htmlspecialchars($course['AssignteacherID']); ?>">
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

    <div class="assignment-container">
        <div class="selected-options">
            <div class="selected-option">
                <p>Selected Course: <?php echo htmlspecialchars($selectedCourseTitle); ?></p>
            </div>
            <div class="selected-option">
                <p>Selected Class: <?php echo htmlspecialchars($selectedClassName); ?></p>
            </div>
        </div>
        <div class="assignment-options">
            <a class="assignment-option individual <?php echo $AAselect_id == 0 ? 'disabled' : ''; ?>" 
            href="assignment_details.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=0"
            <?php echo $AAselect_id == 0 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                Individual Assignment
            </a>
            <a class="assignment-option group <?php echo $AAselect_id == 1 ? 'disabled' : ''; ?>" 
            href="assignment_details.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=1"
            <?php echo $AAselect_id == 1 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                Group Assignment
            </a>
        </div>
    </div> 
    
    <div class="assignment-folder-container">
        <h2>Assignment</h2>

        <!-- Buttons for actions -->
        <div class="action-buttons">
            <button onclick="window.location='assignment_details.php?assignteacher_id=<?php echo $assignteacherID; ?>&AAselect_id=<?php echo $AAselect_id; ?>';">Back</button>
        </div>

        <div class="action-buttons">
            <form method="post" action="" enctype="multipart/form-data"> <!-- New: Added enctype for file uploads -->
                <div class="form-group">
                    <label for="folderName">Assignment Name:</label>
                    <input type="text" id="folderName" name="folderName" placeholder="Enter assignment name" required>
                </div>
                <div class="form-group">
                    <label for="folderStatus">Status:</label>
                    <select id="folderStatus" name="folderStatus" required>
                        <option value="0">Show Assignment</option>
                        <option value="1">Hide Assignment</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="dueDate">Due Date:</label>
                    <input type="datetime-local" id="dueDate" name="dueDate">
                </div>

                <div class="form-group">
                    <label for="files">Upload Files:</label>
                    <input type="file" id="files" name="files[]" multiple>
                    <div id="fileNamesDisplay"></div> <!-- Display selected file names here -->
                </div>

                <div class="form-group">
                    <button type="submit" id="addFolderButton" name="addFolder">Add Assignment</button>
                </div>
            </form>
        </div>

        <!-- Table for displaying folders -->
        <div class="table-container">
        <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Assignment Name</th>
                        <th>Due Date</th>
                        <th>Date Added</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($learningMaterials) > 0): ?>
                        <?php foreach ($learningMaterials as $index => $material): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($material['FolderName']); ?></td>
                                <td><?php echo htmlspecialchars($material['DueDate']); ?></td>
                                <td><?php echo htmlspecialchars($material['FolderDate']); ?></td>
                                <td><?php echo ($material['ShowFolder'] == 0) ? 'Unlocked' : 'Locked'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No assignment added yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>

<script>

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
        var sessionDropdown = document.getElementById("sessionDropdown");
        var semesterDropdown = document.getElementById("semesterDropdown");

        input.addEventListener("input", function() {
            searchCourses();
        });

        sessionDropdown.addEventListener("change", function() {
            searchCourses();
        });

        semesterDropdown.addEventListener("change", function() {
            searchCourses();
        });
    });

    function searchCourses() {
        var input, filter, cards, cardContainer, title, i;
        input = document.getElementById("searchInput");
        filter = input.value.toUpperCase();
        cardContainer = document.getElementById("learnGrid");
        cards = cardContainer.getElementsByClassName("learn-card");

        var selectedSession = document.getElementById("sessionDropdown").value;
        var selectedSemester = document.getElementById("semesterDropdown").value;

        for (i = 0; i < cards.length; i++) {
            title = cards[i].querySelector(".content h4");
            var session = cards[i].getAttribute("data-session");
            var semester = cards[i].getAttribute("data-semester");

            var matchesSearch = title.innerText.toUpperCase().includes(filter);
            var matchesSession = selectedSession === "" || session === selectedSession;
            var matchesSemester = selectedSemester === "" || semester === selectedSemester;

            if (matchesSearch && matchesSession && matchesSemester) {
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