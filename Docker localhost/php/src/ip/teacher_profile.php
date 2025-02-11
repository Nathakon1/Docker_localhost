<?php
session_start();
include 'database.php';
// รับค่า user_id (มาจาก session หรือ request)
$user_id = $_SESSION['user_id'] ?? 0;

//(1) ตรวจจับ POST: อัปเดต Contact (teachers) + Job (post_jobs)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. รับค่า phone_number, email (Contact)
    $user_id     = $_SESSION['user_id'] ?? 0;
    $phone_number = $_POST['phone_number'] ?? '';
    $email        = $_POST['email'] ?? '';

    // 2. อัปเดตข้อมูลตาราง teachers
    $sqlTeacher = "UPDATE teachers
                   SET Phone_number = ?,
                       email = ?
                   WHERE id = ?";
    $stmtT = $conn->prepare($sqlTeacher);
    $stmtT->bind_param("ssi", $phone_number, $email, $user_id);

    if (!$stmtT->execute()) {
        echo "error_teachers";
        exit();
    }
    $stmtT->close();
    // ถ้าไม่มีปัญหาใด ๆ
    $conn->close();
    echo "success";
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['notification_id'])) {
    session_start();
    include 'database.php'; // ✅ เชื่อมต่อฐานข้อมูล

    $user_id = $_SESSION['user_id'] ?? 0;  // ✅ รับ user_id จาก session
    $notification_id = intval($_GET['notification_id']); // ✅ แปลงเป็น int ป้องกัน SQL Injection

    if ($notification_id > 0 && $user_id > 0) {
        $sql = "UPDATE `notifications` 
                SET `status` = 'read' 
                WHERE `id` = ? AND `user_id` = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $notification_id, $user_id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Notification updated"]);
        } else {
            echo json_encode(["success" => false, "error" => "Database update failed"]);
        }

        $stmt->close();
    } else {
        echo json_encode(["success" => false, "error" => "Invalid ID or user"]);
    }

    exit();
}



// ✅ 2. ดึงข้อมูลแจ้งเตือน
$sql = "SELECT notifications.id AS notification_id, 
               notifications.message, 
               notifications.created_at, 
               notifications.status, 
               post_jobs.title AS job_title
        FROM notifications
        JOIN job_applications ON notifications.reference_id = job_applications.id
        JOIN post_jobs ON job_applications.post_jobs_id = post_jobs.id
        WHERE notifications.user_id = ?
        ORDER BY notifications.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => $row['notification_id'],
        'title' => $row['job_title'],
        'message' => $row['message'],
        'time' => $row['created_at'],
        'status' => strtolower($row['status'])
    ];
}
$stmt->close();

// ✅ 3. ดึงจำนวนแจ้งเตือนที่ยังไม่ได้อ่าน
$sql = "SELECT COUNT(*) AS unread_count FROM notifications WHERE status = 'unread' AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$unread_count = $row ? $row['unread_count'] : 0;
$stmt->close();

// ✅ 4. ส่ง JSON กลับไปให้ JavaScript (เฉพาะเมื่อเป็น AJAX Request)
if (isset($_GET['fetch_notifications'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'notifications' => $notifications,
        'unread_count' => $unread_count
    ]);
    exit(); // **ออกจากสคริปต์ทันที** เพื่อให้ PHP ไม่โหลด HTML ด้านล่าง
}
// --3.2 ดึงข้อมูลอาจารย์ (Contact)
$sqlTeacher = "SELECT 
                  t.id,
                  t.name,
                  t.email,
                  t.department_id,
                  t.Phone_number,
                  m.id AS mojor_id,
                  m.mojor_name AS department_name
               FROM teachers t
               JOIN mojor m ON t.department_id = m.id
               WHERE t.id = ?";
$stmtT = $conn->prepare($sqlTeacher);
$stmtT->bind_param("i", $user_id);
$stmtT->execute();
$resT = $stmtT->get_result();
$teacher = $resT->fetch_assoc();
$stmtT->close();



// --3.4 ดึง job ของอาจารย์ (post_jobs)
$sqlJobs = "SELECT * 
            FROM post_jobs
            WHERE teacher_id = ?
            ORDER BY created_at DESC";
$stmtJ = $conn->prepare($sqlJobs);
$stmtJ->bind_param("i", $user_id);
$stmtJ->execute();
$resJobs = $stmtJ->get_result();
$jobs = [];
while ($rowJob = $resJobs->fetch_assoc()) {
    $jobs[] = $rowJob;
}
$stmtJ->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Teacher Profile</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">
    <!-- ไฟล์ CSS ของคุณ -->
    <link rel="stylesheet" href="css/header-footer.html">
    <link rel="stylesheet" href="css/header-footerstyle.css">
    <link rel="stylesheet" href="css/TeacherProfileStyle.css">
    <link rel="stylesheet" href="css/header-footer.html">
    <script src="js/teacher_profile.js"></script>
</head>

