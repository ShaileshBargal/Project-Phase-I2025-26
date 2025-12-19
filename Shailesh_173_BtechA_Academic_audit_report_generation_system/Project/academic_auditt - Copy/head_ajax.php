<?php
// head_ajax.php — AJAX endpoint for head.php
session_start();
require_once "db.php";
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['head_id']) || ($_SESSION['role'] ?? '') !== 'head') {
    echo json_encode(['ok'=>false,'msg'=>'Not authorized']); exit();
}
$action = $_POST['action'] ?? '';

function j($ok,$msg='', $data=null){ echo json_encode(array_filter(['ok'=>$ok,'msg'=>$msg,'data'=>$data], function($v){return $v!==null;})); exit(); }

if ($action === 'send_request') {
    $head_id = (int)$_SESSION['head_id'];
    $incharge_id = (int)($_POST['incharge_id'] ?? 0);
    $incharge_name = trim($_POST['incharge_name'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $requirement = trim($_POST['requirement'] ?? '');
    if ($requirement === '' || ($incharge_id<=0 && $incharge_name==='')) j(false,'Missing fields');
    $stmt = $conn->prepare("INSERT INTO audit_requests (head_id, incharge_id, incharge_name, role, requirement, status, requested_at) VALUES (?, ?, ?, ?, ?, 'sent', NOW())");
    $stmt->bind_param("iisss", $head_id, $incharge_id, $incharge_name, $role, $requirement);
    if ($stmt->execute()) {
        // optional: log activity
        if ($conn->query("SHOW TABLES LIKE 'activity_logs'")->num_rows) {
            $ins = $conn->prepare("INSERT INTO activity_logs (action_type, details) VALUES (?, ?)");
            $details = "Head {$head_id} sent request '{$requirement}' to {$incharge_name} ({$role})";
            $ins->bind_param("ss", $action, $details);
            @$ins->execute();
        }
        j(true,'Request sent');
    } else j(false,'DB error: '.$conn->error);
}

// rename role
if ($action === 'rename_role') {
    $rid = (int)($_POST['rid'] ?? 0);
    $new = trim($_POST['new_name'] ?? '');
    if ($rid<=0 || $new==='') j(false,'Missing fields');
    $upd = $conn->prepare("UPDATE incharge_roles SET role = ? WHERE id = ?");
    $upd->bind_param("si", $new, $rid);
    if ($upd->execute()) {
        // optional: log
        $stmt = $conn->prepare("INSERT INTO activity_logs (action_type, details) VALUES (?, ?)");
        @$stmt->bind_param("ss", $action, $new);
        @$stmt->execute();
        j(true,'Role renamed');
    } else j(false,'DB error: '.$conn->error);
}

// get history by incharge id
if ($action === 'get_history') {
    $uid = (int)($_POST['incharge_id'] ?? 0);
    if ($uid<=0) j(false,'Missing id');
    $rs = $conn->prepare("SELECT requirement, status, requested_at FROM audit_requests WHERE incharge_id = ? ORDER BY requested_at DESC LIMIT 200");
    $rs->bind_param("i",$uid); $rs->execute();
    $res = $rs->get_result(); $arr = [];
    while($row = $res->fetch_assoc()) $arr[] = $row;
    j(true,'OK',$arr);
}

// edit user (username/email)
if ($action === 'edit_user') {
    $uid = (int)($_POST['user_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($uid<=0 || $username==='') j(false,'Missing fields');
    $up = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    $up->bind_param("ssi", $username, $email, $uid);
    if ($up->execute()) {
        j(true,'User updated');
    } else j(false,'DB error: '.$conn->error);
}

// add role (AJAX)
if ($action === 'add_role') {
    $uid = (int)($_POST['user_id'] ?? 0);
    $role = trim($_POST['role'] ?? '');
    if ($uid<=0 || $role==='') j(false,'Missing');
    $check = $conn->prepare("SELECT id FROM incharge_roles WHERE user_id = ? AND role = ?");
    $check->bind_param("is",$uid,$role); $check->execute(); $check->store_result();
    if ($check->num_rows>0) j(false,'Already assigned');
    $ins = $conn->prepare("INSERT INTO incharge_roles (user_id, role) VALUES (?, ?)");
    $ins->bind_param("is",$uid,$role);
    if ($ins->execute()) j(true,'Role assigned');
    else j(false,'DB error: '.$conn->error);
}

// remove role (AJAX)
if ($action === 'remove_role') {
    $rid = (int)($_POST['rid'] ?? 0);
    if ($rid<=0) j(false,'Missing rid');
    $del = $conn->prepare("DELETE FROM incharge_roles WHERE id = ?");
    $del->bind_param("i",$rid);
    if ($del->execute()) j(true,'Removed');
    else j(false,'DB error: '.$conn->error);
}

j(false,'Unknown action');
