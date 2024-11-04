<?php
session_start();
require_once 'config.php';

if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => '请先登录']);
    exit;
}

header('Content-Type: application/json');

$historyFile = 'uploads/history.json';
if (file_exists($historyFile)) {
    $history = json_decode(file_get_contents($historyFile), true) ?? [];
    echo json_encode($history);
} else {
    echo json_encode([]);
} 