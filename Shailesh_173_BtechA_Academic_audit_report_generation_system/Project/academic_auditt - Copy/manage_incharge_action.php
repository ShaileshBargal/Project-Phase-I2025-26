<?php
session_start();
require_once "db.php";
if(!isset($_SESSION['head_id']) || ($_SESSION['role'] ?? '') !== 'head'){
    exit(json_encode(['ok'=>false,'msg'=>'Unauthorized']));
}

header('Content-Type: application/json; charset=utf-8');

// Ensure supporting table exists
$conn->query("
CREATE TABLE IF NOT EXISTS incharge_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role_name VARCHAR(191) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_map (user_id, role_name),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$action = $_POST['action'] ?? '';

switch($action){

    case 'add_role':
        $role = trim($_POST['role'] ?? '');
        if(!$role){ echo json_encode(['ok'=>false,'msg'=>'Role cannot be empty']); exit; }
        $stmt = $conn->prepare("INSERT IGNORE INTO incharge_roles (user_id, role_name) VALUES (0, ?)");
        $stmt->bind_param("s",$role); $stmt->execute();
        echo json_encode(['ok'=>true,'role_name'=>$role]); exit;

    case 'add_incharge':
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $role_name = trim($_POST['role_name'] ?? '');
        if(!$username){ echo json_encode(['ok'=>false,'msg'=>'Name required']); exit; }

        $password_hash = password_hash('teacher123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username,password,role,Email,mobile_no,created_at) VALUES (?,?,?,?,?,NOW())");
        $stmt->bind_param("sssss",$username,$password_hash,$role_name,$email,$mobile);
        $stmt->execute();
        $user_id = $stmt->insert_id;

        if($role_name){
            $stmt2 = $conn->prepare("INSERT IGNORE INTO incharge_roles (user_id, role_name) VALUES (?,?)");
            $stmt2->bind_param("is",$user_id,$role_name); $stmt2->execute();
        }
        echo json_encode(['ok'=>true,'username'=>$username,'password'=>'teacher123']); exit;

    case 'assign_role':
        $user_id = intval($_POST['user_id'] ?? 0);
        $role_name = trim($_POST['role_name'] ?? '');
        if(!$user_id || !$role_name){ echo json_encode(['ok'=>false]); exit; }
        $stmt = $conn->prepare("INSERT IGNORE INTO incharge_roles (user_id, role_name) VALUES (?,?)");
        $stmt->bind_param("is",$user_id,$role_name); $stmt->execute();
        echo json_encode(['ok'=>true]); exit;

    case 'remove_role':
        $map_id = intval($_POST['map_id'] ?? 0);
        if(!$map_id){ echo json_encode(['ok'=>false]); exit; }
        $stmt = $conn->prepare("DELETE FROM incharge_roles WHERE id=?");
        $stmt->bind_param("i",$map_id); $stmt->execute();
        echo json_encode(['ok'=>true]); exit;

    case 'remove_incharge':
        $user_id = intval($_POST['user_id'] ?? 0);
        if(!$user_id){ echo json_encode(['ok'=>false]); exit; }
        $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='incharge'");
        $stmt->bind_param("i",$user_id); $stmt->execute();
        echo json_encode(['ok'=>true]); exit;

    case 'fetch_all':
        $users=[]; $ures = $conn->query("SELECT id, username, Email, mobile_no FROM users WHERE role='incharge' ORDER BY username ASC");
        while($row=$ures->fetch_assoc()) $users[]=$row;

        $maps=[]; $mres=$conn->query("SELECT ir.id AS map_id,u.id AS user_id,u.username,ir.role_name FROM incharge_roles ir JOIN users u ON u.id=ir.user_id ORDER BY u.username, ir.role_name");
        while($row=$mres->fetch_assoc()) $maps[]=$row;

        $roles=[]; $rres=$conn->query("SELECT DISTINCT role_name FROM incharge_roles ORDER BY role_name ASC");
        while($row=$rres->fetch_assoc()) $roles[]=$row;

        echo json_encode(['ok'=>true,'users'=>$users,'maps'=>$maps,'roles'=>$roles]); exit;

    default: echo json_encode(['ok'=>false,'msg'=>'Unknown action']); exit;
}
