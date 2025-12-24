<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqAIService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.groq.com/openai/v1';
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('ai.groq_api_key', env('GROQ_API_KEY'));
        $this->model = config('ai.groq_model', 'llama-3.1-8b-instant');
    }

    /**
     * Chat with AI
     * 
     * @param array $messages Array of messages with role and content
     * @param array $context Optional context data
     * @return string
     */
    public function chat(array $messages, array $context = []): string
    {
        try {
            if (empty($this->apiKey)) {
                throw new \Exception('Groq API key chưa được cấu hình. Vui lòng thêm GROQ_API_KEY vào file .env');
            }

            // Build system message if context provided
            $systemMessages = [];
            if (!empty($context)) {
                $systemMessages[] = [
                    'role' => 'system',
                    'content' => $this->buildSystemPrompt($context)
                ];
            }

            $allMessages = array_merge($systemMessages, $messages);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $this->model,
                    'messages' => $allMessages,
                    'max_tokens' => 1000,
                    'temperature' => 0.7,
                ]);

            if ($response->failed()) {
                $error = $response->json();
                $errorMessage = $error['error']['message'] ?? 'Groq API error';
                Log::error('Groq API Error: ' . $errorMessage, ['response' => $error]);
                throw new \Exception($errorMessage);
            }

            $data = $response->json();
            
            if (!isset($data['choices'][0]['message']['content'])) {
                Log::error('Groq API: No content in response', ['data' => $data]);
                throw new \Exception('Không nhận được phản hồi từ AI. Vui lòng thử lại.');
            }

            $content = $data['choices'][0]['message']['content'];
            
            if (empty(trim($content))) {
                Log::warning('Groq API: Empty content response', ['data' => $data]);
                throw new \Exception('AI trả về phản hồi rỗng. Vui lòng thử lại.');
            }

            return $content;
        } catch (\Exception $e) {
            Log::error('Groq API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Build system prompt from context
     * 
     * @param array $context
     * @return string
     */
    protected function buildSystemPrompt(array $context): string
    {
        $prompt = "Bạn là AI Assistant cho hệ thống quản lý dự án. Bạn giúp người dùng quản lý projects, tasks, và team collaboration.\n\n";

        if (isset($context['user'])) {
            $user = $context['user'];
            $prompt .= "Người dùng: {$user->name} ({$user->email})\n";
        }

        // Add projects information if available
        $userProjects = $context['user_projects'] ?? [];
        $projectsCount = 0;
        if (is_array($userProjects)) {
            $projectsCount = count($userProjects);
        } elseif (is_object($userProjects) && method_exists($userProjects, 'count')) {
            $projectsCount = $userProjects->count();
        }
        
        if ($projectsCount > 0) {
            $projectsCount = $context['projects_count'] ?? $projectsCount;
            $prompt .= "\n=== THÔNG TIN DỰ ÁN CỦA NGƯỜI DÙNG ===\n";
            $prompt .= "Tổng số dự án: {$projectsCount}\n\n";
            
            // Convert to array if it's a Collection
            $projectsArray = is_array($userProjects) ? $userProjects : $userProjects->toArray();
            
            foreach ($projectsArray as $project) {
                $prompt .= "Dự án: {$project['name']}\n";
                $prompt .= "  - ID: {$project['id']}\n";
                $prompt .= "  - Trạng thái: {$project['status']}\n";
                $prompt .= "  - Tiến độ: {$project['progress_percentage']}% ({$project['completed_tasks']}/{$project['total_tasks']} tasks)\n";
                if ($project['start_date']) {
                    $prompt .= "  - Ngày bắt đầu: {$project['start_date']}\n";
                }
                if ($project['end_date']) {
                    $prompt .= "  - Ngày kết thúc: {$project['end_date']}\n";
                }
                if ($project['is_pinned']) {
                    $prompt .= "  - ⭐ Đã ghim\n";
                }
                $prompt .= "\n";
            }
            
            $prompt .= "Bạn có thể:\n";
            $prompt .= "- Phân tích số lượng dự án và tiến độ\n";
            $prompt .= "- Đưa ra nhận xét về tình trạng các dự án\n";
            $prompt .= "- Gợi ý cải thiện hoặc quản lý dự án\n";
            $prompt .= "- Trả lời câu hỏi về các dự án cụ thể\n";
        } else {
            $prompt .= "\nNgười dùng hiện chưa có dự án nào.\n";
            $prompt .= "Bạn có thể giúp họ tạo dự án mới.\n";
        }

        if (isset($context['project'])) {
            $project = $context['project'];
            $prompt .= "\n=== DỰ ÁN ĐANG XEM ===\n";
            $prompt .= "Tên: {$project->name}\n";
            $prompt .= "Trạng thái: {$project->status}\n";
        }

        $prompt .= "\nHãy trả lời một cách hữu ích, rõ ràng và ngắn gọn. Sử dụng tiếng Việt.";
        $prompt .= "Khi người dùng hỏi về dự án, hãy sử dụng thông tin trên để trả lời chính xác.";

        return $prompt;
    }
}

