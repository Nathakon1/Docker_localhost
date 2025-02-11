<?php
session_start();
// เชื่อมต่อฐานข้อมูล
include 'database.php';

// ตรวจสอบและอัปเดตสถานะแจ้งเตือนเมื่อมีการส่งค่าผ่าน URL (GET)
if (isset($_GET['id'])) {
    $notification_id = intval($_GET['id']); // ป้องกัน SQL Injection

    $update_sql = "UPDATE notifications SET status = 'read' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $notification_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "id" => $notification_id]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }

    $stmt->close();
    $conn->close();
    exit(); // หยุดการทำงานของ PHP
}


$user_id = $_SESSION['user_id'];

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

// ✅ ดึงข้อมูลแจ้งเตือน
function getNotifications($conn, $user_id)
{
    $sql = "SELECT notifications.id AS notification_id, 
                   notifications.message, 
                   notifications.created_at, 
                   notifications.status, 
                   accepted_applications.accept_status_id, 
                   accept_status.accept_name_status
            FROM notifications
            JOIN accepted_applications ON notifications.reference_id = accepted_applications.id
            JOIN accept_status ON accepted_applications.accept_status_id = accept_status.id
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
            'title' => $row['accept_name_status'],
            'message' => $row['message'],
            'time' => $row['created_at'],
            'status' => strtolower($row['status']),
            'accept_status_id' => $row['accept_status_id'] ?? null
        ];
    }
    return $notifications;
}
// ✅ อัปเดตสถานะแจ้งเตือนเป็น "read"
if (isset($_GET['id'])) {
    $notification_id = intval($_GET['id']);
    $update_sql = "UPDATE notifications SET status = 'read' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $notification_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "id" => $notification_id]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
    $stmt->close();
    $conn->close();
    exit();
}
// ✅ โหลดแจ้งเตือนทั้งหมด (เมื่อโหลดหน้าเว็บ)
$notifications = getNotifications($conn, $user_id);
$unread_count = count(array_filter($notifications, function ($n) {
    return $n['status'] === 'unread';
}));

