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
$assessmentfolder_id = intval($_GET['assessmentfolder_id']); 

// Get the $AAselect_id from the assessmentfolder table based on $assessmentfolder_id
$AAselect_id = null; // Initialize $AAselect_id variable

// Query the database to get the SelectionAA value based on $assessmentfolder_id
$selectAAQuery = "SELECT * FROM assessmentfolder WHERE AssessmentfolderID = $assessmentfolder_id";
$selectAAResult = $conn->query($selectAAQuery);

if ($selectAAResult->num_rows == 1) {
    $selectAARow = $selectAAResult->fetch_assoc();
    $AAselect_id = $selectAARow['SelectionAA'];
    $selectFolderName = $selectAARow['FolderName'];
    $selectStartDate = $selectAARow['StartDate'];
    $selectEndDate = $selectAARow['EndDate'];
} else {
    
}

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

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $studentID = $_SESSION['user_id'];
    $assessmentfolderID = intval($_GET['assessmentfolder_id']); // Assuming you're passing assessmentfolder_id through GET

    // Directory where files will be uploaded
    $uploadDir = "uploads/";

    // File details
    $fileName = $_FILES['file']['name'];
    $fileType = $_FILES['file']['type'];
    $fileSize = $_FILES['file']['size'];
    $fileTmpName = $_FILES['file']['tmp_name'];

    // Get file extension
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

    // Move the uploaded file to the uploads directory
    $destination = $uploadDir . $fileName;
    if (move_uploaded_file($fileTmpName, $destination)) {
        // File uploaded successfully, now insert into database
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "kvpd_elearning"; // Replace with your actual database name

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Prepare and execute SQL statement to insert into database
        $insertQuery = "INSERT INTO assessment_submission (StudentID, AssessmentfolderID, FileName, FilePath, FileType, FileSize, DateSubmission) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("iisssd", $studentID, $assessmentfolderID, $fileName, $destination, $fileType, $fileSize);
        
        if ($stmt->execute()) {

        } else {
            // Error in submission
            echo "Error uploading file: " . $conn->error;
        }

        $stmt->close();
    } else {
        // Error in moving file
        echo "Error uploading file.";
    }
}

$selectAssessmentQuery = "SELECT FileName, FilePath, DateSubmission, AssesssubID FROM assessment_submission WHERE StudentID = $studentID AND AssessmentfolderID = $assessmentfolder_id";
$selectAssessmentResult = $conn->query($selectAssessmentQuery);

$assessmentFiles = [];
if ($selectAssessmentResult->num_rows > 0) {
    while ($row = $selectAssessmentResult->fetch_assoc()) {
        $assessmentFiles[] = $row;
    }
}

$AssessmentfileQuery = "SELECT * FROM assessmentfile WHERE AssessmentfolderID = $assessmentfolder_id";
$AssessmentfileResult = $conn->query($AssessmentfileQuery);

$Assessmentfile = [];
if ($AssessmentfileResult->num_rows > 0) {
    while ($row = $AssessmentfileResult->fetch_assoc()) {
        $Assessmentfile[] = $row;
    }
}


