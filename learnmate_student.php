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
$assignteacher_id = intval($_GET['assignteacherID']); 
$learnfolder_id = intval($_GET['learnfolderID']);

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

// Query to get AssignteacherID, CourseView, and TeacherID based on $course_id and $class_id
$assignTeacherQuery = "SELECT AssignteacherID FROM assignteacher WHERE CourseID = $course_id AND ClassID = $class_id";
$assignTeacherResult = $conn->query($assignTeacherQuery);

if ($assignTeacherResult->num_rows == 1) {
    $assignTeacherRow = $assignTeacherResult->fetch_assoc();
    $assignteacherID = $assignTeacherRow['AssignteacherID'];
}

// Initialize an array to store the learnfolder details
$learnFolders = [];

// Query to get Learnfolder details based on AssignteacherID
if ($assignteacherID !== "") {
    $learnFolderQuery = "SELECT LearnfolderID, FolderDate, FolderName, ShowFolder FROM learnfolder WHERE AssignteacherID = $assignteacherID";
    $learnFolderResult = $conn->query($learnFolderQuery);

    if ($learnFolderResult->num_rows > 0) {
        while ($learnFolderRow = $learnFolderResult->fetch_assoc()) {
            // Only add folders where ShowFolder is 0
            if ($learnFolderRow['ShowFolder'] == 0) {
                $learnFolders[] = $learnFolderRow;
            }
        }
    }
}

$FolderQuery = "SELECT FolderName, FolderDate, ShowFolder FROM learnfolder WHERE LearnfolderID = $learnfolder_id";
$FolderResult = $conn->query($FolderQuery);
$Folder = $FolderResult->fetch_assoc();

// Query to get learning materials
$learningMaterialQuery = "SELECT LearnMateID, LearnMateName, LearnMateFilePath, LearnMateDate FROM learningmaterial WHERE LearnfolderID = $learnfolder_id";
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
        .folder-container {
            margin-top: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            overflow-x: auto;
        }

        .folder-container h2 {
            margin-bottom: 20px;
            color: #333;
            font-size: 24px;
        }

        .folder-container table {
            width: 100%;
            border-collapse: collapse;
            background-color: #f2f2f2;
        }

        .folder-container th,
        .folder-container td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            text-align: left;
        }

        .folder-container th {
            background-color: #000; /* Set table header background color to black */
            color: #f2f2f2; /* Set text color of table header to light grey */
        }

        .folder-container tbody tr:hover {
            background-color: #f5f5f5;
            cursor: pointer;
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
        .table-container th:nth-child(1),
        .table-container td:nth-child(1) {
            width: 10%; /* Set width for the first column */
        }

        .table-container th:nth-child(2),
        .table-container td:nth-child(2) {
            width: 75%; /* Set width for the second column */
        }
        .learn-table-container th:nth-child(1),
        .learn-table-container td:nth-child(1) {
            width: 30px; /* Set width for the first column */
        }
        .learn-table-container th:nth-child(2),
        .learn-table-container td:nth-child(2) {
            width: 10%; /* Set width for the second column */
        }
        .learn-table-container th:nth-child(3),
        .learn-table-container td:nth-child(3) {
            width: 70%; /* Set width for the second column */
        }

        /* Modal Styles */
        #feedbackModal {
            position: fixed;
            top: 60px; /* Adjust this value according to the height of your header */
            left: 0;
            width: 100%;
            height: calc(100% - 60px); /* Adjust this value according to the height of your header */
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000; /* Ensure the modal is above other elements */
        }

        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            position: relative;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }

        .star-rating .star {
            font-size: 2em;
            cursor: pointer;
            color: #ccc;
        }

        .star-rating .star.selected {
            color: gold;
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
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .action-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .action-buttons button:hover {
            opacity: 0.8;
        }

        .action-buttons button {
            background-color: #6c757d;
            color: white;
        }

        .action-buttons button:hover {
            background-color: #5a6268;
        }

    </style>
</head>
<body>
<?php include 'include/studentheader.php'; ?>
<h2>LEARNING MATERIAL</h2>
<div class="custom-line"></div>

<div class="container">
    <div class="course-container">
        <h2>Select Course</h2>
        <input type="text" id="searchInput" placeholder="Search course title...">
        <div id="courseGrid" class="course-grid">
            <?php if (count($courses) > 0): ?>
                <?php foreach ($courses as $course): ?>
                    <div class="course-card">
                        <a href="folder_details_student.php?course_id=<?php echo htmlspecialchars($course['CourseID']); ?>&class_id=<?php echo htmlspecialchars($classID); ?>">
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

    <div class="folder-container">
        <h2>FOLDERS</h2>

        <div class="action-buttons">
            <button onclick="window.location='folder_details_student.php?class_id=<?php echo $class_id; ?>&course_id=<?php echo $course_id; ?>';">Back</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Folder Name</th>
                        <th>Date Added</th>
                    </tr>
                </thead>
                <tbody>
                        <tr>
                            <td><?php echo "1"; ?></td>
                            <td><?php echo htmlspecialchars($Folder['FolderName']); ?></td>
                            <td><?php echo htmlspecialchars($Folder['FolderDate']); ?></td>
                        </tr>
                </tbody>
            </table>
        </div>

        <h2>MATERIAL</h2>

        <div class="learn-table-container">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>File Type</th>
                        <th>File</th>
                        <th>Date Added</th>
                        <th>Feedback</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($learningMaterials) > 0): ?>
                        <?php foreach ($learningMaterials as $index => $material): ?>
                            <tr style="cursor: pointer;">
                                <td class="file-row" data-file-path="<?php echo htmlspecialchars($material['LearnMateFilePath']); ?>"><?php echo $index + 1; ?></td>
                                <td class="file-row" data-file-path="<?php echo htmlspecialchars($material['LearnMateFilePath']); ?>"><?php echo htmlspecialchars($material['LearnMateName']); ?></td>
                                <td class="file-row" data-file-path="<?php echo htmlspecialchars($material['LearnMateFilePath']); ?>"><?php echo htmlspecialchars($material['LearnMateFilePath']); ?></td>
                                <td class="file-row" data-file-path="<?php echo htmlspecialchars($material['LearnMateFilePath']); ?>"><?php echo htmlspecialchars($material['LearnMateDate']); ?></td>
                                <td>
                                    <button class="feedback-btn" data-learnmate-id="<?php echo htmlspecialchars($material['LearnMateID']); ?>">Give Feedback</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No materials added yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>

