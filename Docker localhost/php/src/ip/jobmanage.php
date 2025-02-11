<?php
include 'database.php';
session_start();

$job_id = isset($_GET['id']) ? intval($_GET['id']) : null;

// ตรวจสอบว่า job_id มีค่าและเป็นตัวเลขที่ถูกต้อง
if ($job_id) {
    // Query หรืออื่นๆ เพื่อดึงข้อมูลที่ใช้ job_id
    $sql = "SELECT post_jobs.title, post_jobs.image, post_jobs.description, post_jobs.category_id, post_jobs.job_status_id, 
                   post_jobs.number_student, post_jobs.created_at, post_jobs.reward_id,
                   reward_type.reward_name, job_categories.category_name AS category, teachers.name AS teacher
            FROM post_jobs 
            JOIN job_categories ON post_jobs.category_id = job_categories.id
            JOIN reward_type ON post_jobs.reward_id = reward_type.id
            JOIN teachers ON post_jobs.teacher_id = teachers.id
            WHERE post_jobs.id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $job = $result->fetch_assoc();
        } else {
            echo "Job not found!";
            exit();
        }

        $stmt->close(); // ปิด statement หลังจากใช้งานเสร็จ
    } else {
        echo "Database query failed!";
        exit();
    }
} else {
    echo "Invalid Job ID!";
    exit();
}
// ดึงข้อมูลประเภทงานจากฐานข้อมูล
$sql = "SELECT id, category_name FROM job_categories ORDER BY id";
$result = $conn->query($sql);
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

