<?php
header("Content-Type: application/json");
$pdo = new PDO("mysql:host=mysql;dbname=voting", "root", "rootpass");

$action = $_GET["action"] ?? null;

if ($action === "create") {
    $question = $_POST["question"];
    $user_id = $_POST["user_id"];
    $options = $_POST["options"]; // comma-separated

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO polls (question, created_by) VALUES (?, ?)");
    $stmt->execute([$question, $user_id]);
    $poll_id = $pdo->lastInsertId();

    foreach (explode(",", $options) as $opt) {
        $stmt = $pdo->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
        $stmt->execute([$poll_id, trim($opt)]);
    }
    $pdo->commit();

    echo json_encode(["message" => "Poll created", "poll_id" => $poll_id]);
} elseif ($action === "list") {
    $polls = $pdo->query("SELECT * FROM polls ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($polls);
}