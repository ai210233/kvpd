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
    SELECT assignteacher.AssignteacherID, course.CourseID, course.CourseTitle, course.CourseDepartment, class.ClassID, class.ClassName 
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

// Prepare data for JavaScript
$courseTitlesWithClassNames = array_map(function($course) {
    return $course['CourseTitle'] . ' - ' . $course['ClassName'];
}, $courses);

$studentCounts = [];
$stmt = $conn->prepare("
    SELECT COUNT(*) as studentCount 
    FROM student 
    WHERE CourseDepartment = ? AND ClassID = ?
");

if ($stmt === false) {
    die("Failed to prepare statement: " . $conn->error);
}

foreach ($courses as $course) {
    $courseDepartment = $course['CourseDepartment'];
    $classID = $course['ClassID'];

    $stmt->bind_param("si", $courseDepartment, $classID);
    $stmt->execute();
    $studentCountResult = $stmt->get_result();

    if ($studentCountResult->num_rows == 1) {
        $studentCountRow = $studentCountResult->fetch_assoc();
        $studentCounts[] = $studentCountRow['studentCount'];
    } else {
        $studentCounts[] = 0;
    }
}

$stmt->close();

// Dummy data generation for submission status counts
$assignmentStatus = [];

// Generating dummy data for each course retrieved from actual data
foreach ($courses as $course) {
    $courseTitle = $course['CourseTitle'] . ' - ' . $course['ClassName'];

    // Random counts for each submission status
    $notSubmitted = rand(0, 20);
    $onTime = rand(0, 20);
    $late = rand(0, 20);

    // Store submission counts for the current course
    $assignmentStatus[$courseTitle] = [
        'Not Submitted' => $notSubmitted,
        'On-time' => $onTime,
        'Late' => $late
    ];
}

$gradeData = [];
$gradeQuery = "
    SELECT assignteacher.AssignteacherID, course.CourseTitle, class.ClassName,
        SUM(CASE WHEN (overallgrade.AssignmentMark + overallgrade.QuizMark + overallgrade.TestMark + overallgrade.FinalMark) BETWEEN 80 AND 100 THEN 1 ELSE 0 END) as A,
        SUM(CASE WHEN (overallgrade.AssignmentMark + overallgrade.QuizMark + overallgrade.TestMark + overallgrade.FinalMark) BETWEEN 70 AND 79 THEN 1 ELSE 0 END) as B,
        SUM(CASE WHEN (overallgrade.AssignmentMark + overallgrade.QuizMark + overallgrade.TestMark + overallgrade.FinalMark) BETWEEN 60 AND 69 THEN 1 ELSE 0 END) as C,
        SUM(CASE WHEN (overallgrade.AssignmentMark + overallgrade.QuizMark + overallgrade.TestMark + overallgrade.FinalMark) BETWEEN 50 AND 59 THEN 1 ELSE 0 END) as D,
        SUM(CASE WHEN (overallgrade.AssignmentMark + overallgrade.QuizMark + overallgrade.TestMark + overallgrade.FinalMark) BETWEEN 40 AND 49 THEN 1 ELSE 0 END) as E,
        SUM(CASE WHEN (overallgrade.AssignmentMark + overallgrade.QuizMark + overallgrade.TestMark + overallgrade.FinalMark) < 40 THEN 1 ELSE 0 END) as Fail
    FROM assignteacher
    JOIN course ON assignteacher.CourseID = course.CourseID
    JOIN class ON assignteacher.ClassID = class.ClassID
    JOIN student ON student.CourseDepartment = course.CourseDepartment AND student.ClassID = class.ClassID
    JOIN overallgrade ON overallgrade.StudentID = student.StudentID AND overallgrade.AssignteacherID = assignteacher.AssignteacherID
    WHERE assignteacher.TeacherID = $teacherID
    GROUP BY assignteacher.AssignteacherID, course.CourseTitle, class.ClassName";
$gradeResult = $conn->query($gradeQuery);

if ($gradeResult->num_rows > 0) {
    while ($row = $gradeResult->fetch_assoc()) {
        $courseTitleWithClassName = $row['CourseTitle'] . ' - ' . $row['ClassName'];
        $gradeData[$row['AssignteacherID']] = [
            'course' => $courseTitleWithClassName,
            'grades' => [
                'A' => $row['A'],
                'B' => $row['B'],
                'C' => $row['C'],
                'D' => $row['D'],
                'E' => $row['E'],
                'Fail' => $row['Fail']
            ]
        ];
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="script/admin/navbar.js"></script>
    <style>
        .container {
            max-width: 1700px;
            margin: 20px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-gap: 20px;
        }

        .graph-container {
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Add some styling for the graph title */
        .graph-container h3 {
            text-align: center;
            color: #333;
        }

        .pie-chart-container {
            display: flex;
            justify-content: center;
        }

        .graph-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .graph-header h3 {
            margin: 0;
            font-size: 20px;
            color: #333;
        }

        .dropdown {
            position: relative;
        }

        .dropdown select {
            padding: 8px 15px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-color: #fff;
            cursor: pointer;
        }

        .dropdown .dropdown-icon {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            color: #555;
        }

        .dropdown select:focus + .dropdown-icon {
            color: #333;
        }

        .canvas-container {
            position: relative;
        }

        .canvas-container canvas {
            max-width: 100%;
            max-height: 600px;
        }
    </style>
</head>
<body>
<?php include 'include/teacherheader.php'; ?>
<h2>REPORT</h2>

<div class="custom-line"></div>

<div class="container">
    <div class="graph-container" id="graph1">
        <div class="graph-header">
            <h3>Number of Students per Course</h3>
        </div>
        <div class="canvas-container">
            <canvas id="barChart"></canvas>
        </div>
    </div>

    <div class="graph-container" id="graph2">
        <div class="graph-header">
            <h3>Total Feedback by Star Rating</h3>
            <div class="dropdown">
                <select id="assignedClassDropdown">
                    <option value="all">All Classes</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['AssignteacherID']; ?>"><?php echo $course['CourseTitle'] . ' - ' . $course['ClassName']; ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="dropdown-icon">&#9660;</span>
            </div>
        </div>
        <div class="canvas-container">
            <canvas id="horizontalBarChart"></canvas>
        </div>
    </div>
    <div class="graph-container" id="graph3">
        <div class="graph-header">
            <h3>Submission Status of Assignments</h3>
        </div>
        <div class="canvas-container">
            <canvas id="stackedBarChart"></canvas>
        </div>
    </div>
    <div class="graph-container" id="graph4">
        <div class="graph-header">
            <h3>Total Student Grades Distribution</h3>
            <div class="dropdown">
                <select id="gradeClassDropdown">
                    <?php foreach ($gradeData as $assignteacherID => $courseData): ?>
                        <option value="<?php echo $assignteacherID; ?>"><?php echo $courseData['course']; ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="dropdown-icon">&#9660;</span>
            </div>
        </div>
        <div class="pie-chart-container">
            <canvas id="pieChart" style="max-width: 600px; max-height: 600px;"></canvas>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>

<script>
    // Prepare the data for the chart
    const courseTitlesWithClassNames = <?php echo json_encode($courseTitlesWithClassNames); ?>;
    const studentCounts = <?php echo json_encode($studentCounts); ?>;

    // Create the bar chart with enhancements
    const ctx = document.getElementById('barChart').getContext('2d');
    const barChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: courseTitlesWithClassNames,
            datasets: [{
                label: 'Number of Students',
                data: studentCounts,
                backgroundColor: function(context) {
                    const colors = [
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ];
                    return colors[context.dataIndex % colors.length];
                },
                borderColor: function(context) {
                    const colors = [
                        'rgba(75, 192, 192, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ];
                    return colors[context.dataIndex % colors.length];
                },
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Number of Students per Course',
                    font: {
                        size: 18
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Students'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Assigned Courses'
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutBounce'
            }
        }
    });

    // Data for the horizontal bar chart
    const feedbackData = {
        labels: ['5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star'],
        datasets: [{
            label: 'Total Feedback',
            data: [12, 19, 3, 5, 2], 
            backgroundColor: [
                'rgba(75, 192, 192, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(153, 102, 255, 0.2)',
                'rgba(255, 159, 64, 0.2)'
            ],
            borderColor: [
                'rgba(75, 192, 192, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]
    };

    // Create the horizontal bar chart
    const ctx2 = document.getElementById('horizontalBarChart').getContext('2d');
    const horizontalBarChart = new Chart(ctx2, {
        type: 'bar',
        data: feedbackData,
        options: {
            indexAxis: 'y',
            plugins: {
                title: {
                    display: true,
                    text: 'Total Feedback by Star Rating',
                    font: {
                        size: 18
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw;
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Total Feedback'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Star Rating'
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutBounce'
            }
        }
    });

    // Function to update the horizontal bar chart based on the selected class
    function updateFeedbackChart(selectedAssignteacherID) {
        // Perform AJAX request to get feedback data for the selected class
        // Replace this with your actual AJAX call to fetch feedback data for the selected class
        // For demo purposes, here we update the feedback data with random values
        const newFeedbackData = {
            labels: ['5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star'],
            datasets: [{
                label: 'Total Feedback',
                data: Array.from({ length: 5 }, () => Math.floor(Math.random() * 20)),
                backgroundColor: [
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        };

        // Update the chart with new data
        horizontalBarChart.data = newFeedbackData;
        horizontalBarChart.update();
    }

    // Event listener for the dropdown change
    document.getElementById('assignedClassDropdown').addEventListener('change', function(event) {
        const selectedAssignteacherID = event.target.value;
        updateFeedbackChart(selectedAssignteacherID);
    });

    const assignmentData = <?php echo json_encode($assignmentStatus); ?>;
        const courseTitles = <?php echo json_encode(array_keys($assignmentStatus)); ?>;
        const assignmentLabels = ['Not Submitted', 'On-time', 'Late'];

        // Colors for each status
        const statusColors = {
            'Not Submitted': 'rgba(54, 162, 235, 0.5)', // Blue
            'On-time': 'rgba(75, 192, 192, 0.5)',       // Green
            'Late': 'rgba(255, 99, 132, 0.5)'           // Red
        };

        // Create datasets for the stacked bar chart
        const datasets = assignmentLabels.map(label => {
            return {
                label: label,
                data: courseTitles.map(courseTitle => assignmentData[courseTitle][label]),
                backgroundColor: statusColors[label],
                borderColor: statusColors[label],
                borderWidth: 1
            };
        });

        // Create the stacked bar chart
        const ctx3 = document.getElementById('stackedBarChart').getContext('2d');
        const stackedBarChart = new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: courseTitles,
                datasets: datasets
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: 'Assignment Status by Course',
                        font: {
                            size: 18
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Assigned Courses'
                        }
                    },
                    y: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Total Assignments'
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutBounce'
                }
            }
        });

        // Prepare grade data for the chart
const gradeData = <?php echo json_encode($gradeData); ?>;

// Function to create or update the pie chart based on selected course
function updateGradeChart(assignteacherID) {
    const gradeLabels = ['A', 'B', 'C', 'D', 'E', 'Fail'];
    const colors = [
        'rgba(75, 192, 192, 0.5)',  // A
        'rgba(54, 162, 235, 0.5)',  // B
        'rgba(255, 206, 86, 0.5)',  // C
        'rgba(153, 102, 255, 0.5)', // D
        'rgba(255, 159, 64, 0.5)',  // E
        'rgba(255, 99, 132, 0.5)'   // Fail
    ];

    const courseGrades = gradeData[assignteacherID].grades;
    const data = gradeLabels.map(label => courseGrades[label]);

    if (window.gradeChart) {
        window.gradeChart.data.datasets[0].data = data;
        window.gradeChart.update();
    } else {
        const ctx4 = document.getElementById('pieChart').getContext('2d');
        window.gradeChart = new Chart(ctx4, {
            type: 'pie',
            data: {
                labels: gradeLabels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: 'Student Grades Distribution',
                        font: {
                            size: 18
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                return `${label}: ${value}`;
                            }
                        }
                    }
                }
            }
        });
    }
}

// Event listener for the dropdown change
document.getElementById('gradeClassDropdown').addEventListener('change', function(event) {
    const selectedAssignteacherID = event.target.value;
    updateGradeChart(selectedAssignteacherID);
});

// Initialize with the first course's data
document.addEventListener('DOMContentLoaded', function() {
    const initialAssignteacherID = document.getElementById('gradeClassDropdown').value;
    updateGradeChart(initialAssignteacherID);
});
</script>


</body>
</html>

<?php
// Close the connection
$conn->close();
?>
