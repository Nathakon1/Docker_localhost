<?php 
session_start();
include 'database.php';

// Query ข้อมูลรวม job กับ categories และ teachers
$sql = "SELECT 
            job_categories.id AS category_id, 
            job_categories.category_name AS category_name,
            post_jobs.id AS job_id,
            post_jobs.job_status_id, 
            post_jobs.title, 
            post_jobs.image, 
            teachers.name AS teacher
        FROM job_categories
        LEFT JOIN post_jobs ON job_categories.id = post_jobs.category_id
        LEFT JOIN teachers ON post_jobs.teacher_id = teachers.id
        ORDER BY job_categories.id";

$result = $conn->query($sql);

// จัดกลุ่มข้อมูลตามหมวดหมู่
$jobs = [];
while ($row = $result->fetch_assoc()) {
    $category_id = $row["category_id"];
    $jobs[$category_id]["name"] = $row["category_name"];

    if (!is_null($row["job_id"])) { // ตรวจสอบว่ามีงานในหมวดหมู่นี้หรือไม่
        $jobs[$category_id]["jobs"][] = $row;
    } else {
        $jobs[$category_id]["jobs"] = []; // ให้เป็นอาร์เรย์ว่างหากไม่มีงาน
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSIT Job Board</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/filter.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/header-footerstyle.css">
</head>

<body>
    <!-- Header -->
    <header class="headerTop">
        <div class="headerTopImg">
            <img src="logo.png" alt="Naresuan University Logo">
            <a href="#">Naresuan University</a>
        </div>
        <nav class="header-nav">
            <?php
            if (isset($_SESSION['user_id'])) {
                if ($_SESSION['user_role'] == 3) {
                    echo '<a href="teacher_profile.php">Profile</a>';
                } elseif ($_SESSION['user_role'] == 4) {
                    echo '<a href="stuf.php">Profile</a>';
                }
            } else {
                echo '<a href="login.php">Login</a>';
            }
            ?>
        </nav>
    </header>

    <!-- Navbar Placeholder -->
    <div id="navbar-placeholder"></div>


    <main class="main-content">
    <?php
    foreach ($jobs as $category_id => $category_data) {
        // กรองเฉพาะงานที่ job_status_id เป็น 1
        $filteredJobs = array_filter($category_data["jobs"], function ($job) {
            return isset($job['job_status_id']) && $job['job_status_id'] == 1;
        });

        // แสดงหมวดหมู่เฉพาะเมื่อมีงาน
        if (!empty($filteredJobs)) {
            echo '<div class="section-title">
                <h2>' . htmlspecialchars($category_data["name"]) . '</h2>
                <a href="view_all_jobs.php?category_id=' . urlencode($category_id) . '" class="see-all-btn">See All</a>
            </div>';

            echo '<div class="job-grid">';
            $count = 0;
            foreach ($filteredJobs as $job) {
                if ($count >= 4) break;
                echo '<a href="joinustest.php?id=' . htmlspecialchars($job['job_id']) . '&ip=' . $_SERVER['REMOTE_ADDR'] . '">
                    <div class="job-card">
                        <img class="job-image" src="' . htmlspecialchars($job['image']) . '" alt="Job Image">
                        <div class="job-info">
                            <div class="job-title">' . htmlspecialchars($job["title"]) . '</div>
                            <div class="job-author">' . htmlspecialchars($job["teacher"]) . '</div>
                        </div>
                    </div>
                </a>';
                $count++;
            }
            echo '</div>'; // ปิด job-grid
        }
    }
    ?>
</main>


    <footer class="footer">
        <p>© CSIT - Computer Science and Information Technology</p>
    </footer>

    <script src="js/main.js"></script>
    <script src="js/navbar.js"></script>
    <script src="js/filter.js"></script>
</body>
</html>

<?php
$conn->close();
?>
