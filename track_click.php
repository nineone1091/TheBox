<?php
require 'db.php'; // 連線資料庫

$tab = $_POST['tab'] ?? '';
if ($tab) {
    $date = date('Y-m-d');

    // 新增或更新當天點擊數
    $stmt = $pdo->prepare("INSERT INTO clicks (tab_name, click_date, click_count)
                           VALUES (?, ?, 1)
                           ON DUPLICATE KEY UPDATE click_count = click_count + 1");
    $stmt->execute([$tab, $date]);

    // 刪除超過一年的資料
    $delete_stmt = $pdo->prepare("DELETE FROM clicks WHERE click_date < DATE_SUB(CURDATE(), INTERVAL 1 YEAR)");
    $delete_stmt->execute();
}
?>
