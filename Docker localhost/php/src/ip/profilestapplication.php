<?php
session_start();
// เชื่อมต่อฐานข้อมูล
include 'database.php';



if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']); // ใช้ id จาก URL ถ้ามีการกด View
} else {
    $user_id = $_SESSION['user_id']; // ถ้าไม่มีค่าใน URL ให้ใช้ id ของคนที่ล็อกอินอยู่
}


// ตรวจสอบว่า user มีอยู่ในฐานข้อมูลหรือไม่
$user_sql = "SELECT id FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

/*if ($user_result->num_rows === 0) {
    die("User not found in database: ID " . $user_id);
} else {
    echo "User found in database: ID " . $user_id;
}*/

$sql = "SELECT students.id, students.name, students.email, students.major_id, 
               students.year, students.about_text, students.experience_text, 
               students.skills_text, students.interest_text, mojor.mojor_name
        FROM students
        JOIN mojor ON students.major_id = mojor.id
        WHERE students.id = ?";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc(); // ดึงข้อมูลนักศึกษาที่ตรงกับ user_id



$sql = "SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count 
FROM reviews 
WHERE student_id= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $avg_rating = round($row['avg_rating'], 1); // ปัดเศษค่าคะแนนเฉลี่ย
    $review_count = $row['review_count']; // จำนวนการให้คะแนน
} else {
    $avg_rating = 0; // ถ้าไม่มีรีวิว ให้คะแนนเป็น 0
    $review_count = 0;
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="css/stupfstyle.css">
    <link rel="stylesheet" href="css/header-footerstyle.css">
</head>

<body>
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
    <!-- รีวิว -->
    <div class="profile-container">
        <div class="header">
            <a href="javascript:history.back()"><i class="bi bi-chevron-left text-white h4 "></i></a>
            <div class="profile">
                <div class="profile-pic">
                    <?php echo strtoupper(mb_substr($student['name'], 0, 1, 'UTF-8')); ?>
                </div>
                <div class="detail-name">
                    <div class="name"><?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="sub-title">สาขา <?php echo htmlspecialchars($student['mojor_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="detail-head">
                <a href="reviewapplication.php?id=<?php echo $user_id; ?>">
                    <div class="review">
                        <div class="rating bg-sumary"><?php echo $avg_rating; ?></div>
                        <div class="review-detail">
                            <div class="stars">
                                <?php
                                // สร้างดาวตามคะแนนเฉลี่ย
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $avg_rating) {
                                        echo '★'; // ถ้า i <= avg_rating ให้แสดงดาวเต็ม
                                    } else {
                                        echo '☆'; // ถ้า i > avg_rating ให้แสดงดาวว่าง
                                    }
                                }
                                ?>
                            </div>
                            <small>from <?php echo $review_count; ?> people</small>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
    <!-- Content Section -->
    <!--พวกรีวิว-->

    <!--ส่วนเนื้อหา-->
    <div class="container">
        <h3>About Me</h3>
        <section class="about-me">
            <!-- แสดงข้อมูลปกติ -->
            <p id="about_text_display"><?php echo htmlspecialchars($student['about_text'], ENT_QUOTES, 'UTF-8'); ?></p>
            <!-- ฟอร์มให้แก้ไขข้อมูล -->
            <textarea class="text_edit" id="about_text_edit" style="display:none;"><?php echo htmlspecialchars($student['about_text'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </section>

        <h3>Experience</h3>
        <section class="experience">
            <p id="experience_text_display"><?php echo htmlspecialchars($student['experience_text'], ENT_QUOTES, 'UTF-8'); ?></p>
            <textarea class="text_edit" id="experience_text_edit" style="display:none;"><?php echo htmlspecialchars($student['experience_text'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </section>

        <h3>Skills</h3>
        <section class="skills">
            <p id="skills_text_display"><?php echo htmlspecialchars($student['skills_text'], ENT_QUOTES, 'UTF-8'); ?></p>
            <textarea class="text_edit" id="skills_text_edit" style="display:none;"><?php echo htmlspecialchars($student['skills_text'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </section>

        <h3>Interest</h3>
        <section class="interest">
            <p id="interest_text_display"><?php echo htmlspecialchars($student['interest_text'], ENT_QUOTES, 'UTF-8'); ?></p>
            <textarea class="text_edit" id="interest_text_edit" style="display:none;"><?php echo htmlspecialchars($student['interest_text'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </section>

        <button class="save-button" style="display:none;" onclick="saveChanges()">Save</button>
    </div>


    </div>



</body>

</html>
<?php $conn->close(); ?>