if (isset($_POST['job_id']) && isset($_POST['job_status_id'])) {
    $job_id = intval($_POST['job_id']);
    $job_status_id = intval($_POST['job_status_id']);

    // อัปเดตสถานะของงาน
    $sql = "UPDATE post_jobs SET job_status_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $job_status_id, $job_id);

    if ($stmt->execute()) {
        echo "Success"; // การอัปเดตสำเร็จ
    } else {
        echo "Error: " . $stmt->error; // ถ้ามีข้อผิดพลาด
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ✅ เมื่อกดปุ่ม Done
    if (isset($_POST['done'])) {
        // รับค่าจากฟอร์ม
        $title = isset($_POST['title']) ? $_POST['title'] : '';
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $reward_id = isset($_POST['reward_id']) ? intval($_POST['reward_id']) : 1; // เริ่มต้นที่ 1 (เงิน)
        $number_student = isset($_POST['number_student']) ? intval($_POST['number_student']) : 1;
        $image_path = isset($_POST['images']) && !empty($_POST['images']) ? $_POST['images'] : $job['image'];

        // ตรวจสอบการกรอกข้อมูล
        if (empty($title) || empty($description) || empty($category_id)) {
            die("Title, Description, and Category are required.");
        }

        // SQL สำหรับอัปเดตข้อมูล
        $sql = "UPDATE post_jobs SET 
                title = ?, 
                description = ?, 
                category_id = ?, 
                reward_id = ?, 
                number_student = ?, 
                image = ? 
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiiisi", $title, $description, $category_id, $reward_id, $number_student, $image_path, $job_id);

        if ($stmt->execute()) {
            echo "<script>alert('Job updated successfully'); window.location='teacher_profile.php';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }
    }

    // ✅ เมื่อกดปุ่ม Delete
    if (isset($_POST['delete_job'])) {
        $delete_status = 3; // เปลี่ยนสถานะเป็นลบ
        $stmt = $conn->prepare("UPDATE post_jobs SET job_status_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $delete_status, $job_id);
        if ($stmt->execute()) {
            // ถ้าการลบสำเร็จ
            echo "<script>alert('Job marked as deleted'); window.location='teacher_profile.php';</script>";
            // หรือถ้าต้องการกลับไปที่หน้า jobmanage.php
            // echo "<script>alert('Job marked as deleted'); window.location='jobmanage.php';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }
    }
}

// ดึงข้อมูลจากฐานข้อมูล
if ($job_id) {
    $sql = "SELECT * FROM post_jobs WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $job = $result->fetch_assoc();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Posting Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/header-footerstyle.css">
    <script src="js/jobmanage.js"></script>
    <link rel="stylesheet" href="css/jobmanage.css">
    <style>
        #statusBtn {
            font-size: 18px;
            font-weight: bold;
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            outline: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* สถานะเปิด (สีเขียวพาสเทล) */
        .open {
            background-color:rgb(98, 214, 98);
            /* เขียวพาสเทล */
            color:rgb(255, 255, 255);
            box-shadow: 0px 4px 10px rgba(160, 231, 160, 0.8);
        }

        /* สถานะปิด (สีส้มพาสเทล) */
        .close {
            background-color:rgb(233, 117, 93);
            /* ส้มพาสเทล */
            color:rgb(255, 255, 255);
            box-shadow: 0px 4px 10px rgba(255, 181, 167, 0.8);
        }

        /* เอฟเฟกต์ตอน hover */
        #statusBtn:hover {
            transform: scale(1.1);
        }

        /* เอฟเฟกต์ตอนกด */
        #statusBtn:active {
            transform: scale(0.9);
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
            <a href="#">About Us</a>
            <a href="#">News</a>
            <a href="#">Logout</a>
        </nav>
    </header>


    <!--เครื่องหมายย้อนกลับ-->
    <nav class="back-head">
        <a href="javascript:history.back()"> <i class="bi bi-chevron-left"></i></a>
    </nav>

    <div class="title-container">
        <a href="#" class="nav-link " onclick="toggleViewApp(this)">View Applications</a>
        <a href="#" class="nav-link bg-gray" onclick="toggleManageJob(this)">Manage Job</a>
    </div>


    <!-- Main Content -->
    <main class="container">

        <!--ส่วนfromต่างๆ-->
        <div class="form-card">
            <div class="d-flex justify-content-between text-center">
                <h4 class="head-title">Manage Job</h4>
                <button id="statusBtn" onclick="toggleStatus()" class="btn"></button>



            </div>
        </div>


        <form method="POST" action="jobmanage.php?id=<?php echo $job_id; ?>">

            <div class="form-group">
                <label class="form-label">Job Name/ชื่องาน</label>
                <input type="text" class="form-input" name="title" value="<?php echo htmlspecialchars($job['title']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Job Details/รายละเอียดงาน</label>
                <textarea name="description" required><?php echo htmlspecialchars($job['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Job Category/ประเภทงาน</label>
                <select class="form-select" name="category_id" required>
                    <option value="">-- เลือกประเภทงาน --</option>
                    <?php foreach ($categories as $category) : ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $job['category_id']) ? 'selected' : ''; ?>>
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
                <input type="number" name="number_student" min="1" value="<?php echo $job['number_student']; ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Job Category/ผลตอบแทน</label>
                <select class="form-select" name="reward_id" required>
                    <option value="1" <?php echo ($job['reward_id'] == 1) ? 'selected' : ''; ?>>เงิน</option>
                    <option value="2" <?php echo ($job['reward_id'] == 2) ? 'selected' : ''; ?>>ชั่วโมงกิจกรรม</option>
                </select>
            </div>

            <!--ปุ่มส่ง-->
            <div class="submit-group">
                <button type="submit" name="delete_job" class="delete-btn" style="background-color: <?php echo ($job['job_status_id'] == 3) ? 'gray' : 'white'; ?>;">
                    <?php echo ($job['job_status_id'] == 3) ? 'Deleted' : 'Delete'; ?>
                </button>
                <button type="submit" name="done" class="submit-btn">Done</button>
            </div>
        </form>


        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
        </div>
    </main>
    <footer class="footer">
        <p>© CSIT - Computer Science and Information Technology</p>
    </footer>
    <script>
        // เปลี่ยนสถานะของภาพที่เลือก
        function selectImage(imageElement) {
            // ลบคลาส selected ออกจากภาพทั้งหมด
            var images = document.querySelectorAll('.images img');
            images.forEach(img => img.classList.remove('selected'));

            // เพิ่มคลาส selected ให้กับภาพที่ถูกเลือก
            imageElement.classList.add('selected');

            // ดึง path ของภาพที่ถูกเลือก (เอาแค่ชื่อไฟล์ไม่รวม URL)
            var imagePath = imageElement.src.split('/').pop(); // หรือใช้ substring เพื่อดึงชื่อไฟล์

            // อัปเดตค่า imagePath ให้กับ input hidden
            document.getElementById('selectedImagePath').value = "images/" + imagePath;
        }

        function updateJobStatus(toggle) {
            // Get the new status (1 = Open, 2 = Close)
            var status = toggle.checked ? 1 : 2;

            // Update the hidden field with the new status value
            document.getElementById('jobStatus').value = status;

            // Send the new status to the server via AJAX
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "update_job_status.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    // Handle success response (optional)
                    console.log("Status updated successfully!");
                }
            };
            xhr.send("job_id=<?php echo $job['id']; ?>&job_status_id=" + status);
        }

        // เมื่อคลิกที่ลิงก์ "View Applications" หรือ "Manage Job" 
        // เพื่อให้ลิงก์ที่คลิกแสดงสถานะ active
        function toggleViewApp(element) {
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            element.classList.add('active');
        }

        // เมื่อคลิกที่ลิงก์ "Manage Job" เพื่อให้ลิงก์ที่คลิกแสดงสถานะ active
        function toggleManageJob(element) {
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            element.classList.add('active');
        }
    </script>
    <script>
        let jobId = <?php echo $job_id; ?>; // ใส่ ID จริงของงาน

        function loadStatus() {
            fetch(`get_status.php?id=${jobId}`)
                .then(response => response.json())
                .then(data => {
                    updateButton(data.status);
                });
        }

        function toggleStatus() {
            let currentStatus = document.getElementById('statusBtn').dataset.status;

            fetch('update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id=${jobId}&status=${currentStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateButton(data.new_status);
                    }
                });
        }

        function updateButton(status) {
            let btn = document.getElementById('statusBtn');
            btn.dataset.status = status;
            btn.innerText = (status == 1) ? 'เปิด (Open) ' : 'ปิด (Close) ';

            // ลบคลาสเดิม แล้วเพิ่มคลาสใหม่
            btn.classList.remove('open', 'close');
            btn.classList.add(status == 1 ? 'open' : 'close');
        }


        loadStatus(); // โหลดสถานะเริ่มต้นเมื่อหน้าโหลด
    </script>
</body>

</html>