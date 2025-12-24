<?php

namespace App\Services;

use App\Helpers\LocaleHelper;
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
                $locale = app()->getLocale();
                throw new \Exception(trans('ai.errors.api_error', [], $locale));
            }

            $content = $data['choices'][0]['message']['content'];
            
            if (empty(trim($content))) {
                Log::warning('Groq API: Empty content response', ['data' => $data]);
                $locale = app()->getLocale();
                throw new \Exception(trans('ai.errors.empty_response', [], $locale));
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
        $locale = app()->getLocale();
        $prompt = trans('ai.system_prompt.intro', [], $locale) . "\n\n";

        if (isset($context['user'])) {
            $user = $context['user'];
            $prompt .= trans('ai.system_prompt.user_info', [
                'name' => $user->name,
                'email' => $user->email
            ], $locale) . "\n";
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
            $prompt .= "\n" . trans('ai.system_prompt.projects_section', [], $locale) . "\n";
            $prompt .= trans('ai.system_prompt.total_projects', ['count' => $projectsCount], $locale) . "\n\n";
            
            // Convert to array if it's a Collection
            $projectsArray = is_array($userProjects) ? $userProjects : $userProjects->toArray();
            
            foreach ($projectsArray as $project) {
                $prompt .= trans('ai.system_prompt.project_name', ['name' => $project['name']], $locale) . "\n";
                $prompt .= "  " . trans('ai.system_prompt.project_id', ['id' => $project['id']], $locale) . "\n";
                $prompt .= "  " . trans('ai.system_prompt.project_status', ['status' => $project['status']], $locale) . "\n";
                $prompt .= "  " . trans('ai.system_prompt.project_progress', [
                    'percentage' => $project['progress_percentage'],
                    'completed' => $project['completed_tasks'],
                    'total' => $project['total_tasks']
                ], $locale) . "\n";
                if ($project['start_date']) {
                    $prompt .= "  " . trans('ai.system_prompt.project_start_date', ['date' => $project['start_date']], $locale) . "\n";
                }
                if ($project['end_date']) {
                    $prompt .= "  " . trans('ai.system_prompt.project_end_date', ['date' => $project['end_date']], $locale) . "\n";
                }
                if ($project['is_pinned']) {
                    $prompt .= "  " . trans('ai.system_prompt.project_pinned', [], $locale) . "\n";
                }
                
                // Add tasks information
                if (isset($project['tasks']) && is_array($project['tasks']) && count($project['tasks']) > 0) {
                    $prompt .= "  " . trans('ai.system_prompt.tasks_section', [], $locale) . "\n";
                    foreach ($project['tasks'] as $task) {
                        $prompt .= "    " . trans('ai.system_prompt.task_item', [
                            'id' => $task['id'],
                            'name' => $task['name'],
                            'status' => $task['status_text']
                        ], $locale) . "\n";
                        if (!empty($task['content'])) {
                            $content = mb_substr($task['content'], 0, 100);
                            $prompt .= "      " . trans('ai.system_prompt.task_description', [
                                'content' => $content . (mb_strlen($task['content']) > 100 ? '...' : '')
                            ], $locale) . "\n";
                        }
                    }
                }
                
                // Add members information for task assignment
                if (isset($project['members']) && is_array($project['members']) && count($project['members']) > 0) {
                    $prompt .= "  " . trans('ai.system_prompt.members_section', [], $locale) . "\n";
                    foreach ($project['members'] as $member) {
                        $prompt .= "    " . trans('ai.system_prompt.member_item', [
                            'id' => $member['id'],
                            'name' => $member['name'],
                            'email' => $member['email']
                        ], $locale) . "\n";
                    }
                }
                
                $prompt .= "\n";
            }
            
            // Add capabilities
            $capabilities = trans('ai.system_prompt.capabilities', [], $locale);
            if (is_array($capabilities)) {
                foreach ($capabilities as $capability) {
                    $prompt .= $capability . "\n";
                }
            }
        } else {
            $noProjects = trans('ai.system_prompt.no_projects', [], $locale);
            if (is_array($noProjects)) {
                foreach ($noProjects as $line) {
                    $prompt .= $line . "\n";
                }
            }
        }

        if (isset($context['project'])) {
            $project = $context['project'];
            $prompt .= "\n" . trans('ai.system_prompt.current_project', [], $locale) . "\n";
            $prompt .= trans('ai.system_prompt.current_project_name', ['name' => $project->name], $locale) . "\n";
            $prompt .= trans('ai.system_prompt.current_project_status', ['status' => $project->status], $locale) . "\n";
        }

        // Add creation rules
        $creationRules = trans('ai.system_prompt.creation_rules', [], $locale);
        $prompt .= "\n" . $creationRules['title'] . "\n";
        $prompt .= $creationRules['intro'] . "\n";
        $prompt .= $creationRules['step1'] . "\n";
        $prompt .= $creationRules['step2'] . "\n";
        $prompt .= $creationRules['step3'] . "\n\n";
        
        $prompt .= $creationRules['task_format'] . "\n";
        $prompt .= $creationRules['task_format_example'] . "\n\n";
        
        $prompt .= $creationRules['project_format'] . "\n";
        $prompt .= $creationRules['project_format_example'] . "\n\n";
        
        $prompt .= $creationRules['important'] . ":\n";
        $prompt .= $creationRules['important1'] . "\n";
        $prompt .= $creationRules['important2'] . "\n";
        $prompt .= $creationRules['important3'] . "\n";
        $prompt .= $creationRules['important4'] . "\n";
        
        // Add usage guide with language instruction
        $usageGuide = trans('ai.system_prompt.usage_guide', [], $locale);
        $prompt .= "\n" . $usageGuide['title'] . "\n";
        $prompt .= $usageGuide['rule1'] . "\n";
        $prompt .= $usageGuide['rule2'] . "\n";
        $prompt .= $usageGuide['rule3'] . "\n";
        $prompt .= $usageGuide['language_rule'] . "\n";
        
        // Add detailed creation rules
        $detailedRules = trans('ai.system_prompt.creation_rules_detailed', [], $locale);
        $prompt .= "\n" . $detailedRules['title'] . "\n";
        $prompt .= $detailedRules['rule1'] . "\n";
        $prompt .= $detailedRules['rule2'] . "\n";
        $prompt .= $detailedRules['rule3'] . "\n";
        $prompt .= $detailedRules['rule4'] . "\n";
        $prompt .= $detailedRules['rule5'] . "\n";

        return $prompt;
    }
}

