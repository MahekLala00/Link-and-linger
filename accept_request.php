<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_config.php';

$request_id = $_GET['id'];

try {

    // Get request details
    $stmt = $pdo->prepare("
        SELECT *
        FROM link_requests
        WHERE request_id = ?
        AND status = 'Pending'
    ");

    $stmt->execute([$request_id]);

    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        header("Location: notifications.php");
        exit();
    }

    // Update request status
    $stmt = $pdo->prepare("
        UPDATE link_requests
        SET status = 'Accepted'
        WHERE request_id = ?
    ");

    $stmt->execute([$request_id]);

    // Create match
    $stmt = $pdo->prepare("
        INSERT INTO matches
        (user1_id, user2_id)
        VALUES (?, ?)
    ");

    $stmt->execute([
        $request['sender_id'],
        $request['receiver_id']
    ]);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

header("Location: notifications.php");
exit();