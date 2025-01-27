<?php
require_once '../../config.php';

$member = $_GET['member'] ?? null;

if (!$member) {
    exit(json_encode(['error' => '사용자 정보가 필요합니다.']));
}

$stmt = $pdo->prepare("
    SELECT DISTINCT c.conversation_id,
           COALESCE(NULLIF(c.question, ''), '(파일만 업로드됨)') as question,
           c.file_metadata,
           c.created_at
    FROM chatbot c
    WHERE c.member = ?
    GROUP BY c.conversation_id
    ORDER BY MAX(c.no) DESC
");

$stmt->execute([$member]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($conversations);