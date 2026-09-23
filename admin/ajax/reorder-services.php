<?php
if (session_status() === PHP_SESSION_NONE) session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
require_once __DIR__ . '/../../includes/functions.php';
header('Content-Type: application/json');
if (empty($_SESSION['admin_id'])) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !hash_equals(csrfToken(), $data['csrf_token'] ?? '')) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }
$st = db()->prepare('UPDATE services SET display_order = ? WHERE id = ?');
db()->beginTransaction();
foreach ($data['order'] ?? [] as $item) $st->execute([(int)$item['order'], (int)$item['id']]);
db()->commit();
echo json_encode(['ok' => true]);
