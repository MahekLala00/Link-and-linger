<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_config.php';

$request_id = $_GET['id'];

try {

    $stmt = $pdo->prepare("
        UPDATE link_requests
        SET status = 'Rejected'
        WHERE request_id = ?
    ");

    $stmt->execute([$request_id]);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

header("Location: notifications.php");
exit();