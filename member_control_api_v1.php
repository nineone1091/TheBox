<?php
function respond($state, $message, $data = null) {
    echo json_encode([
        "state" => $state,
        "message" => $message,
        "data" => $data
    ]);
    exit; // 確保響應後結束執行
}

function get_json_input() {
    // 從 php://input 獲取原始輸入資料
    $data = file_get_contents('php://input');
    
    // 將資料解碼為 PHP 陣列
    $input = json_decode($data, true);
    
    // 如果解碼失敗，返回錯誤
    if (json_last_error() !== JSON_ERROR_NONE) {
        respond(false, "JSON 格式錯誤：" . json_last_error_msg());
    }

    return $input;
}

function create_connection() {
    // 設定資料庫連線資訊
    $host = "localhost";
    $username = "owner01";
    $password = "123456";
    $database = "TheBox";

    // 建立連線
    $conn = mysqli_connect($host, $username, $password, $database);

    // 檢查連線是否成功
    if (!$conn) {
        respond(false, "資料庫連線失敗：" . mysqli_connect_error());
    }

    return $conn;
}

function register_user(){
    $input = get_json_input();
    if (isset($input["username"], $input["password"], $input["email"])) {
        $p_username = trim($input["username"]);
        $p_password = password_hash(trim($input["password"]), PASSWORD_DEFAULT);
        $p_email = trim($input["email"]);
        //trim ->移除空白

        if ($p_username && $p_password && $p_email) {

            $conn = create_connection();

            $stmt = $conn->prepare("INSERT INTO member (Username, Password, Email) VALUES (?,?,?)");
            $stmt->bind_param("sss", $p_username, $p_password, $p_email);

            if ($stmt->execute()) {
                respond(true,"註冊成功");
            } else {
                respond(false,"註冊失敗");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false,"欄位不得為空");
        }
    } else {
        respond(false,"欄位錯誤");
    }
}

function login_user(){
    $input = get_json_input();
    if (isset($input['username'], $input['password'])) {
        $p_username = trim($input['username']);
        $p_password = trim($input['password']);
        if ($p_username && $p_password) {

            $conn = create_connection();

            $stmt = $conn->prepare("SELECT * FROM member WHERE Username = ?");
            $stmt->bind_param("s", $p_username); // 一定要綁定變數
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                if (password_verify($p_password, $row["Password"])) {
                    // 設定 UID 的隨機生成  
                    $uid01 = substr(hash('sha256', time()), 10, 4) . substr(bin2hex(random_bytes(16)), 4, 4);
                    $update_stmt = $conn->prepare("UPDATE member SET Uid01 = ? WHERE Username = ?");
                    $update_stmt->bind_param('ss', $uid01, $p_username);
                    if ($update_stmt->execute()) {

                        // 取得會員的相關資訊  
                        $user_stmt = $conn->prepare("SELECT Username, Email, Uid01, Created_at FROM member WHERE Username = ?");
                        $user_stmt->bind_param('s', $p_username); // 參數綁定  
                        $user_stmt->execute();
                        $user_data = $user_stmt->get_result()->fetch_assoc();
                        respond(true,"登入成功",$user_data);

                    } else {
                        respond(false,"登入失敗，UID更新失敗");
                    }
                }
            } else {
                // 比對失敗
                respond(false,"登入失敗，密碼錯誤");
            }
        } else {
            respond(false,"登入失敗，該帳號不存在");
        }

        $stmt->close();
        $conn->close();
    } else {
        respond(false,"欄位不得為空");
    }
}

function check_uid(){
    $input = get_json_input();
    if (isset($input['uid01'])) {
        $p_uid = trim($input['uid01']);
        if ($p_uid) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT Username, Email, Uid01, Created_at FROM member WHERE Uid01 = ?");
            $stmt->bind_param("s", $p_uid); // 一定要綁定變數
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $userdata = $result->fetch_assoc();
                respond(true,"驗證成功",$userdata);
            } else {
                respond(false,"驗證失敗");
            }

            $stmt->close();
            $conn->close();
        } else {
            respond(false,"欄位不得為空");
        }
    } else {
        respond(false,"欄位錯誤");
    }
}

