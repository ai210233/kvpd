<?php
session_start();

date_default_timezone_set('Asia/Kuala_Lumpur');

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

$folderID = intval($_GET['folder_id']); 
$assignteacherID = intval($_GET['assignteacher_id']); 

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

$editFolderQuery = "SELECT FolderName, FolderDate, ShowFolder FROM learnfolder WHERE LearnfolderID = $folderID";
$editFolderResult = $conn->query($editFolderQuery);
$editFolder = $editFolderResult->fetch_assoc();

// Query to get learning materials
$learningMaterialQuery = "SELECT LearnMateID, LearnMateName, LearnMateFilePath, LearnMateDate FROM learningmaterial WHERE LearnfolderID = $folderID";
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


if (isset($_POST['editFolder'])) {
    // Retrieve form data
    $folderName = $_POST['folderName'];
    $folderStatus = $_POST['folderStatus'];

    // Update folder details in the database
    $updateFolderQuery = "UPDATE learnfolder SET FolderName = '$folderName', ShowFolder = '$folderStatus' WHERE LearnfolderID = $folderID";

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
    $deleteQuery = "DELETE FROM learningmaterial WHERE LearnMateID IN ($placeholders)";
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
        .edit-learn-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
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

        .action-buttons button#editButton {
            background-color: #007BFF;
            color: white;
        }

        .action-buttons button#editButton:hover {
            background-color: #0056b3;
        }

        .action-buttons button {
            background-color: #6c757d;
            color: white;
        }

        .action-buttons button:hover {
            background-color: #5a6268;
        }

        #editForm {
            display: none;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 20px;
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
            width: calc(100% - 22px); /* Adjusted width to accommodate for padding */
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box; /* Ensure padding is included in the width calculation */
        }

        /* Adjusting width and padding for better alignment */
        #folderName,
        #folderStatus, 
        #folderDate {
            width: calc(100% - 22px);
            padding: 10px;
        }

        .form-group button[type="submit"] {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background-color: #28a745;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .form-group button[type="submit"]:hover {
            background-color: #218838;
        }

        /* CSS for table */
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
            width: 20px;
        }

        th:nth-child(2), td:nth-child(2) { /* No. column */
            width: 20px;
        }

        th:nth-child(3), td:nth-child(3) { /* Folder Name column */
            width: 10%;
        }

        th:nth-child(4), td:nth-child(4) { /* Folder Name column */
            width: 60%;
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
            margin-right: 20px;
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
<h2>MANAGE LEARNING MATERIAL</h2>

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
                        <a href="learn_details.php?assignteacher_id=<?php echo htmlspecialchars($course['AssignteacherID']); ?>">
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

    <div class="edit-learn-container">
        <h2>Folder</h2>

        <div class="action-buttons">
            <button onclick="window.location='learn_details.php?assignteacher_id=<?php echo $assignteacherID; ?>';">Back</button>
            <button id="editButton" onclick="toggleEditForm()">Edit</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Folder Name</th>
                        <th>Date Added</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                        <tr>
                            <td><?php echo htmlspecialchars($editFolder['FolderName']); ?></td>
                            <td><?php echo htmlspecialchars($editFolder['FolderDate']); ?></td>
                            <td><?php echo ($editFolder['ShowFolder'] == 0) ? 'Unlocked' : 'Locked'; ?></td>
                        </tr>
                </tbody>
            </table>
        </div>

        <div id="editForm" style="display: none;">
            <form method="post" action="">
                <div class="form-group">
                    <label for="folderName">Folder Name:</label>
                    <input type="text" id="folderName" name="folderName" value="<?php echo htmlspecialchars($editFolder['FolderName']); ?>" placeholder="Enter folder name" required>
                </div>
                <div class="form-group">
                    <label for="folderDate">Date:</label>
                    <input type="text" id="folderDate" name="folderDate" value="<?php echo htmlspecialchars($editFolder['FolderDate']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="folderStatus">Status:</label>
                    <select id="folderStatus" name="folderStatus" required>
                        <option value="0" <?php if ($editFolder['ShowFolder'] == 0) echo 'selected'; ?>>Show Folder</option>
                        <option value="1" <?php if ($editFolder['ShowFolder'] == 1) echo 'selected'; ?>>Hide Folder</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" name="editFolder" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>

        <h2>Material</h2>

        <!-- Buttons for actions -->
        <form method="POST" id="deleteForm">
            <div class="action-buttons">
                <button type="button" onclick="window.location='add_file.php?folder_id=<?php echo $folderID; ?>&assignteacher_id=<?php echo $assignteacherID; ?>';">Add File</button>
                <button type="submit" class="delete" id="deleteButton" disabled>Delete</button>
            </div>

            <div class="learn-table-container">
                <table>
                    <thead>
                        <tr>
                            <th></th>
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
                                    <!-- Checkbox for each row -->
                                    <td>
                                        <input type="checkbox" name="selectedRows[]" value="<?php echo htmlspecialchars($material['LearnMateID']); ?>" onclick="toggleDeleteButton()">
                                    </td>
                                    <td class="file-row" data-file-path="<?php echo htmlspecialchars($material['LearnMateFilePath']); ?>"><?php echo $index + 1; ?></td>
                                    <td class="file-row" data-file-path="<?php echo htmlspecialchars($material['LearnMateFilePath']); ?>"><?php echo htmlspecialchars($material['LearnMateName']); ?></td>
                                    <td class="file-row" data-file-path="<?php echo htmlspecialchars($material['LearnMateFilePath']); ?>"><?php echo htmlspecialchars($material['LearnMateFilePath']); ?></td>
                                    <td class="file-row" data-file-path="<?php echo htmlspecialchars($material['LearnMateFilePath']); ?>"><?php echo htmlspecialchars($material['LearnMateDate']); ?></td>
                                    <td>
                                        <button type="button" onclick="window.location='view_feedback.php?folder_id=<?php echo $folderID; ?>&assignteacher_id=<?php echo $assignteacherID; ?>&learnmate_id=<?php echo htmlspecialchars($material['LearnMateID']); ?>';">View Feedback</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No folders added yet.</td>
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
