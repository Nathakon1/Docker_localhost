<?php
session_start();
include 'database.php';

// ✅ เปิด Error Reporting เพื่อให้แสดง Error ถ้ามี
error_reporting(E_ALL);
ini_set('display_errors', 1);

$teacher_id = $_SESSION['user_id'] ?? 0;
// ✅ ถ้าเป็น AJAX Request สำหรับโหลดนิสิตที่เกี่ยวข้องกับงาน
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['job_id'])) {
    $job_id = intval($_GET['job_id'] ?? 0);
    $response = [];

    if ($job_id > 0) {
        $sql = "SELECT s.id AS student_id, s.name AS student_name
                FROM job_applications ja
                JOIN students s ON ja.student_id = s.id
                JOIN accepted_applications aa ON ja.id = aa.job_application_id
                WHERE ja.post_jobs_id = ? 
                AND aa.accept_status_id = 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $response[] = [
                'student_id' => $row['student_id'],
                'student_name' => $row['student_name']
            ];
        }
        $stmt->close();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ✅ Debug: ตรวจสอบค่าที่ได้รับจาก JavaScript
    file_put_contents("debug_log.txt", print_r($_POST, true), FILE_APPEND);

    $student_id = intval($_POST['student_id'] ?? 0);
    $job_id = intval($_POST['job_id'] ?? 0);
    $post_id = $job_id; // ✅ แก้ไข: ใช้ post_id แทน job_id
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($teacher_id == 0 || $student_id == 0 || $post_id == 0 || $rating < 1 || $rating > 5 || empty($comment)) {
        echo json_encode(["success" => false, "error" => "ข้อมูลไม่ถูกต้อง"]);
        exit();
    }

    // ✅ ตรวจสอบว่า นิสิตถูก Accept และงานปิดแล้ว
    $sqlCheckStatus = "SELECT js.job_status_name, aa.accept_status_id
                       FROM post_jobs j
                       JOIN job_status js ON j.job_status_id = js.id
                       JOIN job_applications ja ON j.id = ja.post_jobs_id
                       JOIN accepted_applications aa ON ja.id = aa.job_application_id
                       WHERE j.id = ? 
                       AND js.job_status_name = 'close'
                       AND aa.accept_status_id = 1";

    $stmtCheck = $conn->prepare($sqlCheckStatus);
    $stmtCheck->bind_param("i", $post_id);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows === 0) {
        echo json_encode(["success" => false, "error" => "คุณไม่สามารถรีวิวได้ เพราะงานยังไม่ปิด หรือ นิสิตไม่ได้ถูก Accept"]);
        exit();
    }
    $stmtCheck->close();

    // ✅ Debug: ตรวจสอบค่าที่จะถูก INSERT
    error_log("📌 INSERT Review: Teacher ID = $teacher_id, Student ID = $student_id, Post ID = $post_id, Rating = $rating, Comment = $comment");
    file_put_contents("debug_log.txt", print_r($_POST, true), FILE_APPEND);
    error_log("📌 Debug PHP: job_id = $job_id, student_id = $student_id, rating = $rating, comment = $comment");

    // ✅ บันทึกรีวิวลงฐานข้อมูล
    $sql = "INSERT INTO reviews (teacher_id, student_id, post_id, rating, comment) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die(json_encode(["success" => false, "error" => "❌ SQL Prepare Error: " . $conn->error]));
    }

    $stmt->bind_param("iiiis", $teacher_id, $student_id, $post_id, $rating, $comment);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "❌ SQL Execution Error: " . $stmt->error]);
    }

    $stmt->close();
    exit();
}

// ✅ ดึงนิสิตที่อาจารย์สามารถรีวิวได้
$sql = "SELECT s.id AS student_id, s.name AS student_name, j.id AS job_id, j.title AS job_title 
        FROM job_applications ja
        JOIN students s ON ja.student_id = s.id
        JOIN post_jobs j ON ja.post_jobs_id = j.id
        JOIN job_status js ON j.job_status_id = js.id
        JOIN accepted_applications aa ON ja.id = aa.job_application_id
        WHERE j.teacher_id = ? 
        AND js.job_status_name = 'close'
        AND aa.accept_status_id = 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ✅ ดึงรายชื่อ "งานที่อาจารย์สร้าง" สำหรับแสดงในฟอร์ม// ✅ ดึงเฉพาะงานที่ปิดแล้ว (job_status_name = 'close')
$sql = "SELECT j.id AS job_id, j.title AS job_title 
FROM post_jobs j
JOIN job_status js ON j.job_status_id = js.id
WHERE j.teacher_id = ? 
AND js.job_status_name = 'close'";  // ✅ เงื่อนไขเลือกเฉพาะงานที่ปิดแล้ว

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ให้คะแนนนิสิต</title>
    <link rel="stylesheet" href="css/review_student.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/header-footerstyle.css">

</head>

