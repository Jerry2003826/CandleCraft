<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Core\Configure;
use Cake\Http\Response;

class AiAssistantController extends AppController
{
    public function index(): void
    {
        $this->set('title', 'AI Assistant');
    }

    public function ask(): ?Response
    {
        $this->request->allowMethod(['post']);

        $question = trim((string)$this->request->getData('question'));
        if ($question === '') {
            $this->Flash->error(__('Please enter a question.'));
            return $this->redirect(['action' => 'index']);
        }

        $apiKey = Configure::read('Deepseek.api_key');
        $baseUrl = Configure::read('Deepseek.base_url', 'https://api.deepseek.com/v1');

        $systemPrompt = "You are an administrative assistant for CandleCraft Academy, a pottery and knitting school. "
            . "You help with common questions about courses, bookings, schedules, and general inquiries. "
            . "Be concise, professional, and helpful. If you don't know specific details, suggest the admin check the relevant section of the system.";

        $response = $this->callAiApi($baseUrl, $apiKey, $systemPrompt, $question);

        $this->set(compact('question', 'response'));
        $this->set('title', 'AI Assistant');

        return null;
    }

    private function callAiApi(string $baseUrl, string $apiKey, string $systemPrompt, string $question): string
    {
        if (empty($apiKey)) {
            return 'AI service is not configured. Please set the Deepseek.api_key in your configuration.';
        }

        $payload = json_encode([
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $question],
            ],
            'max_tokens' => 1000,
            'temperature' => 0.7,
        ]);

        $ch = curl_init("{$baseUrl}/chat/completions");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result === false) {
            return 'Failed to connect to AI service. Please try again.';
        }

        $data = json_decode($result, true);
        if ($httpCode !== 200 || isset($data['error'])) {
            $errorMsg = $data['error']['message'] ?? 'Unknown error';
            return "AI service error: {$errorMsg}";
        }

        return $data['choices'][0]['message']['content'] ?? 'No response from AI service.';
    }
}
