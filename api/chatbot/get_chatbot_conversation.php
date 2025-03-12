<?php
require_once '../../config.php';

$conversationId = $_GET['conversation_id'] ?? null;

if (!$conversationId) {
    exit(json_encode(['error' => '대화 ID가 필요합니다.']));
}

$stmt = $pdo->prepare("
    SELECT 
        c.question as content,
        'user' as role,
        c.no,
        c.file_metadata,
        c.created_at
    FROM chatbot c
    WHERE c.conversation_id = ?
    UNION ALL
    SELECT 
        c.answer as content,
        'bot' as role,
        c.no,
        NULL as file_metadata,
        c.created_at
    FROM chatbot c
    WHERE c.conversation_id = ?
    ORDER BY no, role DESC
");

$stmt->execute([$conversationId, $conversationId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($messages);