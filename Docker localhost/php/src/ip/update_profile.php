<?php
include 'database.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่ามีการส่งข้อมูลมาจาก AJAX หรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ตรวจสอบข้อมูลที่ส่งมาจากฟอร์ม
    if (isset($_POST['about_text']) && isset($_POST['experience_text']) && 
        isset($_POST['skills_text']) && isset($_POST['interest_text'])) {
        
        $about_text = $_POST['about_text'];
        $experience_text = $_POST['experience_text'];
        $skills_text = $_POST['skills_text'];
        $interest_text = $_POST['interest_text'];

        // สมมติว่าใช้ session หรือค่าอื่นๆ เพื่อระบุตัวตนผู้ใช้
        session_start();
        $user_id = $_SESSION['user_id']; // ใช้ session หรือ method อื่นเพื่อระบุ user_id

        // สร้างคำสั่ง SQL สำหรับการอัปเดตข้อมูล
        $sql = "UPDATE students 
                SET about_text = ?, experience_text = ?, skills_text = ?, interest_text = ? 
                WHERE id = ?";

        // ใช้ statement เพื่อป้องกัน SQL injection
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $about_text, $experience_text, $skills_text, $interest_text, $user_id);

        // ตรวจสอบว่าอัปเดตสำเร็จหรือไม่
        if ($stmt->execute()) {
            echo "Data updated successfully.";
        } else {
            echo "Error updating data: " . $conn->error;
        }

        // ปิดการเชื่อมต่อ
        $stmt->close();
        $conn->close();
    } else {
        echo "Missing data in the request.";
    }
} else {
    echo "Invalid request method.";
}
?>
