<?php
if (session_status() === PHP_SESSION_NONE) session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !hash_equals(csrfToken(), $data['csrf_token'] ?? '')) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }

$projectId = (int)($data['project_id'] ?? 0);
$order     = $data['order'] ?? [];

if (!$projectId || !is_array($order)) { echo json_encode(['ok'=>false]); exit; }

$st = db()->prepare('UPDATE media SET display_order = ? WHERE id = ? AND project_id = ?');
db()->beginTransaction();
foreach ($order as $item) {
    $st->execute([(int)$item['order'], (int)$item['id'], $projectId]);
}
db()->commit();

echo json_encode(['ok' => true]);
