<?php
// 에러 표시 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../config.php';

class ChatbotAPI {
    private $pdo;
    private $apiKey;
    private $customInstructions;
    
    public function __construct($pdo, $apiKey) {
        $this->pdo = $pdo;
        $this->apiKey = $apiKey;
        $this->customInstructions = "회생 파산 법률 상담 AI 어시스턴트입니다. 법적 조언이나 상담을 제공할 수 있으며, 실제 변호사와의 상담이 필요한 경우 이를 권장합니다.";
    }

    public function processRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->sendError('잘못된 요청 방식입니다.');
        }

        // 피드백 처리
        if (isset($_POST['feedback'])) {
            return $this->processFeedback();
        }

        // 메시지 처리
        $member = $_POST['member'] ?? null;
        $message = $_POST['message'] ?? '';
        $conversationId = $_POST['conversation_id'] ?? '';

        if (!$member) {
            return $this->sendError('사용자 정보가 필요합니다.');
        }

        if (empty($message) && empty($_FILES['file'])) {
            return $this->sendError('메시지 또는 파일이 필요합니다.');
        }

        // 새 대화일 경우 conversation_id 생성
        if (empty($conversationId)) {
            $conversationId = $this->generateConversationId($member);
        }

        return $this->processMessage($member, $message, $conversationId);
    }

    private function processMessage($member, $message, $conversationId) {
        $fileMetadata = null;
        
        // 파일 처리
        if (!empty($_FILES['file'])) {
            $file = $_FILES['file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $fileMetadata = json_encode([
                    'name' => $file['name'],
                    'type' => $file['type'],
                    'size' => $file['size']
                ]);

                if ($file['type'] === 'application/pdf') {
                    $pdfText = $this->extractPdfText($file['tmp_name']);
                    $message .= "\n\nPDF 내용:\n" . $pdfText;
                }
            }
        }

        // 이전 대화 내역 가져오기
        $history = $this->getConversationHistory($conversationId);
        
        // Claude API 호출
        $answer = $this->getClaudeResponse($message, $history);
        
        // 대화 저장
        $chatId = $this->saveChat($conversationId, $member, $message, $answer, $fileMetadata);

        return [
            'conversation_id' => $conversationId,
            'answer' => $answer,
            'chat_id' => $chatId
        ];
    }

    private function processFeedback() {
        $chatId = $_POST['chat_id'] ?? null;
        $feedback = $_POST['feedback'] ?? null;

        if (!$chatId || !$feedback) {
            return $this->sendError('피드백 정보가 부족합니다.');
        }

        $stmt = $this->pdo->prepare("UPDATE chatbot SET feedback = ? WHERE no = ?");
        $success = $stmt->execute([$feedback, $chatId]);

        return ['success' => $success];
    }

    private function generateConversationId($member) {
        return $member . '_' . time() . '_' . mt_rand(1000, 9999);
    }

    private function getConversationHistory($conversationId) {
        $stmt = $this->pdo->prepare("
            SELECT question, answer 
            FROM chatbot 
            WHERE conversation_id = ? 
            ORDER BY no ASC 
            LIMIT 10
        ");
        $stmt->execute([$conversationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function extractPdfText($filePath) {
        // PDF 텍스트 추출 로직 구현
        // 실제 구현에서는 적절한 PDF 라이브러리 사용 필요
        return "PDF 텍스트 추출은 별도 구현이 필요합니다.";
    }

    private function getClaudeResponse($message, $history) {
        $url = 'https://api.anthropic.com/v1/messages';
        $messages = [];
        
        // 이전 대화 내역 포맷팅
        foreach ($history as $chat) {
            $messages[] = ['role' => 'user', 'content' => $chat['question']];
            $messages[] = ['role' => 'assistant', 'content' => $chat['answer']];
        }
        
        // 현재 메시지 추가
        $messages[] = ['role' => 'user', 'content' => $message];

        $data = [
            'model' => 'claude-3-5-sonnet-20241022',
            'messages' => $messages,
            'max_tokens' => 4096,
            'temperature' => 0.7,
            'system' => $this->customInstructions
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'anthropic-version: 2023-06-01',
                'x-api-key: ' . $this->apiKey
            ]
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return "API 오류: " . $error;
        }

        $result = json_decode($response, true);
        $answer = $result['content'][0]['text'] ?? "응답 처리 중 오류가 발생했습니다.";
        
        return nl2br(htmlspecialchars($answer));
    }

    private function saveChat($conversationId, $member, $question, $answer, $fileMetadata = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO chatbot (
                conversation_id, 
                member, 
                question, 
                answer, 
                file_metadata
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $conversationId,
            $member,
            $question,
            $answer,
            $fileMetadata
        ]);

        return $this->pdo->lastInsertId();
    }

    private function sendError($message) {
        return ['error' => $message];
    }
}

// API 인스턴스 생성 및 실행
$chatbot = new ChatbotAPI($pdo, CLAUDE_API_KEY);
$result = $chatbot->processRequest();

// JSON 응답 전송
header('Content-Type: application/json');
echo json_encode($result);