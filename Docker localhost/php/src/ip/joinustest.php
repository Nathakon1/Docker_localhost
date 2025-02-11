<?php
session_start();
include 'database.php';

// รับค่า id จาก URL
$job_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($job_id) {
    // ดึงข้อมูลรายละเอียดของงานตาม ID
    $sql = "SELECT post_jobs.title, post_jobs.image, post_jobs.description, reward_type.reward_name, job_categories.category_name AS category, teachers.name AS teacher
            FROM post_jobs 
            JOIN teachers ON post_jobs.teacher_id = teachers.id
            JOIN job_categories ON post_jobs.category_id = job_categories.id
            JOIN reward_type ON post_jobs.reward_id = reward_type.id
            WHERE post_jobs.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $job = $result->fetch_assoc();
    $stmt->close();
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // ดึง role_id ของผู้ใช้จากฐานข้อมูล
    $sql = "SELECT role_id FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($user_role);
    $stmt->fetch();
    $stmt->close();
} else {
    $user_role = 0; // ถ้าไม่ได้ล็อกอิน กำหนดค่าเป็น 0
}

// ดึงกฎการรายงานจากฐานข้อมูล
$report_reasons = [];
$sql = "SELECT id, report_category_name FROM report_categories";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $report_reasons[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Join Us Page">
    <title>Join Us</title>
    <link rel="stylesheet" href="css/header-footerstyle.css">
    <link rel="stylesheet" href="css/joinus.css">
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

    <!-- Main Content -->
    <a href="hometest.php" class="back-arrow"></a>

    <div class="container">
        <div class="applicant-card">
            <div class="applicant-photo-joinus">
                <img src="<?php echo htmlspecialchars($job['image']); ?>" alt="Job Image">
            </div>
        </div>

        <div class="title-container">
            <h1 class="section-title"><?php echo htmlspecialchars($job['title']); ?></h1>
        </div>

        <div class="applicant-card">
            <div class="applicant-details">
                <span><?php echo nl2br(htmlspecialchars($job['description'])); ?></span>
            </div>
            <div class="applicant-reward">
                <span>ผลตอบแทน :</span>
                <span><?php echo nl2br(htmlspecialchars($job['reward_name'])); ?></span>
            </div>

            <div class="applicant-details-name">
                <span class="emoji">👤</span>
                <?php echo htmlspecialchars($job['teacher']); ?>
            </div>

            <!-- Button Container -->
            <div class="button-container">
                <?php
                if (isset($_SESSION['user_id'])) {
                    echo '<button class="report-btn" onclick="showReportModal()">รายงาน</button>';

                    if ($user_role == 4) {
                        echo '<a href="jobapply.php?id=' . htmlspecialchars($job_id) . '"><button class="joinus-btn">Join us</button></a>';
                    } else {
                        echo '<script>
                                 function showAlert() {
                                alert("มีแค่นิสิตที่สามารถสมัครงานได้");
                        }
                     </script>';
                        echo '<button class="joinus-btn disabled" onclick="showAlert()">Join us</button>';
                    }
                } else {
                    echo '<a href="login.php"><button class="report-btn">รายงาน</button></a>';
                    echo '<a href="login.php"><button class="joinus-btn">Join us</button></a>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Report Modal -->
    <div class="modal" id="reportModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>รายงานปัญหา</h2>
                <span class="close-btn" onclick="closeReportModal()"></span>
            </div>

            <div class="modal-body">
                <form action="report_process.php" method="POST">
                    <p>กรุณาเลือกสาเหตุการรายงาน:</p>

                    <div class="report-options">
                        <?php foreach ($report_reasons as $reason) : ?>
                            <label class="report-label">
                                <input type="radio" name="report_reason" value="<?php echo $reason['id']; ?>" required>
                                <?php echo htmlspecialchars($reason['report_category_name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- Hidden Inputs for Post and User ID -->
                    <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($job_id); ?>">
                    <input type="hidden" name="reporter_id" value="<?php echo $_SESSION['user_id']; ?>">

                    <div class="modal-footer">
                        <button type="button" class="cancel-btn" onclick="closeReportModal()">ยกเลิก</button>
                        <button type="submit" class="confirm-btn">ยืนยันการรายงาน</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>© CSIT - Computer Science and Information Technology</p>
    </footer>

    <script src="js/joinus.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
$conn->close();
?>