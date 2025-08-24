<?php

// مدیریت درخواست‌های OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ۲. تنظیم Content-Type پاسخ به JSON
header("Content-Type: application/json");


// ۴. اتصال به دیتابیس و فعال کردن PDO Exception Mode برای مدیریت بهتر خطاها
try {
    $pdo = new PDO("mysql:host=mysql;dbname=voting", "root", "rootpass");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    http_response_code(500); // خطای داخلی سرور
    // لاگ کردن خطای اتصال به دیتابیس: error_log("Database Connection Error: " . $e->getMessage());
    echo json_encode(["message" => "Database connection failed. Please try again later."]);
    exit;
}

// ۵. دریافت و اعتبارسنجی اولیه داده‌های ورودی
$poll_id = $_POST["poll_id"] ?? null;
$option_id = $_POST["option_id"] ?? null;
$user_id = $_POST["user_id"] ?? null;

if (empty($poll_id) || empty($option_id) || empty($user_id)) {
    http_response_code(400); // Bad Request
    echo json_encode(["message" => "Missing required fields: poll_id, option_id, or user_id."]);
    exit;
}

try {
    // ۶. بررسی اینکه آیا کاربر قبلاً در این نظرسنجی رأی داده است یا خیر
    $stmtSelect = $pdo->prepare("SELECT id FROM votes WHERE poll_id = :poll_id AND user_id = :user_id");
    $stmtSelect->bindParam(':poll_id', $poll_id, PDO::PARAM_INT);
    $stmtSelect->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmtSelect->execute();

    if ($stmtSelect->fetch()) {
        http_response_code(409); // Conflict - کاربر قبلاً رأی داده است
        echo json_encode(["message" => "You have already voted in this poll."]);
    } else {
        // ۷. اگر کاربر قبلاً رأی نداده، رأی جدید را ثبت کن
        $stmtInsert = $pdo->prepare("INSERT INTO votes (poll_id, option_id, user_id) VALUES (:poll_id, :option_id, :user_id)");
        $stmtInsert->bindParam(':poll_id', $poll_id, PDO::PARAM_INT);
        $stmtInsert->bindParam(':option_id', $option_id, PDO::PARAM_INT);
        $stmtInsert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        // اجرای کوئری INSERT و بررسی موفقیت آن
        if ($stmtInsert->execute()) {
            http_response_code(201); // Created - رأی با موفقیت ثبت شد
            echo json_encode(["message" => "Vote submitted successfully."]);
        } else {
            // این بخش در حالت PDO::ERRMODE_EXCEPTION معمولاً اجرا نمی‌شود چون exception رخ می‌دهد
            // اما برای اطمینان بیشتر و حالت‌های دیگر PDO error mode می‌توان آن را داشت.
            http_response_code(500); // خطای داخلی سرور
            // لاگ کردن خطا: error_log("Failed to execute INSERT statement for voting.");
            echo json_encode(["message" => "Failed to submit vote due to a server error."]);
        }
    }
} catch (PDOException $e) {
    http_response_code(500); // خطای داخلی سرور (معمولاً خطای SQL)
    
    echo json_encode(["message" => "An error occurred while processing your vote. Please check the data or try again later. Error: " . $e->getMessage()]); // نمایش موقت پیام خطا برای دیباگ
}
?>