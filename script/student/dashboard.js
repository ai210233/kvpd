
function searchLearningMaterial() {
    // Get selected CourseTitle
    var selectedCourseTitle = $("#courseTitle").val();

    // Make an AJAX request to fetch learning materials based on CourseTitle and ClassName
    $.ajax({
        type: "POST",
        url: "fetch_learning_material.php", // Create this PHP file to handle the server-side logic
        data: { courseTitle: selectedCourseTitle },
        success: function(response) {
            $(".learning-material-container").html(response);
        }
    });
}

// Function to populate CourseTitle dropdown based on CourseDepartment
function populateCourseTitles() {
    // Get CourseDepartment based on StudentID
    var courseDepartment = "<?php echo $studentSKM; ?>";

    // Make an AJAX request to fetch CourseTitles based on CourseDepartment
    $.ajax({
        type: "POST",
        url: "fetch_course_titles.php", // Create this PHP file to handle the server-side logic
        data: { courseDepartment: courseDepartment },
        success: function(response) {
            $("#courseTitle").html(response);
        }
    });
}

// Populate CourseTitles on page load
$(document).ready(function() {
    populateCourseTitles();
});