$sql = "SELECT students.id, students.name, students.email, students.major_id, students.year, 
               students.about_text, students.experience_text, students.skills_text, students.interest_text,
               mojor.id AS major_id, mojor.mojor_name
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
    <link rel="stylesheet" href="css/header-footer.html">
    <script type="application/json" id="notifications-data">
        <?php echo json_encode($notifications); ?>
    </script>

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
                <a href="review.php">
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
                <div>
                    <button class="notification-btn">
                        <i class="bi bi-bell"></i>
                        <span class="notification-badge" <?php echo ($unread_count == 0) ? 'style="display:none;"' : ''; ?>>
                            <?php echo $unread_count; ?>
                        </span>
                        <button class="edit-button" onclick="toggleEdit()">Edit</button>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Content Section -->
    <!--พวกรีวิวแจ้งเตือนและปุ่มแก้ไข-->
    <div class="notifications-card" id="notifications">
        <div class="headerNoti">
            <h1 class="page-title">Notifications</h1>
            <span class="notification-count" <?php echo ($unread_count == 0) ? 'style="display:none;"' : ''; ?>>
                <?php echo $unread_count; ?> new</span>
            <button class="close-button" id="close-notifications">&times;</button>
        </div>
        <div class="tabs">
            <div class="tab active" data-filter="all">All</div>
            <div class="tab" data-filter="unread">Unread</div>
            <div class="tab" data-filter="accepted">Accepted</div>
            <div class="tab" data-filter="reject">Rejected</div>
        </div>
        <div class="notification-list" id="notification-list">
            <?php foreach ($notifications as $notification) { ?>
                <div class="notification-item" data-status="<?php echo $notification['status']; ?>">
                    <div class="notification-content">
                        <h3 class="notification-title"><?php echo $notification['title']; ?></h3>
                        <p class="notification-message"><?php echo $notification['message']; ?></p>
                        <span class="notification-time"><?php echo $notification['time']; ?></span>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

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
    <footer class="footer">
        <p>© CSIT - Computer Science and Information Technology</p>
    </footer>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tabs = document.querySelectorAll(".tab");
            const notificationList = document.getElementById("notification-list");

            function fetchNotifications(filterType) {
                fetch(window.location.href) // 👈 โหลดข้อมูลจากไฟล์เดียวกัน
                    .then(response => response.text())
                    .then(html => {
                        let parser = new DOMParser();
                        let doc = parser.parseFromString(html, "text/html");
                        let notifications = JSON.parse(doc.getElementById("notifications-data").textContent);
                        updateNotifications(notifications, filterType);
                    })
                    .catch(error => console.error("Error fetching notifications:", error));
            }

            function updateNotifications(notifications, filterType) {
                notificationList.innerHTML = ""; // เคลียร์รายการเดิม
                let unreadCount = 0;

                notifications.forEach((notification, index) => {
                    if (filterType === "all" ||
                        (filterType === "unread" && notification.status === "unread") ||
                        (filterType === "accepted" && notification.title === "Accepted") ||
                        (filterType === "reject" && notification.title === "Rejected")) {

                        const notificationItem = document.createElement("div");
                        notificationItem.classList.add("notification-item", notification.status);
                        notificationItem.setAttribute("data-status", notification.status);
                        notificationItem.setAttribute("data-id", notification.id);
                        notificationItem.innerHTML = `
                <div class="notification-content">
                    <h3 class="notification-title">${notification.title}</h3>
                    <p class="notification-message">${notification.message}</p>
                    <span class="notification-time">${notification.time}</span>
                </div>
            `;

                        // ✅ ถ้าเป็น unread ให้เพิ่ม event listener
                        if (notification.status === "unread") {
                            notificationItem.addEventListener("click", function() {
                                markAsRead(notification.id);
                            });
                            unreadCount++;
                        }

                        notificationList.appendChild(notificationItem);
                    }
                });

                // ✅ อัปเดต badge และ notification count
                let notificationBadge = document.querySelector(".notification-badge");
                let notificationCount = document.querySelector(".notification-count");

                if (notificationBadge && notificationCount) {
                    if (unreadCount > 0) {
                        notificationBadge.innerText = unreadCount;
                        notificationBadge.style.display = "inline-block";
                        notificationCount.innerText = `${unreadCount} new`;
                        notificationCount.style.display = "inline-block";
                    } else {
                        notificationBadge.style.display = "none";
                        notificationCount.style.display = "none";
                    }
                }

                // ✅ เปลี่ยนชื่อแท็บ "Unread" เป็น "Unread" โดยไม่มีตัวเลข
                let unreadTab = document.querySelector(".tab[data-filter='unread']");
                if (unreadTab) {
                    unreadTab.innerText = "Unread";
                }
            }


            function markAsRead(notificationId) {
                fetch(`?id=${notificationId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log("Notification marked as read:", notificationId);

                            // ✅ ค้นหา notification item ที่คลิก
                            let notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                            if (notificationItem) {
                                // ✅ ตรวจสอบว่ากำลังอยู่ในแท็บ Unread หรือไม่
                                let activeTab = document.querySelector(".tab.active").getAttribute("data-filter");
                                if (activeTab === "unread") {
                                    notificationItem.remove(); // 🔥 ลบแจ้งเตือนออกจาก Unread ทันที
                                } else {
                                    // ✅ ถ้าอยู่แท็บอื่นให้เปลี่ยนเป็น Read
                                    notificationItem.dataset.status = "read";
                                    notificationItem.classList.remove("unread");
                                    notificationItem.classList.add("read");
                                }
                            }

                            // ✅ อัปเดต badge และ notification count ทันที
                            let notificationBadge = document.querySelector(".notification-badge");
                            let notificationCount = document.querySelector(".notification-count");

                            if (notificationBadge && notificationCount) {
                                let currentCount = parseInt(notificationBadge.innerText) || 0;
                                if (currentCount > 0) {
                                    currentCount--;
                                    notificationBadge.innerText = currentCount;
                                    notificationCount.innerText = `${currentCount} new`;

                                    if (currentCount === 0) {
                                        notificationBadge.style.display = "none";
                                        notificationCount.style.display = "none";
                                    }
                                }
                            }

                            // ✅ แท็บ Unread อัปเดตให้แสดงเป็น "Unread" โดยไม่มีตัวเลข
                            let unreadTab = document.querySelector(".tab[data-filter='unread']");
                            if (unreadTab) {
                                unreadTab.innerText = "Unread";
                            }
                        }
                    })
                    .catch(error => console.error("Error updating notification:", error));
            }



            // 🔄 โหลดข้อมูลใหม่ทุกครั้งที่เปลี่ยนแท็บ
            tabs.forEach(tab => {
                tab.addEventListener("click", function() {
                    tabs.forEach(t => t.classList.remove("active"));
                    this.classList.add("active");

                    const filterType = this.getAttribute("data-filter");
                    fetchNotifications(filterType); // 🔄 โหลดข้อมูลใหม่
                });
            });

            // โหลดแจ้งเตือนทั้งหมดตอนเริ่มต้น
            fetchNotifications("all");
        });
    </script>
    <script>
        const notificationButton = document.querySelector('.notification-btn');
        const notificationsCard = document.getElementById('notifications');
        const closeButton = document.getElementById('close-notifications');

        notificationButton.addEventListener('click', () => {
            notificationsCard.style.display = 'block';
        });

        closeButton.addEventListener('click', () => {
            notificationsCard.style.display = 'none';
        });

        document.addEventListener('click', (event) => {
            if (!notificationsCard.contains(event.target) && !notificationButton.contains(event.target)) {
                notificationsCard.style.display = 'none';
            }
        });
    </script>
    <script>
        function toggleEdit() {
            // สลับแสดงข้อมูลระหว่าง "แสดง" และ "แก้ไข"
            const textSections = ['about_text', 'experience_text', 'skills_text', 'interest_text'];

            textSections.forEach(section => {
                const displayElement = document.getElementById(`${section}_display`);
                const editElement = document.getElementById(`${section}_edit`);

                // ถ้าข้อมูลแสดงอยู่ ให้แสดงฟอร์มแก้ไข
                if (displayElement.style.display !== "none") {
                    displayElement.style.display = "none";
                    editElement.style.display = "block";
                } else {
                    displayElement.style.display = "block";
                    editElement.style.display = "none";
                }
            });

            // สลับแสดงปุ่ม Save
            const saveButton = document.querySelector('.save-button');
            saveButton.style.display = saveButton.style.display === "none" ? "inline-block" : "none";
        }

        function saveChanges() {
            // ดึงข้อมูลจาก textarea
            let aboutText = document.getElementById('about_text_edit').value;
            let experienceText = document.getElementById('experience_text_edit').value;
            let skillsText = document.getElementById('skills_text_edit').value;
            let interestText = document.getElementById('interest_text_edit').value;

            // ตรวจสอบค่าที่ดึงมา
            console.log("About:", aboutText);
            console.log("Experience:", experienceText);
            console.log("Skills:", skillsText);
            console.log("Interest:", interestText);

            // ส่งข้อมูลไปยัง PHP
            let xhr = new XMLHttpRequest();
            xhr.open("POST", "update_profile.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    // แสดงข้อมูลที่ถูกอัปเดตบนหน้าเว็บ
                    document.getElementById('about_text_display').innerText = aboutText;
                    document.getElementById('experience_text_display').innerText = experienceText;
                    document.getElementById('skills_text_display').innerText = skillsText;
                    document.getElementById('interest_text_display').innerText = interestText;

                    // ซ่อน textarea และแสดงข้อความที่ถูกแก้ไข
                    toggleEdit();
                }
            };

            xhr.send("about_text=" + encodeURIComponent(aboutText) +
                "&experience_text=" + encodeURIComponent(experienceText) +
                "&skills_text=" + encodeURIComponent(skillsText) +
                "&interest_text=" + encodeURIComponent(interestText));
        }
    </script>

</body>

</html>
<?php $conn->close(); ?>