<?php
// ตั้งค่าการเชื่อมต่อฐานข้อมูล
$servername = "db"; // ใช้ชื่อ service ของ MySQL ใน docker-compose
$username = "root"; // ชื่อผู้ใช้ฐานข้อมูลที่กำหนดใน docker-compose
$password = "MYSQL_ROOT_PASSWORD"; // รหัสผ่านฐานข้อมูลที่กำหนดใน docker-compose
$dbname = "ip2"; // ชื่อฐานข้อมูลที่กำหนดใน docker-compose

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
