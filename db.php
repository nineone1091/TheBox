<?php
$host = 'localhost';
$db   = 'TheBox'; // 你的資料庫名稱
$user = 'owner01';   // 你的 MySQL 使用者名稱
$pass = '123456';       // 你的 MySQL 密碼
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("資料庫連線失敗：" . $e->getMessage());
}
?>
