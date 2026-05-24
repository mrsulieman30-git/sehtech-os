<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmAccount;
use App\Models\CrmDeal;
use App\Models\CrmContact;
use App\Models\CrmActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalesController extends Controller
{
    public function getSalesData(Request $request)
    {
        // Fetch Deals for Pipeline
        $deals = CrmDeal::with(['account.contacts', 'assignee:id,name,avatar'])->get();
        
        // Fetch Accounts
        $accounts = CrmAccount::with('contacts', 'deals')->orderBy('name')->get();
        
        // Fetch recent Activities
        $activities = CrmActivity::with(['activatable', 'creator:id,name,avatar'])
                        ->where('type', '!=', 'status_change')
                        ->orderBy('created_at', 'desc')->take(50)->get();

        // Forecast data (simplified: deals by expected_close_date month)
        $forecastDeals = CrmDeal::whereNotNull('expected_close_date')
                            ->whereNotIn('stage', ['closed_lost', 'lost'])
                            ->get();

        return response()->json([
            'deals' => $deals,
            'accounts' => $accounts,
            'activities' => $activities,
            'forecast' => $forecastDeals
        ]);
    }

    public function storeAccount(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'industry' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'contacts' => 'nullable|array',
            'contacts.*.first_name' => 'required|string',
            'contacts.*.last_name' => 'nullable|string',
            'contacts.*.email' => 'nullable|email',
            'contacts.*.phone' => 'nullable|string',
            'contacts.*.whatsapp' => 'nullable|string',
        ]);

        $account = CrmAccount::create([
            'name' => $request->name,
            'industry' => $request->industry,
            'city' => $request->city,
            'country' => $request->country,
            'status' => 'lead'
        ]);

        if ($request->has('contacts') && is_array($request->contacts)) {
            foreach ($request->contacts as $contactData) {
                \App\Models\CrmContact::create([
                    'crm_account_id' => $account->id,
                    'first_name' => $contactData['first_name'],
                    'last_name' => $contactData['last_name'] ?? null,
                    'email' => $contactData['email'] ?? null,
                    'phone' => $contactData['phone'] ?? null,
                    'whatsapp' => $contactData['whatsapp'] ?? null,
                ]);
            }
        }

        return response()->json(['message' => 'Account created', 'account' => $account->load('contacts')]);
    }

    public function updateAccount(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'industry' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
        ]);

        $account = CrmAccount::findOrFail($id);
        $account->update($request->only(['name', 'industry', 'city', 'country']));

        return response()->json(['message' => 'Account updated successfully', 'account' => $account]);
    }

    public function storeContact(Request $request)
    {
        $request->validate([
            'crm_account_id' => 'required|exists:crm_accounts,id',
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'whatsapp' => 'nullable|string',
            'job_title' => 'nullable|string'
        ]);

        $contact = CrmContact::create($request->all());
        return response()->json(['message' => 'Contact added', 'contact' => $contact]);
    }

    public function updateContact(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'whatsapp' => 'nullable|string',
            'job_title' => 'nullable|string'
        ]);

        $contact = CrmContact::findOrFail($id);
        $contact->update($request->all());

        return response()->json(['message' => 'Contact updated successfully', 'contact' => $contact]);
    }

    public function destroyContact($id)
    {
        $contact = CrmContact::findOrFail($id);
        $contact->delete();

        return response()->json(['message' => 'Contact deleted successfully']);
    }

    public function storeDeal(Request $request)
    {
        $request->validate([
            'crm_account_id' => 'required|exists:crm_accounts,id',
            'title' => 'required|string',
            'value' => 'nullable|numeric',
            'stage' => 'required|string',
            'expected_close_date' => 'nullable|date',
            'requirements' => 'nullable|string',
            'payment_type' => 'nullable|string',
            'recurring_frequency' => 'nullable|string',
            'recurring_amount' => 'nullable|numeric',
            'collection_date' => 'nullable|date',
            'contract_file' => 'nullable|file|mimes:pdf,doc,docx'
        ]);

        $data = $request->except(['contract_file']);
        $data['assigned_to'] = Auth::id();

        if ($request->hasFile('contract_file')) {
            $path = $request->file('contract_file')->store('contracts', 'public');
            $data['contract_file_path'] = $path;

            // Synergy: Create Legal Contract record
            $contract = \App\Models\Contract::create([
                'crm_account_id' => $request->crm_account_id,
                'title' => 'Contract for Deal: ' . $request->title,
                'file_id' => $path, // simplified
                'status' => 'draft',
                'created_by' => Auth::id()
            ]);
            $data['legal_contract_id'] = $contract->id;
        }

        $deal = CrmDeal::create($data);
        return response()->json(['message' => 'Deal created', 'deal' => $deal]);
    }

    public function updateDeal(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string',
            'value' => 'nullable|numeric',
            'stage' => 'nullable|string',
            'expected_close_date' => 'nullable|date',
            'priority' => 'nullable|string',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'payment_type' => 'nullable|string',
            'recurring_frequency' => 'nullable|string',
            'recurring_amount' => 'nullable|numeric',
            'collection_date' => 'nullable|date',
        ]);

        $deal = CrmDeal::findOrFail($id);
        $oldStage = $deal->stage;
        $deal->update($request->all());

        // Synergy: Automatically bill the deal if newly marked Closed Won
        if (($deal->stage === 'closed_won' || $deal->stage === 'won') && $oldStage !== 'closed_won' && $oldStage !== 'won') {
            $this->triggerAutoBilling($deal);
        }

        return response()->json(['message' => 'Deal updated successfully', 'deal' => $deal]);
    }

    public function updateDealStage(Request $request, $id)
    {
        $request->validate(['stage' => 'required|string']);
        $deal = CrmDeal::findOrFail($id);
        $oldStage = $deal->stage;
        $deal->update(['stage' => $request->stage]);

        // Synergy: Automatically bill the deal if newly marked Closed Won
        if (($request->stage === 'closed_won' || $request->stage === 'won') && $oldStage !== 'closed_won' && $oldStage !== 'won') {
            $this->triggerAutoBilling($deal);
        }

        return response()->json(['message' => 'Deal stage updated']);
    }

    protected function triggerAutoBilling($deal)
    {
        // Check if invoice already exists to prevent duplicate billing
        $exists = \App\Models\Invoice::where('meta->deal_id', $deal->id)->exists();
        if (!$exists) {
            $amount = ($deal->payment_type === 'recurring' && $deal->recurring_amount > 0)
                ? $deal->recurring_amount
                : ($deal->value ?: 0);

            $dueDate = $deal->collection_date ?: ($deal->expected_close_date ?: now()->addDays(15));

            \App\Models\Invoice::create([
                'invoice_number' => 'INV-' . strtoupper(substr(uniqid(), -6)),
                'client_id' => $deal->crm_account_id,
                'issue_date' => now()->format('Y-m-d'),
                'due_date' => \Carbon\Carbon::parse($dueDate)->format('Y-m-d'),
                'subtotal' => $amount,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => $amount,
                'balance_due' => $amount,
                'status' => 'sent',
                'items' => [
                    [
                        'description' => 'Contract Services for Won Deal: ' . $deal->title,
                        'quantity' => 1,
                        'price' => $amount
                    ]
                ],
                'meta' => ['deal_id' => $deal->id],
                'created_by' => Auth::id()
            ]);
        }
    }

    public function showAccount($id)
    {
        $account = CrmAccount::with(['contacts', 'deals'])->findOrFail($id);
        
        $activities = CrmActivity::with('creator:id,name,avatar')
            ->where('activatable_type', CrmAccount::class)
            ->where('activatable_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'account' => $account,
            'activities' => $activities
        ]);
    }

    public function showDeal($id)
    {
        $deal = CrmDeal::with(['account.contacts', 'assignee:id,name,avatar'])->findOrFail($id);
        
        $activities = CrmActivity::with('creator:id,name,avatar')
            ->where('activatable_type', CrmDeal::class)
            ->where('activatable_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'deal' => $deal,
            'activities' => $activities
        ]);
    }



    public function getClientsList(Request $request)
    {
        // Used by Finance module to populate dropdowns
        $accounts = CrmAccount::select('id', 'name')->orderBy('name')->get();
        // Map to expected frontend keys if needed: id, first_name, last_name, organization
        $mapped = $accounts->map(function($acc) {
            return [
                'id' => $acc->id,
                'first_name' => '',
                'last_name' => '',
                'organization' => $acc->name
            ];
        });
        return response()->json($mapped);
    }
}