// Check if form is submitted for file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_submit'])) {
    $deleteID = intval($_POST['delete_id']);
    
    // Connect to the database
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare and execute SQL statement to delete from the database
    $deleteQuery = "DELETE FROM assessment_submission WHERE AssesssubID = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $deleteID);
    
    if ($stmt->execute()) {
        header("Location: {$_SERVER['PHP_SELF']}?assessmentfolder_id={$assessmentfolder_id}&student_id={$studentID}&assignteacher_id={$assignteacher_id}");
        exit();     
    } else {
        // Error in deletion
        echo "Error deleting file: " . $conn->error;
    }

    $stmt->close();
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

        .view-assessment-container {
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
        .clock {
            font-family: 'Roboto', sans-serif;
            font-size: 36px;
            font-weight: bold;
            color: #333;
            text-align: center;
            margin-top: 20px;
        }

        .clock-text {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .clock-label {
            font-size: 24px;
            color: #666;
            margin-right: 10px;
        }

        .clock-time {
            font-size: 36px;
        }

        .late .clock-time {
            color: red; /* Change color for late submission */
        }

        /* Animation for late submission */
        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0; }
            100% { opacity: 1; }
        }

        .late .clock-time {
            animation: blink 1s infinite;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input[type="file"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input[type="file"]:focus {
            border-color: #007bff;
        }

        .submit-btn {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #0056b3;
        }

        .submit-btn:focus {
            outline: none;
        }
        .submitted-files-container {
            margin-top: 30px;
        }

        .file-list {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
        }

        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding: 10px 0;
        }

        .file-info {
            flex-grow: 1;
        }

        .file-name {
            margin: 0;
            font-weight: bold;
        }

        .file-submission-date {
            margin: 5px 0 0 0;
            color: #666;
        }

        .delete-form {
            margin-left: 10px;
        }

        .delete-btn {
            background-color: #dc3545;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .delete-btn:hover {
            background-color: #c82333;
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
        /* Remove underline from anchor tags */
        a {
            text-decoration: none;
            color: inherit; /* Use the default color */
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
                        <a href="choose_assessment_student.php?assignteacher_id=<?php echo htmlspecialchars($course['AssignteacherID']); ?>">
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

    <div class="assessment-container">
        <div class="selected-options">
            <div class="selected-option">
                <p>Selected Course: <?php echo htmlspecialchars($assigncourseTitle); ?></p>
            </div>
            <div class="selected-option">
                <p>Selected Class: <?php echo htmlspecialchars($assignclassName); ?></p>
            </div>
        </div>
        <div class="assessment-options">
            <a class="assessment-option quiz <?php echo $AAselect_id == 2 ? 'disabled' : ''; ?>" 
            href="assessment_student_details.php?assignteacher_id=<?php echo $assignteacher_id; ?>&AAselect_id=2"
            <?php echo $AAselect_id == 2 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                Quiz
            </a>
            <a class="assessment-option test <?php echo $AAselect_id == 3 ? 'disabled' : ''; ?>" 
            href="assessment_student_details.php?assignteacher_id=<?php echo $assignteacher_id; ?>&AAselect_id=3"
            <?php echo $AAselect_id == 3 ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                Test
            </a>
        </div>
    </div>  

    <div class="view-assessment-container">
        <h2>Assessment</h2>
        <div class="action-buttons">
                <button onclick="window.location='assessment_student_details.php?assignteacher_id=<?php echo $assignteacher_id; ?>&AAselect_id=<?php echo $AAselect_id; ?>&assessmentfolder_id=<?php echo $assessmentfolder_id; ?>';">Back</button>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Assessment Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo 1; ?></td>
                        <td><?php echo $selectFolderName; ?></td>
                        <td><?php echo $selectStartDate; ?></td>
                        <td><?php echo $selectEndDate; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2>File</h2>
        <div class="view-assessment-container">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>File Name</th>
                            <th>File Size</th>
                            <th>File Type</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($Assessmentfile) > 0): ?>
                            <?php foreach ($Assessmentfile as $index => $file): ?>
                                <tr style="cursor: pointer;">
                                    <!-- Checkbox for each row -->
                                    <td><?php echo $index + 1; ?></td>
                                    <td><a href="<?php echo htmlspecialchars($file['FilePath']); ?>" target="_blank"><?php echo htmlspecialchars($file['FileName']); ?></td>
                                    <td><?php echo htmlspecialchars($file['FileSize']); ?></td>
                                    <td><?php echo htmlspecialchars($file['FileType']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No assessment file added yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <h2>Submission Place</h2>
        <div class="clock" id="clock">
            <div class="clock-text">
                <span class="clock-label">END DATE:</span>
                <span class="clock-time" id="clock-time">--:--:--</span>
            </div>
        </div>

        <div class="submitted-files-container">
            <?php if (!empty($assessmentFiles)): ?>
                <h3>Submitted Files:</h3>
                <div class="file-list">
                    <?php foreach ($assessmentFiles as $file): ?>
                        <div class="file-item">
                            <div class="file-info">
                                <p class="file-name">
                                    <a href="<?php echo $file['FilePath']; ?>" download><?php echo $file['FileName']; ?></a>
                                </p>
                                <p class="file-submission-date">Date Submission: <?php echo $file['DateSubmission']; ?></p>
                            </div>
                            <form class="delete-form" action="<?php echo $_SERVER['PHP_SELF']; ?>?assessmentfolder_id=<?php echo $assessmentfolder_id; ?>?student_id=<?php echo $studentID; ?>&assignteacher_id=<?php echo $assignteacher_id; ?>"" method="post">
                                <input type="hidden" name="delete_id" value="<?php echo $file['AssesssubID']; ?>">
                                <button type="submit" class="delete-btn" name="delete_submit">Delete</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No files submitted for this assessment.</p>
            <?php endif; ?>
        </div>

        <div class="submission-assessment-container">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="file" style="margin-top:10px">Choose File:</label>
                    <input type="file" name="file" id="file">
                </div>
                <button type="submit" class="submit-btn" name="submit">Submit Assessment</button>
            </form>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>

<script>
    // Set the end date
    var endDate = new Date("<?php echo $selectEndDate; ?>").getTime();

    // Update theclock every second
    var clockInterval = setInterval(updateClock, 1000);

function updateClock() {
    // Get the current date and time
    var now = new Date().getTime();

    // Calculate the remaining time
    var distance = endDate - now;

    // If distance is negative, add 'late' class
    if (distance < 0) {
        document.getElementById("clock").classList.add('late');
    }

    // If end date is not set or NaN, set endDateText to appropriate message
    var endDateText;
    if (endDate === null || isNaN(endDate)) {
        endDateText = "NO END DATE";
    } else {
        // Calculate days, hours, minutes, and seconds
        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        // Format the remaining time
        var timeString = days + "d " + hours + "h " + minutes + "m " + seconds + "s";
        endDateText = timeString;
    }

    // Display the remaining time or appropriate message
    var clockElement = document.getElementById("clock-time");
    clockElement.innerText = endDateText;
}

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