function register_uni(){
    $input = get_json_input();
    if (isset($input["username"])) {
        if ($input["username"] != "") {
            $p_username = $input["username"];
            $conn = create_connection();
    
            // 修正 SQL 語句，將變數嵌入查詢中
            $stmt = $conn->prepare("SELECT Username FROM member WHERE Username = ?");
            $stmt->bind_param("s", $p_username); // 一定要綁定變數
            $stmt->execute();
            $result = $stmt->get_result();
    
            if (mysqli_num_rows($result) > 0) {
                respond(false,"名稱已存在不得使用");
            } else {
                respond(true,"名稱不存在可以使用");
            }
    
            mysqli_close($conn);
        } else {
            respond(false,"欄位不得為空");
        }
    } else {
        respond(false,"欄位錯誤");
    }
}

function delete_user(){
    $input = get_json_input();
    if (isset($input["id"])) {
        $p_id=trim($input["id"]);
        //trim ->移除空白

        if ($p_id) {

            $conn = create_connection();

            $stmt = $conn->prepare("DELETE FROM member WHERE ID = ?");
            $stmt->bind_param("i",$p_id);

            if ($stmt->execute()) {
                if($stmt->affected_rows===1){
                    respond(true,"刪除成功");
                }else{
                    respond(false,"刪除失敗，無刪除行動");
                }
            } else {
                respond(false,"刪除失敗");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false,"欄位不得為空");
        }
    } else {
        respond(false,"欄位錯誤");
    }
}

function update_user(){
    $input = get_json_input();
    if (isset($input["id"],$input["email"])) {
        $p_id=trim($input["id"]);
        $p_email = trim($input["email"]);
        //trim ->移除空白

        if ($p_email && $p_id) {

            $conn = create_connection();

            $stmt = $conn->prepare("UPDATE member SET Email = ? WHERE ID = ?");
            $stmt->bind_param("si",$p_email,$p_id);

            if ($stmt->execute()) {
                if($stmt->affected_rows===1){
                    respond(true,"更新成功");
                }else{
                    respond(false,"更新失敗，無修改內容");
                }
            } else {
                respond(false,"更新失敗");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false,"欄位不得為空");
        }
    } else {
        respond(false,"欄位錯誤");
    }
}

function get_all_user_data(){
    $conn = create_connection();

    $stmt = $conn->prepare("SELECT * FROM member ORDER BY ID DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows >0){
        $mydata = array();
        while($row=$result->fetch_assoc()){
            unset($row["Password"]);
            unset($row["Uid01"]);
            $mydata[]=$row;
        }
        respond(true,"取得所有會員資料成功",$mydata);
    }else{
        respond(false,"查無資料");
    }
    $stmt->close();
    $conn->close();

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = strtolower(trim($_GET['action'] ?? ''));
    switch ($action) {
        case 'register':
            register_user();
            break;
        case 'login':
            login_user();
            break;
        case 'checkuid':
            check_uid();
            break;
        case 'register_uni':
            register_uni();
            break;
        case 'update':
            update_user();
            break;
        default:
            respond(false, "無效的操作: $action");
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = strtolower(trim($_GET['action'] ?? ''));
    if (empty($action)) {
        respond(false, "未提供有效的 action 參數");
    }

    switch ($action) {
        case 'getalldata':
            get_all_user_data();
            break;
        default:
            respond(false, "無效的操作: $action");
    }
}else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $action = strtolower(trim($_GET['action'] ?? ''));
    if (empty($action)) {
        respond(false, "未提供有效的 action 參數");
    }

    switch ($action) {
        case 'delete':
            delete_user();
            break;
        default:
            respond(false, "無效的操作: $action");
    }
} else {
    respond(false, "無效的請求方法");
}