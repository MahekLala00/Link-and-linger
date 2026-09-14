<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_config.php';

$sender_id = $_SESSION['user_id'];
$receiver_id = $_GET['id'];

try {

    // Prevent sending request to yourself
    if ($sender_id == $receiver_id) {
        header("Location: linkpg.php");
        exit();
    }

    // Check if request already exists
    $stmt = $pdo->prepare("
        SELECT *
        FROM link_requests
        WHERE sender_id = ?
        AND receiver_id = ?
    ");

    $stmt->execute([$sender_id, $receiver_id]);

    if (!$stmt->fetch()) {

        $stmt = $pdo->prepare("
            INSERT INTO link_requests
            (sender_id, receiver_id)
            VALUES (?, ?)
        ");

        $stmt->execute([$sender_id, $receiver_id]);
    }

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

header("Location: linkpg.php");
exit();