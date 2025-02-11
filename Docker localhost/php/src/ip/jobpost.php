<?php
session_start();
include 'database.php';
$_SESSION['teacher_id'] = $_SESSION['user_id'] ?? 0;


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับค่าจากฟอร์ม (แก้ไขให้ตรงกัน)
    $jobpost_title = $_POST['title'] ?? null;
    $jobpost_reward_id = $_POST['reward_id'] ?? null;
    $jobpost_descriptions = $_POST['descriptions'] ?? null;
    $jobpost_created_at = $_POST['created_at'] ?? date('Y-m-d H:i:s'); // ใช้วันปัจจุบันหากไม่มีค่า
    $jobpost_number_student = $_POST['number_student'] ?? null;
    $jobpost_category_id = $_POST['category_id'] ?? null;
    $jobpost_teacher_id = $_SESSION['teacher_id'];
    $jobpost_job_status_id = 1;
    $jobpost_images = $_POST['images'] ?? null;

    // ตรวจสอบค่าที่จำเป็นก่อนบันทึก
    if ($jobpost_title && $jobpost_reward_id && $jobpost_descriptions) {
        // ใช้ Prepared Statement ป้องกัน SQL Injection
        $stmt = $conn->prepare("INSERT INTO post_jobs 
            (title, reward_id, description, created_at, number_student, category_id, teacher_id, job_status_id, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "sissiiiis",  // แก้ไขเป็น string, int, string, string, int, int, int, int, string
            $jobpost_title,
            $jobpost_reward_id,
            $jobpost_descriptions,
            $jobpost_created_at,
            $jobpost_number_student,
            $jobpost_category_id,
            $jobpost_teacher_id,
            $jobpost_job_status_id,
            $jobpost_images
        );

        if ($stmt->execute()) {
            echo "<script>alert('New job posted successfully'); window.location='teacher_profile.php';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "<script>alert('Error: Missing required fields.'); window.history.back();</script>";
    }

    $conn->close();
}



?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Posting Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/jobpose.css">
    <link rel="stylesheet" href="css/header-footerstyle.css">
    <script src="js/jobpost.js"></script>
</head>

<body>
    <!-- Header -->
    <header class="headerTop">
        <div class="headerTopImg">
            <img src="logo.png" alt="Naresuan University Logo">
            <a href="#">Naresuan University</a>
        </div>
        <nav class="header-nav">
            <a href="#">About Us</a>
            <a href="#">News</a>
            <a href="#">Logout</a>
        </nav>
    </header>

    <!--เครื่องหมายย้อนกลับ-->
    <nav class="back-head">
        <a href="javascript:history.back()"> <i class="bi bi-chevron-left"></i></a>

    </nav>
    <!-- Main Content -->
    <main class="container">

        <!--ส่วนfromต่างๆ-->
        <div class="form-card">
            <h1 class="form-title">Job Posting</h1>

            <form method="POST" action="jobpost.php">
                <div class="form-group">
                    <label class="form-label">Job Name/ชื่องาน</label>
                    <input type="text" class="form-input" name="title" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Job Details/รายละเอียดงาน</label>
                    <textarea id="job-details" name="descriptions" placeholder="" required></textarea>
                </div>
                <?php
                $categories = [];
                $sql = "SELECT id, category_name 
                            FROM job_categories
                            ORDER BY job_categories.id";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $categories[] = $row;
                    }
                }
                ?>
                <div class="form-group">
                    <label class="form-label">Job Category/ประเภทงาน</label>
                    <select class="form-select" name="category_id" required>
                        <option value="">-- เลือกประเภทงาน --</option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <div class="form-group">
                    <label class="form-label">Cover photo/ภาพหน้าปกงาน</label>
                    <div class="images">
                        <img src="images/img1.jpg" alt="Image 1" onclick="selectImage(this)">
                        <img src="images/img2.jpg" alt="Image 2" onclick="selectImage(this)">
                        <input type="hidden" name="images" id="selectedImagePath">
                    </div>
                </div>


                <div class="form-group">
                    <label class="form-label">Student Count Required/จำนวนตำแหน่งที่ต้องการ</label>
                    <input type="number" id="vacancy" name="number_student" placeholder="" min="1">
                </div>


                <div class="form-group">
                    <label class="form-label">Job Category/ผลตอบแทน</label>
                    <select class="form-select" name="reward_id" required>
                        <option value=""></option>
                        <option value="1">เงิน</option>
                        <option value="2">ชั่วโมงกิจกรรม</option>
                    </select>
                </div>

                <!--ปุ่มส่ง-->
                <div class="submit-group">
                    <button type="submit" class="submit-btn">Add Job</button>
                </div>
            </form>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        </div>
        </div>
    </main>
    <footer class="footer">
        <p>© CSIT - Computer Science and Information Technology</p>
    </footer>
</body>

</html>