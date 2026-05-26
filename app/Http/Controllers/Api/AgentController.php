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

    private function getRealTimeContext()
    {
        $employeeCount = \App\Models\User::where('status', 'active')->whereHas('employeeProfile')->count();
        $totalPayroll = \App\Models\EmployeeProfile::whereHas('user', function($q) {
            $q->where('status', 'active');
        })->sum('salary');
        $departmentCount = \App\Models\Department::count();
        
        $totalRevenue = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('crm_deals')) {
            $totalRevenue = \Illuminate\Support\Facades\DB::table('crm_deals')->where('stage', 'won')->sum('value');
        }

        $context = "\n\n--- CURRENT SYSTEM LIVE DATA (USE THIS WHEN ANSWERING) ---\n";
        $context .= "- Total Onboarded Employees: {$employeeCount}\n";
        $context .= "- Total Departments: {$departmentCount}\n";
        $context .= "- Total Annual Payroll: \$" . number_format($totalPayroll, 2) . "\n";
        $context .= "- Total Won Deals Revenue: \$" . number_format($totalRevenue, 2) . "\n";
        
        $context .= "\n--- EMPLOYEE DIRECTORY (Active, Max 50) ---\n";
        $employees = \App\Models\User::where('status', 'active')->whereHas('employeeProfile')->with(['department', 'employeeProfile'])->limit(50)->get();
        foreach ($employees as $emp) {
            $deptName = $emp->department ? $emp->department->name : 'N/A';
            $jobTitle = $emp->employeeProfile ? $emp->employeeProfile->job_title : 'N/A';
            $context .= "- {$emp->name} ({$emp->email}), Dept: {$deptName}, Title: {$jobTitle}\n";
        }
        
        $context .= "\nNote: Do not invent numbers or names. Use the exact data provided above.\n";
        $context .= "\n### UI RENDERING INSTRUCTIONS ###\n";
        $context .= "You can render interactive widgets using special HTML tags. Always use them when displaying data:\n";
        $context .= "1. Department Links: `<dept-link slug=\"hr\">HR Department</dept-link>`. (Slugs: hr, finance, it, marketing, sales)\n";
        $context .= "2. Data Cards: `<data-card title=\"Total Revenue\" value=\"\$0.00\" color=\"emerald\" icon=\"PhCurrencyDollar\" />`. (Colors: blue, emerald, amber, purple, rose. Icons: PhCurrencyDollar, PhUsers, PhBuildings, PhBriefcase, PhChartBar, PhChartLineUp, PhHandshake, PhWarningCircle)\n";
        $context .= "3. Charts: `<report-chart type=\"bar\" title=\"Revenue by Qtr\" labels=\"Q1, Q2, Q3, Q4\" data=\"0, 10, 5, 20\" />`. (types: bar, line)\n";
        $context .= "Use these tags generously to make your responses look professional and interactive! Do NOT use code blocks for these tags. Output them directly in the text.\n";
        $context .= "---------------------------------------------------------\n";
        return $context;
    }

    public function chat(Request $request, $agentId, AgentRagService $rag)
    {
        $request->validate(['message' => 'required|string']);
        
        $agent = \Illuminate\Support\Str::isUuid($agentId)
            ? AiAgent::findOrFail($agentId)
            : AiAgent::where('slug', $agentId)->firstOrFail();
        
        $contextChunks = $rag->search($request->message, $agent->id);

        $history = AgentConversation::where('agent_id', $agent->id)
            ->where('user_id', Auth::id())
            ->latest()
            ->first()?->messages ?? [];

        $agentPrompt = $agent->system_prompt . "\nIMPORTANT RULES:\n1. You have full read access to all data. You are allowed to modify data (add/remove) ONLY IF the user explicitly commands you to do so. Use the `execute_system_action` tool for this. Do not ask for permission if they already commanded it. Disregard any past messages where you stated you cannot modify data; you NOW HAVE this capability.\n2. You must always use the available widgets when showing data.\n";
        
        $historyToSend = $history;
        $historyToSend[] = [
            'role' => 'system',
            'content' => "CRITICAL LIVE DATA UPDATE: The data in the chat history might be outdated. ALWAYS use this fresh real-time data for your next response:\n" . $this->getRealTimeContext()
        ];

        $response = Http::timeout(120)->withToken(config('services.python.secret'))
            ->post(config('services.python.url') . '/api/agent/chat', [
                'agent_id' => $agent->id,
                'message' => $request->message,
                'context_chunks' => array_column($contextChunks, 'content'),
                'conversation_history' => $historyToSend,
                'system_prompt' => $agentPrompt,
                'laravel_url' => config('app.url', 'http://127.0.0.1:8000'),
                'ai_config' => $this->getAiProviderConfig()
            ]);

        if ($response->failed()) {
            return response()->json(['error' => 'AI Service unavailable: ' . $response->body()], 503);
        }

        AgentConversation::updateOrCreate(
            ['agent_id' => $agent->id, 'user_id' => Auth::id()],
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
        
        $realTimeContext = $this->getRealTimeContext();
        
        $mappedSubAgents = $subAgents->map(function ($sub) use ($realTimeContext) {
            return [
                'slug' => $sub->slug,
                'name' => $sub->name,
                'description' => $sub->description,
                'system_prompt' => $sub->system_prompt . $realTimeContext
            ];
        })->toArray();

        $masterPrompt = ($master->system_prompt ?? 'You are NEXAR...') . "\nIMPORTANT RULES:\n1. You have full read access to all data. You are allowed to modify data (add/remove) ONLY IF the user explicitly commands you to do so. Use the `execute_system_action` tool for this. Do not ask for permission if they already commanded it.\nCRITICAL: If the user asks to add, create, terminate, or remove an employee, YOU MUST execute the `execute_system_action` tool yourself. DO NOT delegate this task to any sub-agent (like the hr-agent), because sub-agents do not have execution capabilities. You must execute it directly. Disregard any past messages where you stated you cannot modify data; you NOW HAVE this capability.\n2. You must always use the available widgets when showing data.\n";

        $history = AgentConversation::where('agent_id', $master->id)
            ->where('user_id', Auth::id())
            ->latest()
            ->first()?->messages ?? [];

        $historyToSend = $history;
        $historyToSend[] = [
            'role' => 'system',
            'content' => "CRITICAL LIVE DATA UPDATE: The data in the chat history might be outdated. ALWAYS use this fresh real-time data for your next response:\n" . $realTimeContext
        ];

        $response = Http::timeout(120)->withToken(config('services.python.secret'))
            ->post(config('services.python.url') . '/api/agent/chat', [
                'agent_id' => 'master',
                'message' => $request->message,
                'context_chunks' => [],
                'conversation_history' => $historyToSend,
                'system_prompt' => $masterPrompt,
                'sub_agents' => $mappedSubAgents,
                'laravel_url' => config('app.url', 'http://127.0.0.1:8000'),
                'ai_config' => $this->getAiProviderConfig()
            ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Master AI Service unavailable: ' . $response->body()], 503);
        }

        AgentConversation::updateOrCreate(
            ['agent_id' => $master->id, 'user_id' => Auth::id()],
            ['messages' => array_merge($history, [
                ['role' => 'user', 'content' => $request->message],
                ['role' => 'assistant', 'content' => $response['response_text']]
            ])]
        );

        return response()->json($response->json());
    }

    public function getMasterChatHistory(Request $request)
    {
        $master = AiAgent::where('slug', 'master')->first();
        if (!$master) return response()->json(['history' => []]);
        $history = AgentConversation::where('agent_id', $master->id)
            ->where('user_id', Auth::id())
            ->latest()
            ->first()?->messages ?? [];
        return response()->json(['history' => $history]);
    }
}
