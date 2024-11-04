<?php
session_start();
require_once 'config.php';

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '未授权的访问']);
    exit;
}

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $newToken = $input['token'] ?? '';
    
    if (empty($newToken)) {
        throw new Exception('Token不能为空');
    }
    
    if (updateToken($newToken)) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('更新Token失败');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 