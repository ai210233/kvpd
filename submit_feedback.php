<?php
session_start();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $learnMateID = intval($_POST['learnMateID']);
    $rating = intval($_POST['rating']);

    // Insert the feedback into the database
    $stmt = $conn->prepare("INSERT INTO feedback (LearnMateID, FeedbackRate) VALUES (?, ?)");
    $stmt->bind_param("ii", $learnMateID, $rating);

    if ($stmt->execute()) {
        echo "Feedback submitted successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
