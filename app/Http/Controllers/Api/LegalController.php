<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ComplianceFramework;
use App\Models\RiskRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class LegalController extends Controller
{
    public function getDashboard(Request $request)
    {
        $contracts = Contract::with('account:id,name,industry')
            ->orderBy('created_at', 'desc')
            ->get();
            
        $templates = ContractTemplate::all();
        
        $frameworks = ComplianceFramework::with('controls')->get();
        
        $risks = RiskRegister::all();

        return response()->json([
            'contracts' => $contracts,
            'templates' => $templates,
            'frameworks' => $frameworks,
            'risks' => $risks,
            'metrics' => [
                'active_contracts' => $contracts->where('status', 'active')->count(),
                'pending_signatures' => $contracts->where('status', 'sent')->count(),
                'expiring_soon' => $contracts->where('status', 'active')->where('end_date', '<=', now()->addDays(30))->count(),
            ]
        ]);
    }

    public function generateContractDraft(Request $request)
    {
        $data = $request->validate([
            'crm_account_id' => 'required|uuid|exists:crm_accounts,id',
            'contract_template_id' => 'required|uuid|exists:contract_templates,id',
            'variables' => 'array',
        ]);
        
        $template = ContractTemplate::findOrFail($data['contract_template_id']);
        
        $promptStr = $template->ai_prompt;
        if (!empty($data['variables'])) {
            foreach($data['variables'] as $k => $v) {
                $promptStr = str_replace('{'.$k.'}', $v, $promptStr);
            }
        }

        $response = Http::timeout(120)->withToken(env('PYTHON_SERVICE_SECRET', 'secret_token_123'))->post('http://localhost:8001/api/agent/chat', [
            'agent_id' => 'legal',
            'message' => 'Please generate the document based on the requirements.',
            'context_chunks' => [],
            'conversation_history' => [],
            'system_prompt' => "You are Lexor, an expert legal AI assistant. Act strictly as a legal drafting engine. Output ONLY the legal contract document text requested. \n" . $promptStr
        ]);

        $generatedText = '';
        if ($response->successful()) {
            $generatedText = $response->json()['response_text'];
        } else {
            return response()->json(['message' => 'AI Generation Failed: ' . $response->body()], 500);
        }

        $draftTitle = $template->name . ' - ' . date('Y-m-d');
        
        $contract = Contract::create([
            'crm_account_id' => $data['crm_account_id'],
            'title' => $draftTitle,
            'status' => 'draft',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'created_by' => Auth::id(),
            'body' => $generatedText
        ]);
        
        // In a real system, you would save $generatedText to a file and link it via file_id.
        // Or store it in a 'body' field on the contract.
        
        return response()->json([
            'message' => 'Contract generated successfully via Lexor AI',
            'contract' => $contract,
            'generated_text' => $generatedText
        ]);
    }

    public function storeRisk(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'likelihood' => 'required|in:low,medium,high',
            'impact' => 'required|in:low,medium,high',
            'status' => 'required|in:open,mitigated,accepted,closed',
            'mitigation_plan' => 'nullable|string'
        ]);

        $risk = RiskRegister::create($data);

        return response()->json(['message' => 'Risk recorded successfully', 'risk' => $risk]);
    }

    public function updateContract(Request $request, $id)
    {
        $contract = Contract::findOrFail($id);
        
        $data = $request->validate([
            'body' => 'nullable|string',
            'status' => 'nullable|string',
            'title' => 'nullable|string'
        ]);

        $contract->update($data);

        return response()->json(['message' => 'Contract updated successfully', 'contract' => $contract]);
    }
}
