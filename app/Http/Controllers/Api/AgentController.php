<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\AgentConversation;
use App\Models\SystemSetting;
use App\Services\AgentRagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class AgentController extends Controller
{
    private function getAiProviderConfig()
    {
        $setting = SystemSetting::where('key', 'ai_providers')->first();
        return $setting ? $setting->value : [
            'provider' => 'deepseek',
            'deepseek' => ['model' => 'deepseek-chat', 'api_key' => env('DEEPSEEK_API_KEY')],
            'openai' => ['model' => 'gpt-4o', 'api_key' => ''],
            'gemini' => ['model' => 'gemini-1.5-pro', 'api_key' => ''],
            'claude' => ['model' => 'claude-3-opus', 'api_key' => '']
        ];
    }

    public function chat(Request $request, $agentId, AgentRagService $rag)
    {
        $request->validate(['message' => 'required|string']);
        
        $agent = AiAgent::findOrFail($agentId);
        
        $contextChunks = $rag->search($request->message, $agentId);

        $history = AgentConversation::where('agent_id', $agentId)
            ->where('user_id', Auth::id())
            ->latest()
            ->first()?->messages ?? [];

        $response = Http::withToken(config('services.python.secret'))
            ->post(config('services.python.url') . '/api/agent/chat', [
                'agent_id' => $agentId,
                'message' => $request->message,
                'context_chunks' => array_column($contextChunks, 'content'),
                'conversation_history' => $history,
                'system_prompt' => $agent->system_prompt,
                'ai_config' => $this->getAiProviderConfig()
            ]);

        if ($response->failed()) {
            return response()->json(['error' => 'AI Service unavailable: ' . $response->body()], 503);
        }

        AgentConversation::updateOrCreate(
            ['agent_id' => $agentId, 'user_id' => Auth::id()],
            ['messages' => array_merge($history, [
                ['role' => 'user', 'content' => $request->message],
                ['role' => 'assistant', 'content' => $response['response_text']]
            ])]
        );

        return response()->json($response->json());
    }

    public function masterChat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'history' => 'array'
        ]);

        $master = AiAgent::where('slug', 'master')->first();
        $subAgents = AiAgent::where('slug', '!=', 'master')->get(['slug', 'name', 'description', 'system_prompt']);

        $response = Http::withToken(env('PYTHON_SERVICE_SECRET', 'secret_token_123'))
            ->post('http://localhost:8001/api/agent/chat', [
                'agent_id' => 'master',
                'message' => $request->message,
                'context_chunks' => [],
                'conversation_history' => $request->history ?? [],
                'system_prompt' => $master->system_prompt ?? 'You are NEXAR...',
                'sub_agents' => $subAgents->toArray(),
                'ai_config' => $this->getAiProviderConfig()
            ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Master AI Service unavailable: ' . $response->body()], 503);
        }

        return response()->json($response->json());
    }
}
