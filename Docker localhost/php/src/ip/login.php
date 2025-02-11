<?php
session_start();
include 'database.php';

// รับข้อมูลจากฟอร์ม
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $password = $_POST['password'];

    // 3. ตรวจสอบ Username และ Password ใน Table `users`
    $sql = "SELECT * FROM users WHERE id = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $id, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // หากพบผู้ใช้ในฐานข้อมูล
        $user = $result->fetch_assoc();
        
        // 4. เก็บข้อมูลผู้ใช้ลงใน Session
        $_SESSION['user_id'] = $user['id'];        // เก็บ user_id
        $_SESSION['user_role'] = $user['role_id'];        // เก็บ role


        // 5. Redirect ไปยัง hometest.php
        header("Location: hometest.php");
        exit();
    } else {
        // หากไม่พบผู้ใช้
        echo "Invalid Username or Password!";
    }
    $stmt->close();
}
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | StepIntoStyle</title>
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <div class="container">
        <h1 class="title">เข้าสู่ระบบ</h1>
        <div class="login-box">
            <form action="login.php" method="POST">
                <input type="text" name="id" placeholder="ID" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
        </div>
    </div>
</body>

</html>