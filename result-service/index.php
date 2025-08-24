<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

// ۲. اتصال PDO و فعال کردن Exception ها برای مدیریت بهتر خطا (اطلاعات اتصال را بررسی کنید)
try {
    // اطلاعات اتصال خود را جایگزین کنید اگر متفاوت است
    // اگر PHP و MySQL در کانتینرهای جداگانه هستند، به جای 127.0.0.1 از نام سرویس MySQL (مثلاً mysql) استفاده کنید.
    $pdo = new PDO("mysql:host=mysql;dbname=voting", "root", "rootpass"); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    // error_log("Database Connection Error in result-service: " . $e->getMessage());
    echo json_encode(["message" => "Database connection failed."]);
    exit;
}

$poll_id = $_GET["poll_id"] ?? null; // استفاده از null coalescing operator برای پیش‌فرض

if (empty($poll_id)) {
    http_response_code(400);
    echo json_encode(["message" => "Poll ID is required."]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            po.id,  -- <--- این خط اضافه شده و بسیار مهم است! شناسه گزینه را انتخاب می‌کند
            po.option_text, 
            COUNT(v.id) AS votes
        FROM 
            poll_options po
        LEFT JOIN 
            votes v ON po.id = v.option_id
        WHERE 
            po.poll_id = :poll_id -- استفاده از named placeholder
        GROUP BY 
            po.id, po.option_text -- اضافه کردن همه ستون‌های غیر تجمعی به GROUP BY
        ORDER BY 
            po.id ASC -- مرتب‌سازی اختیاری اما خوب
    ");
    $stmt->bindParam(':poll_id', $poll_id, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);

} catch (PDOException $e) {
    http_response_code(500);
    // error_log("SQL Error in result-service for poll_id $poll_id: " . $e->getMessage());
    echo json_encode(["message" => "Error fetching results. SQL Error: " . $e->getMessage()]); // موقتاً برای دیباگ
    exit;
}
?>