<body>
    <div class="profile-container">

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
                    echo '<a href="logout.php">Logout</a>';
                } else {
                    // หากยังไม่ได้ล็อกอิน แสดงปุ่มเข้าสู่ระบบ
                    echo '<a href="login.php">Login</a>';
                }
                ?>
            </nav>
        </header>
        <!-- End Header -->

        <!-- โปรไฟล์ส่วนบน -->
        <div class="header">
            <a href="hometest.php"><i class="bi bi-chevron-left text-white h4 "></i></a>
            <div class="profile">
                <div class="profile-pic">
                    <?php echo strtoupper(mb_substr($teacher['name'], 0, 1, 'UTF-8')); ?>
                </div>
                <div class="detail-name">
                    <div class="name">
                        <?php echo htmlspecialchars($teacher['name'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div class="sub-title">
                        อาจารย์ภาควิชา <?php echo htmlspecialchars($teacher['department_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Header Profile -->

        <!-- Content -->
        <div class="content">
            <div class="detail-head">
                <div class="review">
                    <div class="review-detail">
                        <!-- ถ้าจะแสดงคะแนน/รีวิว -->
                    </div>
                </div>
                <div>
                    <!-- Notification btn -->
                    <button class="notification-btn">
                        <i class="bi bi-bell"></i>
                        <span class="notification-badge" <?php echo ($unread_count == 0) ? 'style="display:none;"' : ''; ?>>
                            <?php echo $unread_count; ?>
                        </span>
                    </button>

                    <!-- Notifications card -->
                    <div class="notifications-card" id="notifications">
                        <div class="headerNoti">
                            <h1 class="page-title">Notifications</h1>
                            <span class="notification-count" <?php echo ($unread_count == 0) ? 'style="display:none;"' : ''; ?>>
                                <?php echo $unread_count; ?> new
                            </span>
                            <button class="close-button" id="close-notifications">&times;</button>
                        </div>

                        <!-- แท็บกรองเฉพาะ All และ Unread -->
                        <div class="tabs">
                            <div class="tab active" data-filter="all">All</div>
                            <div class="tab" data-filter="unread">Unread</div>
                        </div>

                        <!-- กล่องเลื่อนขึ้นลงเมื่อมีมากกว่า 2 รายการ -->
                        <div class="notification-list" id="notification-list">
                            <?php foreach ($notifications as $notification) { ?>
                                <div class="notification-item"
                                    data-status="<?php echo $notification['status']; ?>"
                                    data-id="<?php echo $notification['id']; ?>">
                                    <div class="notification-content">
                                        <h3 class="notification-title"><?php echo $notification['title']; ?></h3>
                                        <p class="notification-message"><?php echo $notification['message']; ?></p>
                                        <span class="notification-time"><?php echo $notification['time']; ?></span>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- Add Job -->
                    <a href="jobpost.php">
                        <button class="addJob-button">Add Job</button>
                    </a>

                    <!-- ปุ่ม Edit -->
                    <button class="edit-button" onclick="toggleEdit()">Edit</button>
                </div>
            </div>
        </div>

        <!-- Contact -->
        <div class="container">
            <h3>Contact</h3>
            <section class="Contact">
                <!-- โหมดแสดง -->
                <div id="contact_display">
                    <p>เบอร์โทร : <?php echo htmlspecialchars($teacher['Phone_number'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p>อีเมล : <?php echo htmlspecialchars($teacher['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <!-- โหมดแก้ไข -->
                <div id="contact_edit" style="display:none;">
                    <label for="phone_number_input">เบอร์โทร :</label>
                    <input type="text" id="phone_number_input"
                        value="<?php echo htmlspecialchars($teacher['Phone_number'], ENT_QUOTES, 'UTF-8'); ?>">

                    <br><br>
                    <label for="email_input">อีเมล :</label>
                    <input type="email" id="email_input"
                        value="<?php echo htmlspecialchars($teacher['email'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </section>
        </div>
        <!-- ปุ่ม Save อยู่ด้านล่าง job -->
        <div class="container">
            <button class="save-button" style="display:none;" onclick="saveChanges()">Save</button>
        </div>
        <div class="container">
            <div class="menu-review">
                <h3>Job</h3>
                <a href="review_student.php" class="btn-review">
                    review
                </a>
            </div>
            <div class="content">
                <div class="grid" id="job_container">
                    <?php foreach ($jobs as $job) { ?>
                        <div class="card" id="<?php echo $job['id']; ?>"
                            onclick="window.location='jobmanage.php?id=<?php echo $job['id']; ?>'">

                            <!-- โหมดแสดง -->
                            <div class="job_display" id="job_display_<?php echo $job['id']; ?>">
                                <div class="card-top">
                                    <!-- ตรวจสอบว่า URL ของรูปภาพถูกต้อง -->
                                    <img src="<?php echo htmlspecialchars($job['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Job Image" class="job-image">
                                </div>
                                <div class="card-body">
                                    <!-- แสดง title -->
                                    <h3><?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p class="job-description">
                                        <?php
                                        $description = htmlspecialchars($job['description'], ENT_QUOTES, 'UTF-8');
                                        // แสดงรายละเอียด ถ้าคำอธิบายยาวกว่า 100 ตัวอักษร จะแสดงแบบย่อ
                                        echo (strlen($description) > 100) ? substr($description, 0, 100) . '...' : $description;
                                        ?>
                                        <span class="full-description" style="display:none;"><?php echo $description; ?></span>
                                        <?php if (strlen($description) > 100) { ?>
                                            <button class="read-more" onclick="toggleDescription(this)">อ่านเพิ่มเติม</button>
                                        <?php } ?>
                                    </p>
                                    <!-- แสดงจำนวนผู้สมัคร -->
                                    <p><strong>รับจำนวน:</strong> <?php echo htmlspecialchars($job['number_student'], ENT_QUOTES, 'UTF-8'); ?> คน</p>
                                    <!-- แสดงวันที่ประกาศ -->
                                    <p><strong>ประกาศเมื่อ:</strong> <?php echo htmlspecialchars($job['created_at'], ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>







        <!-- Footer -->
        <footer class="footer">
            <p>© CSIT - Computer Science and Information Technology</p>
        </footer>
    </div>

</body>

</html>

<?php $conn->close(); ?>