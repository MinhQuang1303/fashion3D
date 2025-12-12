<?php
// api/thong_bao_thoi_gian_thuc.php
require_once __DIR__.'/../includes/ket_noi_db.php';
require_once __DIR__.'/../includes/ham_chung.php';
if(!isAdmin()){ echo json_encode(['success'=>false]); exit; }
$user_id = (int)($_POST['user_id'] ?? 0);
$title = $_POST['title'] ?? 'Thông báo';
$msg = $_POST['message'] ?? '';
if(!$user_id || !$msg){ echo json_encode(['success'=>false,'msg'=>'Thiếu dữ liệu']); exit; }
$pdo->prepare("INSERT INTO Notifications (user_id,title,message,type) VALUES(?,?,?,?)")->execute([$user_id,$title,$msg,'system']);
echo json_encode(['success'=>true]);
