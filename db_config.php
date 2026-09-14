<?php
$host     = 'sql301.infinityfree.com';
$db       = 'if0_42108721_linkandlinger_db';
$user     = 'if0_42108721';
$password = 'Maheklala';
$charset  = 'utf8mb4';
$port     = '3306';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $password, $options);
     // Connection is silent on success now
} catch (\PDOException $e) {
     die("Database connection failed: " . $e->getMessage());
}
?>