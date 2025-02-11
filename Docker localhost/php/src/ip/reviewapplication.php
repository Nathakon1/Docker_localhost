<?php
session_start();
include 'database.php';
// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']); // ใช้ id จาก URL ถ้ามีการกด View
} else {
    $user_id = $_SESSION['user_id']; // ถ้าไม่มีค่าใน URL ให้ใช้ id ของคนที่ล็อกอินอยู่
}

$sql = "SELECT r.rating, r.comment, t.name AS teacher_name, r.created_at, pj.title
        FROM reviews r
        LEFT JOIN teachers t ON r.teacher_id = t.id
        LEFT JOIN post_jobs pj ON r.post_id = pj.id
        WHERE r.student_id = ?
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = [
        'teacher_name' => $row['teacher_name'],  // ชื่ออาจารย์
        'title' => $row['title'],        // ชื่องาน
        'comment' => $row['comment'],     // คอมเมนต์
        'rating' => $row['rating']     // คอมเมนต์
    ];
}


$sql = "SELECT rating, COUNT(*) as count 
        FROM reviews 
        WHERE student_id = ? 
        GROUP BY rating";

// เตรียมคำสั่ง SQL
$stmt = $conn->prepare($sql);

// ผูกค่าตัวแปรกับคำสั่ง SQL
$stmt->bind_param("i", $user_id);  // 'i' สำหรับ integer

// เรียกใช้คำสั่ง
$stmt->execute();

// ดึงผลลัพธ์
$result = $stmt->get_result();

// ประมวลผลผลลัพธ์
$rating_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$total_reviews = 0;
$total_score = 0;

while ($row = $result->fetch_assoc()) {
    $rating_counts[$row['rating']] = $row['count'];
    $total_reviews += $row['count'];
    $total_score += $row['rating'] * $row['count'];
}

// หาค่าเฉลี่ย
$avg_rating = $total_reviews > 0 ? round($total_score / $total_reviews, 1) : 0;

// หาค่าร้อยละ
$rating_percentages = [];
foreach ($rating_counts as $star => $count) {
    $rating_percentages[$star] = $total_reviews > 0 ? ($count / $total_reviews) * 100 : 0;
}

?>


<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews & Ratings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/reviewstyle.css">
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
    <nav class="review-head">
        <a href="javascript:history.back()"><i class="bi bi-chevron-left"></i></a>
        <h1 class="review-head-text">Review</h1>
    </nav>
    <div class="content">
        <!-- Reviews Section -->
        <!--ส่วนเนื้อหารีวิว-->
        <div class="container">
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="user-info">
                        <div class="user-icon">👤</div>
                        <div class="user-details">
                            <span><?php echo htmlspecialchars($review['teacher_name']); ?></span>
                            <span><?php echo htmlspecialchars($review['title']); ?></span>
                            <span><?php echo nl2br(htmlspecialchars($review['comment'])); ?></span>
                        </div>
                    </div>
                    <div class="review-score">★ <?php echo number_format($review['rating'], 1); ?></div>
                </div>
            <?php endforeach; ?>
        </div>


        <!-- Summary Section -->
        <!--ส่วนรีวิวรวม-->
        <div class="summary">
            <h4>รีวิวจากอาจารย์ (<?php echo $total_reviews; ?>)</h4>
            <div class="bg-sumary">
                <div class="average"><?php echo number_format($avg_rating, 1); ?></div>
                <div class="fullscore">จาก <?php echo $total_reviews; ?> คน</div>
            </div>
            <div class="breakdown">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <div>
                        <span>
                            <?php for ($j = 1; $j <= 5; $j++): ?>
                                <i class="bi bi-star-fill <?php echo $j <= $i ? '' : 'graystar'; ?>"></i>
                            <?php endfor; ?>
                        </span>
                        <div class="bar">
                            <div class="fill" style="width: <?php echo $rating_percentages[5 - $i]; ?>%;"></div>
                        </div>
                        <span>(<?php echo $rating_counts[$i]; ?>)</span>

                    </div>
                <?php endfor; ?>
            </div>
        </div>

    </div>
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>

<?php
$stmt->close();
$conn->close();
?>