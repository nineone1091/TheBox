<?php
header("Content-Type: application/json");
date_default_timezone_set('Asia/Taipei');

function respond($state, $message, $data = null)
{
    echo json_encode([
        "state" => $state,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

function get_json_input()
{
    $data = file_get_contents("php://input");
    $input = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        respond(false, "JSON 格式錯誤：" . json_last_error_msg());
    }
    return $input;
}

function create_connection()
{
    $host = "localhost";
    $username = "owner01";
    $password = "123456";
    $database = "TheBox";

    $conn = mysqli_connect($host, $username, $password, $database);
    $conn->query("SET time_zone = 'Asia/Taipei'");
    if (!$conn) {
        respond(false, "資料庫連線失敗：" . mysqli_connect_error());
    }
    return $conn;
}

$conn = create_connection();
$action = strtolower(trim($_GET['action'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'user_info') {
        $uid = $_GET['uid'] ?? '';
        $result = $conn->query("SELECT id, Username, Level FROM member WHERE Uid01 = '$uid'");
        if ($user = $result->fetch_assoc()) {
            $user["isAdmin"] = ($user["Level"] == 100);
            respond(true, "用戶資訊", $user);
        } else {
            respond(false, "找不到用戶");
        }
    } elseif ($action === 'get_comments') {
        $commentsResult = $conn->query("SELECT c.id, c.title, c.content, m.Username, c.created_at
                                        FROM comments c
                                        JOIN member m ON c.user_id = m.id");
        $comments = $commentsResult->fetch_all(MYSQLI_ASSOC);

        foreach ($comments as &$comment) {
            $repliesResult = $conn->query("SELECT * FROM replies WHERE comment_id = " . $comment['id']);
            $comment['replies'] = $repliesResult->fetch_all(MYSQLI_ASSOC);
        }

        respond(true, "留言資料", $comments);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add_comment') {
        $input = get_json_input();
        $title = $conn->real_escape_string($input['title']);
        $content = $conn->real_escape_string($input['content']);
        $uid = $_COOKIE['Uid01'] ?? '';

        if (!$uid) {
            respond(false, "請先登入");
        }

        $userResult = $conn->query("SELECT id, Username FROM member WHERE Uid01 = '$uid'");
        if ($userResult->num_rows === 0) {
            respond(false, "無效的用戶");
        }
        $user = $userResult->fetch_assoc();
        $userId = $user['id'];
        $username = $user['Username'];

        $query = "INSERT INTO comments (user_id, title, content, created_at) VALUES ('$userId', '$title', '$content', NOW())";
        if ($conn->query($query)) {
            respond(true, "留言成功", [
                "id" => $conn->insert_id,
                "title" => $title,
                "content" => $content,
                "created_at" => date("Y-m-d H:i:s"),
                "username" => $username
            ]);
        } else {
            respond(false, "留言失敗：" . $conn->error);
        }
    } elseif ($action === 'reply_comment') {
        $input = get_json_input();
        $commentId = $input['comment_id'] ?? null;
        $replyContent = $input['reply'] ?? null;
        $uid = $input['uid'] ?? '';

        if (!$commentId || !$replyContent || !$uid) {
            respond(false, "缺少必要參數");
        }

        // 確認使用者是管理員
        $adminCheck = $conn->query("SELECT id, Level,Username FROM member WHERE Uid01 = '$uid'");
        $admin = $adminCheck->fetch_assoc();

        if (!$admin || $admin['Level'] != 100) {
            respond(false, "沒有管理員權限");
        }

        $adminId = $admin['id'];
        $adminUsername = $admin['Username'];  // 取得管理員的 Username
        if (!$adminUsername) {
            respond(false, "無法獲取管理員的用戶名");
        }

        // 插入回覆
        $createdAt = date("Y-m-d H:i:s");
        $stmt = $conn->prepare("INSERT INTO replies (comment_id, content, user_id, username, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $commentId, $replyContent, $adminId, $adminUsername,$createdAt);
        if ($stmt->execute()) {
            respond(true, "回覆成功", [
                "newReplyId" => $conn->insert_id,
                "created_at" => $createdAt  // 確保回傳時間
            ]);
        } else {
            respond(false, "回覆失敗：" . $stmt->error);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'delete_comment') {
    $id = $_GET['id'] ?? '';
    $conn->query("DELETE FROM comments WHERE id = $id");
    respond(true, "留言已刪除");
} else {
    respond(false, "無效的操作");
}
