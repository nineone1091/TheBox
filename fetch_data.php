<?php
require 'db.php';

$month = $_GET['month'] ?? '';

// 建立 SQL 查詢
if ($month) {
    $sql = "SELECT tab_name, SUM(click_count) as total_clicks FROM clicks WHERE DATE_FORMAT(click_date, '%Y-%m') = ? GROUP BY tab_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$month]);
} else {
    $sql = "SELECT tab_name, SUM(click_count) as total_clicks FROM clicks GROUP BY tab_name";
    $stmt = $pdo->query($sql);
}

$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data[$row['tab_name']] = $row['total_clicks'];
}

echo json_encode($data);
?>