<!-- Feedback Modal -->
<div id="feedbackModal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2>Rate this material</h2>
        <div class="star-rating">
            <span class="star" data-value="1">&#9733;</span>
            <span class="star" data-value="2">&#9733;</span>
            <span class="star" data-value="3">&#9733;</span>
            <span class="star" data-value="4">&#9733;</span>
            <span class="star" data-value="5">&#9733;</span>
        </div>
        <button id="submitFeedback">Submit</button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fileRows = document.querySelectorAll('.file-row');

        fileRows.forEach(row => {
            row.addEventListener('click', function () {
                const filePath = 'uploads/' + this.getAttribute('data-file-path');
                const fileName = filePath.split('/').pop();
                const fileExtension = filePath.split('.').pop().toLowerCase();

                if (fileExtension === 'mp4' || fileExtension === 'webm') {
                    // Video file, open in a new tab for preview
                    window.open(filePath, '_blank');
                } else if (fileExtension === 'jpg' || fileExtension === 'jpeg' || fileExtension === 'png' || fileExtension === 'gif') {
                    // Image file, download
                    const a = document.createElement('a');
                    a.href = filePath;
                    a.download = fileName;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                } else {
                    // Other file types, download
                    const a = document.createElement('a');
                    a.href = filePath;
                    a.download = fileName;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const feedbackButtons = document.querySelectorAll('.feedback-btn');
        const modal = document.getElementById('feedbackModal');
        const closeModal = document.querySelector('.close-btn');
        const stars = document.querySelectorAll('.star');
        let selectedRating = 0;
        let learnMateID = 0;

        feedbackButtons.forEach(button => {
            button.addEventListener('click', function () {
                learnMateID = this.getAttribute('data-learnmate-id');
                modal.style.display = 'block';
            });
        });

        closeModal.addEventListener('click', function () {
            modal.style.display = 'none';
            resetStars();
        });

        stars.forEach(star => {
            star.addEventListener('click', function () {
                selectedRating = this.getAttribute('data-value');
                highlightStars(selectedRating);
            });
        });

        document.getElementById('submitFeedback').addEventListener('click', function () {
            if (selectedRating > 0 && learnMateID > 0) {
                submitFeedback(learnMateID, selectedRating);
                modal.style.display = 'none';
                resetStars();
            }
        });

        function highlightStars(rating) {
            stars.forEach(star => {
                const starValue = star.getAttribute('data-value');
                if (starValue <= rating) {
                    star.classList.add('selected');
                } else {
                    star.classList.remove('selected');
                }
            });
        }

        function resetStars() {
            selectedRating = 0;
            stars.forEach(star => {
                star.classList.remove('selected');
            });
        }

        function submitFeedback(learnMateID, rating) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "submit_feedback.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    alert("Feedback submitted successfully!");
                }
            };
            xhr.send("learnMateID=" + learnMateID + "&rating=" + rating);
        }
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
