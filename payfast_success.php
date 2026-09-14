<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$idea_id = 0;

if (isset($_POST['custom_str1'])) {
    $idea_id = intval($_POST['custom_str1']);
} elseif (isset($_GET['id'])) {
    $idea_id = intval($_GET['id']);
}

if ($idea_id <= 0) {
    header("Location: linger.php");
    exit();
}

try {
    $check = $pdo->prepare("SELECT * FROM purchased_ideas WHERE user_id = ? AND idea_id = ?");
    $check->execute([$user_id, $idea_id]);
    
    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO purchased_ideas (user_id, idea_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $idea_id]);
    }
    
    header("Location: linger.php?unlocked=1");
    exit();

} catch (PDOException $e) {
    die("Error processing successful purchase mapping: " . $e->getMessage());
}
?>