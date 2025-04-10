<?php
$pdo = new PDO("mysql:host=localhost;dbname=TheBox;charset=utf8mb4", "owner01", "123456", [
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
]);

// 定義分類對應
$categories = [
    1 => "歌回",
    2 => "雜談",
    3 => "遊戲",
    4 => "Cover",
    5 => "特別企劃",
    6 => "ASMR",
    7 => "料理"
];

// 接收篩選條件
$category = isset($_GET['category']) ? (int)$_GET['category'] : null;
$year = isset($_GET['year']) ? $_GET['year'] : null;
$month = isset($_GET['month']) ? $_GET['month'] : null;
$person = isset($_GET['person']) ?$_GET['person'] : null;
$member_only = isset($_GET['member_only']) ? (int)$_GET['member_only'] : null;
$general_only = isset($_GET['general_only']) ? (int)$_GET['general_only'] : null;
$sortOrder = isset($_GET['sortOrder']) && strtoupper($_GET['sortOrder']) === "ASC" ? "ASC" : "DESC"; // 預設 DESC

$query = "SELECT * FROM stream WHERE 1";
$params = [];

if ($category) {
    $query .= " AND category = ?";
    $params[] = $category;
}
if ($year && $month) {
    $query .= " AND YEAR(upload_date) = ? AND MONTH(upload_date) = ?";
    array_push($params, $year, $month);
}
if ($person) {
    $query .= " AND person = ?";
    $params[] = $person;
}
if ($member_only && $general_only) {
    $query .= " AND member_only IN (1, 2)";  // 同時顯示一般影片與會員限定
} elseif ($member_only) {
    $query .= " AND member_only = 2";  // 只顯示會員限定影片
} elseif ($general_only) {
    $query .= " AND member_only = 1";  // 只顯示一般影片
}

// **當兩個都沒勾選時，不加入篩選條件 (顯示所有影片)**
if (!$member_only && !$general_only) {
    // 不做任何篩選，讓所有影片都顯示
}

$query .= " ORDER BY upload_date $sortOrder";  

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($videos);
?>