<body><body>
    <!-- ✅ ส่วน Header -->
    <header class="headerTop">
        <div class="headerTopImg">
            <img src="logo.png" alt="Naresuan University Logo">
            <a href="#">Naresuan University</a>
        </div>
        <nav class="header-nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <!-- ✅ ส่วน Navigation -->
    <nav class="review-head">
        <a href="teacher_profile.php"><i class="bi bi-chevron-left"></i></a>
    </nav>

    <!-- ✅ ส่วน Main Content -->
    <main class="container">
        <h2>Review Student</h2>

        <form id="reviewForm">
            <!-- ✅ เลือกงาน -->
            <label for="job">เลือกงาน :</label>
            <select id="job" name="job_id" required>
                <option value="">-- เลือกงาน --</option>
                <?php foreach ($jobs as $job): ?>
                    <option value="<?php echo $job['job_id']; ?>">
                        <?php echo $job['job_title']; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- ✅ เลือกนิสิต -->
            <label for="student">นิสิต :</label>
            <select id="student" name="student_id" required disabled>
                <option value="">-- กรุณาเลือกงานก่อน --</option>
            </select>

            <!-- ✅ ให้คะแนน -->
            <label for="rating">คะแนน :</label>
            <input type="number" id="rating" name="rating" min="1" max="5" required disabled>

            <!-- ✅ รีวิว -->
            <label for="comment">รีวิว :</label>
            <textarea id="comment" name="comment" required disabled></textarea>

            <!-- ✅ ปุ่มส่งรีวิว -->
            <button type="submit" disabled>ส่งรีวิว</button>
        </form>

        <!-- ✅ แสดงข้อความแจ้งเตือน -->
        <p id="statusMessage"></p>
    </main>

    <!-- ✅ ส่วน Footer -->
    <footer class="footer">
        <p>© CSIT - Computer Science and Information Technology</p>
    </footer>
</body>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const jobSelect = document.getElementById("job");
            const studentSelect = document.getElementById("student");
            const ratingInput = document.getElementById("rating");
            const commentInput = document.getElementById("comment");
            const submitButton = document.querySelector("button[type='submit']");
            const form = document.getElementById("reviewForm");

            // ✅ โหลดนิสิตที่เกี่ยวข้องเมื่อเลือกงาน
            jobSelect.addEventListener("change", function() {
                const jobId = jobSelect.value;

                if (!jobId) {
                    studentSelect.innerHTML = `<option value="">-- กรุณาเลือกงานก่อน --</option>`;
                    studentSelect.disabled = true;
                    ratingInput.disabled = true;
                    commentInput.disabled = true;
                    submitButton.disabled = true;
                    return;
                }

                fetch(`review_student.php?job_id=${jobId}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log("📌 Loaded Students:", data); // ✅ Debug รายชื่อนิสิต
                        studentSelect.innerHTML = `<option value="">-- เลือกนิสิต --</option>`;
                        data.forEach(student => {
                            studentSelect.innerHTML += `<option value="${student.student_id}">${student.student_name}</option>`;
                        });

                        studentSelect.disabled = false;
                    })
                    .catch(error => console.error("❌ Fetch Error:", error));
            });

            // ✅ เปิดให้ใส่คะแนน & รีวิวเมื่อเลือกนิสิต
            studentSelect.addEventListener("change", function() {
                if (studentSelect.value) {
                    ratingInput.disabled = false;
                    commentInput.disabled = false;
                    submitButton.disabled = false;
                } else {
                    ratingInput.disabled = true;
                    commentInput.disabled = true;
                    submitButton.disabled = true;
                }
            });

            // ✅ ส่งข้อมูลรีวิวไปที่ PHP
            form.addEventListener("submit", function(event) {
                event.preventDefault();

                const jobId = jobSelect.value;
                const studentId = studentSelect.value;
                const rating = ratingInput.value;
                const comment = commentInput.value;

                if (!jobId) {
                    alert("กรุณาเลือกงาน");
                    return;
                }
                if (!studentId) {
                    alert("กรุณาเลือกนิสิต");
                    return;
                }
                if (!rating) {
                    alert("กรุณาให้คะแนน");
                    return;
                }
                if (!comment) {
                    alert("กรุณาเขียนรีวิว");
                    return;
                }

                console.log("📌 Sending Data:", {
                    jobId,
                    studentId,
                    rating,
                    comment
                }); // ✅ Debug ค่าก่อนส่ง

                fetch("review_student.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `job_id=${encodeURIComponent(jobId)}&student_id=${encodeURIComponent(studentId)}&rating=${encodeURIComponent(rating)}&comment=${encodeURIComponent(comment)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log("📌 Server Response:", data); // ✅ Debug ตอบกลับจาก PHP
                        if (data.success) {
                            document.getElementById("statusMessage").innerText = "✅ รีวิวถูกบันทึกแล้ว";
                            form.reset();
                            studentSelect.innerHTML = `<option value="">-- กรุณาเลือกงานก่อน --</option>`;
                            studentSelect.disabled = true;
                            ratingInput.disabled = true;
                            commentInput.disabled = true;
                            submitButton.disabled = true;
                        } else {
                            document.getElementById("statusMessage").innerText = "❌ บันทึกรีวิวไม่สำเร็จ: " + data.error;
                        }
                    })
                    .catch(error => console.error("❌ Fetch Error:", error));
            });
        });
    </script>
</body>

</html>