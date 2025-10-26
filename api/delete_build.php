<?php
require_once __DIR__ . '/../db.php';
$pdo = getPDO();
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
$build_id = $data['build_id'] ?? 0;

if (!$build_id) {
    echo json_encode(['success'=>false,'error'=>'Thiếu ID cấu hình']);
    exit;
}

try {
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM build_items WHERE build_id = ?")->execute([$build_id]);
    $pdo->prepare("DELETE FROM builds WHERE build_id = ?")->execute([$build_id]);
    $pdo->commit();
    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
