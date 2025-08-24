<?php
header("Content-Type: application/json");
$pdo = new PDO("mysql:host=mysql;dbname=voting", "root", "rootpass");

$action = $_GET["action"] ?? null;

if ($action === "register") {
    $username = $_POST["username"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $password]);
    echo json_encode(["message" => "User registered"]);
} elseif ($action === "login") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user["password"])) {
        echo json_encode(["message" => "Login successful", "user_id" => $user["id"]]);
    } else {
        echo json_encode(["message" => "Login failed"]);
    }
}
