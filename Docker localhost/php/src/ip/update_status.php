<?php
require 'database.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

$job_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$current_status = isset($_POST['status']) ? intval($_POST['status']) : 1;

// เปลี่ยนค่า status (ถ้า 1 -> 2, ถ้า 2 -> 1)
$new_status = ($current_status == 1) ? 2 : 1;

$sql = "UPDATE post_jobs SET job_status_id = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $new_status, $job_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'new_status' => $new_status]);
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
?>
