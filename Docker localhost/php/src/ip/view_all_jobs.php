<?php
session_start();
include 'database.php';

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'desc'; // เรียงจากใหม่ไปเก่าเป็นค่าเริ่มต้น
$filter_status = isset($_GET['status']) ? intval($_GET['status']) : 0; // 0 หมายถึงไม่กรอง

$sql_category = "SELECT category_name FROM job_categories WHERE id = ?";
$stmt = $conn->prepare($sql_category);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$stmt->bind_result($category_name);
$stmt->fetch();
$stmt->close();

$sql_jobs = "SELECT post_jobs.id, post_jobs.job_status_id, post_jobs.title, post_jobs.image, teachers.name AS teacher
            FROM post_jobs 
            LEFT JOIN teachers ON post_jobs.teacher_id = teachers.id
            WHERE post_jobs.category_id = ?";

if ($filter_status > 0) {
    $sql_jobs .= " AND post_jobs.reward_id = ?";
}

$sql_jobs .= " ORDER BY post_jobs.id " . ($sort_order == 'asc' ? 'ASC' : 'DESC');

$stmt = $conn->prepare($sql_jobs);

if ($filter_status > 0) {
    $stmt->bind_param("ii", $category_id, $filter_status);
} else {
    $stmt->bind_param("i", $category_id);
}

$stmt->execute();
$result = $stmt->get_result();

$jobs = [];
while ($row = $result->fetch_assoc()) {
    if ($row["job_status_id"] == 1 || $row["job_status_id"] == 2) {
        $jobs[] = $row;
    }
}

$stmt->close();
$conn->close();
?>

<!-- index.html -->
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSIT Job Board</title>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- External CSS -->
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/filter.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="css/view_all_jobs.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/header-footerstyle.css">
    <script>
        function applyFilter() {
            let sort = document.getElementById('sort').value;
            let status = document.getElementById('status').value;
            window.location.href = `?category_id=<?php echo $category_id; ?>&sort=${sort}&status=${status}`;
        }
    </script>
    <style>
        /* ปรับแต่งตัวกรอง */
        .filters {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .filters label {
            font-weight: bold;
            color: #333;
        }

        .filters select {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }

        .filters select:hover {
            border-color: #007bff;
        }
    </style>
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
            // ตรวจสอบสถานะการล็อกอิน
            if (isset($_SESSION['user_id'])) {
                if ($_SESSION['user_role'] == 3) {
                    // หาก Role คือ 3 (อาจารย์)
                    echo '<a href="teacher_profile.php">Profile</a>';
                } elseif ($_SESSION['user_role'] == 4) {
                    // หาก Role คือ 4 (นิสิต)
                    echo '<a href="stuf.php">Profile</a>';
                }
            } else {
                // หากยังไม่ได้ล็อกอิน แสดงปุ่มเข้าสู่ระบบ
                echo '<a href="login.php">Login</a>';
            }
            ?>
        </nav>
    </header>

    <!-- Navbar Placeholder -->
    <div id="navbar-placeholder"></div>



    <div id="contentWrapper" class="content-wrapper"></div>

    <main class="main-content">
        <div class="content">
            <h1 class="category-head">งานทั้งหมดในหมวด <?php echo htmlspecialchars($category_name); ?></h1>
            <div class="filters">
                <label>เรียงตาม:</label>
                <select id="sort" onchange="applyFilter()">
                    <option value="desc" <?php echo $sort_order == 'desc' ? 'selected' : ''; ?>>ใหม่ → เก่า</option>
                    <option value="asc" <?php echo $sort_order == 'asc' ? 'selected' : ''; ?>>เก่า → ใหม่</option>
                </select>
                <label>ประเภทผลตอบแทน:</label>
                <select id="status" onchange="applyFilter()">
                    <option value="0">ทั้งหมด</option>
                    <option value="1" <?php echo $filter_status == 1 ? 'selected' : ''; ?>>money</option>
                    <option value="2" <?php echo $filter_status == 2 ? 'selected' : ''; ?>>hours of experience</option>
                </select>
            </div>
            <?php if (empty($jobs)): ?>
                <p class="no-jobs">ไม่มีการประกาศงานประเภทนี้</p>
            <?php else: ?>
                <div class="job-grid">
                    <?php foreach ($jobs as $job): ?>
                        <a href="joinustest.php?id=<?php echo htmlspecialchars($job['id']); ?>&ip=<?php echo $_SERVER['REMOTE_ADDR']; ?>">
                            <div class="job-card">
                                <img class="job-image" src="<?php echo isset($job["image"]) ? htmlspecialchars($job["image"]) : "images/default.jpg"; ?>" alt="Job Image">
                                <div class="job-info">
                                    <div class="job-title"><?php echo htmlspecialchars($job["title"]); ?></div>
                                    <div class="job-author"><?php echo htmlspecialchars($job["teacher"]); ?></div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- External JS -->
    <script src="js/main.js"></script>
    <script src="js/navbar.js"></script>
    <script src="js/filter.js"></script>
</body>

</html>