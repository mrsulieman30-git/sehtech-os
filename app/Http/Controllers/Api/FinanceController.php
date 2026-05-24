<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Bill;
use App\Models\CrmDeal;
use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FinanceController extends Controller
{
    public function getDashboard(Request $request)
    {
        // 1. Core Metrics
        $totalRevenue = Payment::sum('amount');
        $outstanding = Invoice::whereNotIn('status', ['paid', 'cancelled', 'draft'])->sum('balance_due');
        $overdue = Invoice::where('status', 'overdue')->sum('balance_due');
        
        $totalExpenses = Bill::whereNotIn('status', ['voided', 'cancelled'])->sum('amount');
        $paidExpenses = Bill::where('status', 'paid')->sum('amount');

        // 2. HR Payroll Synergy Metric
        $monthlyPayroll = EmployeeProfile::whereHas('user', function($q) {
            $q->where('status', 'active');
        })->sum('salary') / 12;

        // 3. Operating Monthly Bills (Non-payroll)
        $monthlyBills = Bill::whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                            ->where('category', '!=', 'payroll')
                            ->whereNotIn('status', ['voided', 'cancelled'])
                            ->sum('amount');

        // 4. Net Burn & Survival Runway Calculations
        $monthlyRevenue = Payment::whereBetween('payment_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('amount');
        $totalMonthlyExpenses = $monthlyPayroll + $monthlyBills;
        $netBurnRate = max($totalMonthlyExpenses - $monthlyRevenue, 0);

        // Model Startup Cash Balance: Seed Capital from System Settings + Income - Expenses Paid
        $startingCashSeed = floatval(\App\Models\SystemSetting::getSetting('company_profile', [])['starting_cash'] ?? 0.00);
        $currentCashBalance = $startingCashSeed + $totalRevenue - $paidExpenses;
        $runwayMonths = $netBurnRate > 0 ? round($currentCashBalance / $netBurnRate, 1) : 99.0; // 99 means infinite (profitable)

        // 5. Invoices & Bills Lists
        $invoices = Invoice::with('client:id,name')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        $bills = Bill::orderBy('due_date', 'asc')->get();
            
        $chartData = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'data' => [4500, 5200, 3800, 8100, 6500, $totalRevenue > 0 ? $totalRevenue : 9200]
        ];

        return response()->json([
            'metrics' => [
                'total_revenue' => $totalRevenue,
                'outstanding_balance' => $outstanding,
                'overdue_amount' => $overdue,
                'total_expenses' => $totalExpenses,
                'cash_balance' => $currentCashBalance,
                'net_burn_rate' => $netBurnRate,
                'runway_months' => $runwayMonths,
                'monthly_payroll' => $monthlyPayroll,
                'monthly_bills' => $monthlyBills
            ],
            'invoices' => $invoices,
            'bills' => $bills,
            'chart' => $chartData
        ]);
    }

    public function storeInvoice(Request $request)
    {
        $request->validate([
            'client_id' => 'required|uuid|exists:crm_accounts,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'tax_rate' => 'numeric|min:0|max:100',
            'deal_id' => 'nullable|uuid|exists:crm_deals,id',
        ]);

        $subtotal = 0;
        foreach($request->items as $item) {
            $subtotal += $item['quantity'] * $item['price'];
        }
        $taxAmount = $subtotal * ($request->tax_rate / 100);
        $total = $subtotal + $taxAmount;

        $meta = [];
        if ($request->filled('deal_id')) {
            $meta['deal_id'] = $request->deal_id;
        }

        $invoice = Invoice::create([
            'invoice_number' => 'INV-' . strtoupper(substr(uniqid(), -6)),
            'client_id' => $request->client_id,
            'issue_date' => $request->issue_date,
            'due_date' => $request->due_date,
            'items' => $request->items,
            'subtotal' => $subtotal,
            'tax_rate' => $request->tax_rate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'balance_due' => $total,
            'status' => 'sent',
            'meta' => $meta,
            'created_by' => Auth::id()
        ]);

        return response()->json(['message' => 'Invoice generated successfully', 'invoice' => $invoice]);
    }

    public function storePayment(Request $request)
    {
        $data = $request->validate([
            'invoice_id' => 'required|uuid|exists:invoices,id',
            'amount' => 'required|numeric',
            'payment_date' => 'required|date',
            'method' => 'required|in:bank_transfer,credit_card,cash,mobile_money,crypto',
        ]);
        $data['recorded_by'] = Auth::id();
        $data['client_id'] = Invoice::find($data['invoice_id'])->client_id;
        
        $payment = Payment::create($data);
        
        $invoice = Invoice::find($data['invoice_id']);
        $invoice->balance_due -= $data['amount'];
        if ($invoice->balance_due <= 0) {
            $invoice->status = 'paid';
        } else {
            $invoice->status = 'partially_paid';
        }
        $invoice->save();
        
        return response()->json(['message' => 'Payment recorded', 'payment' => $payment]);
    }

    // --- ACCOUNTS PAYABLE (BILLS & EXPENSES) ---

    public function getBills()
    {
        $bills = Bill::orderBy('due_date', 'asc')->get();
        return response()->json($bills);
    }

    public function storeBill(Request $request)
    {
        $request->validate([
            'vendor_name' => 'required|string|max:255',
            'category' => 'required|string|in:software,marketing,office,travel,payroll,other',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
            'is_recurring' => 'nullable|boolean',
            'recurring_frequency' => 'nullable|string|in:monthly,quarterly,yearly'
        ]);

        $bill = Bill::create([
            'bill_number' => 'BILL-' . strtoupper(substr(uniqid(), -6)),
            'vendor_name' => $request->vendor_name,
            'category' => $request->category,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'status' => 'unpaid',
            'notes' => $request->notes,
            'created_by' => Auth::id(),
            'is_recurring' => $request->input('is_recurring', false),
            'recurring_frequency' => $request->input('recurring_frequency')
        ]);

        return response()->json(['message' => 'Vendor bill recorded', 'bill' => $bill]);
    }

    public function payBill($id)
    {
        $bill = Bill::findOrFail($id);
        $bill->update([
            'status' => 'paid',
            'payment_date' => Carbon::now()->format('Y-m-d')
        ]);

        return response()->json(['message' => 'Bill marked as paid', 'bill' => $bill]);
    }

    public function updateBill(Request $request, $id)
    {
        $bill = Bill::findOrFail($id);

        $request->validate([
            'vendor_name' => 'required|string|max:255',
            'category' => 'required|string|in:software,marketing,office,travel,payroll,other',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
            'is_recurring' => 'nullable|boolean',
            'recurring_frequency' => 'nullable|string|in:monthly,quarterly,yearly'
        ]);

        $bill->update([
            'vendor_name' => $request->vendor_name,
            'category' => $request->category,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'notes' => $request->notes,
            'is_recurring' => $request->input('is_recurring', false),
            'recurring_frequency' => $request->input('recurring_frequency')
        ]);

        return response()->json(['message' => 'Bill updated successfully', 'bill' => $bill]);
    }

    public function voidBill($id)
    {
        $bill = Bill::findOrFail($id);
        $bill->update([
            'status' => 'voided',
            'payment_date' => null
        ]);

        return response()->json(['message' => 'Bill voided successfully', 'bill' => $bill]);
    }

    public function cancelBill($id)
    {
        $bill = Bill::findOrFail($id);
        $bill->update([
            'status' => 'cancelled',
            'payment_date' => null
        ]);

        return response()->json(['message' => 'Bill cancelled successfully', 'bill' => $bill]);
    }

    public function destroyBill($id)
    {
        $bill = Bill::findOrFail($id);
        $bill->delete();

        return response()->json(['message' => 'Bill deleted successfully']);
    }

    // --- SYNERGY: SALES & BD ---

    public function getWonDealsQueue()
    {
        $wonDeals = CrmDeal::with('account')
            ->whereIn('stage', ['closed_won', 'won'])
            ->get()
            ->filter(function ($deal) {
                // Return only won/closed_won deals that don't have an invoice generated yet
                return !Invoice::where('meta->deal_id', $deal->id)->exists();
            })
            ->values();

        return response()->json($wonDeals);
    }

    // --- SYNERGY: HR PAYROLL ---

    public function getPayrollMetrics()
    {
        $employees = EmployeeProfile::whereHas('user', function($q) {
            $q->where('status', 'active');
        })->with('user:id,name,avatar')->get();

        $totalMonthly = EmployeeProfile::whereHas('user', function($q) {
            $q->where('status', 'active');
        })->sum('salary') / 12;

        // Check if payroll has already been posted as a bill for the current month
        $currentMonthName = Carbon::now()->format('F Y');
        $payrollPosted = Bill::where('category', 'payroll')
            ->where('notes', 'like', '%' . $currentMonthName . '%')
            ->exists();

        return response()->json([
            'employees' => $employees,
            'total_monthly_payroll' => $totalMonthly,
            'payroll_posted_this_month' => $payrollPosted,
            'current_month' => $currentMonthName
        ]);
    }

    public function postPayrollExpense(Request $request)
    {
        $totalMonthly = EmployeeProfile::whereHas('user', function($q) {
            $q->where('status', 'active');
        })->sum('salary') / 12;
        if ($totalMonthly <= 0) {
            return response()->json(['message' => 'No active payroll to post.'], 400);
        }

        $currentMonthName = Carbon::now()->format('F Y');
        
        // Prevent double posting
        $exists = Bill::where('category', 'payroll')
            ->where('notes', 'like', '%' . $currentMonthName . '%')
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Payroll for ' . $currentMonthName . ' has already been posted.'], 400);
        }

        $bill = Bill::create([
            'bill_number' => 'BILL-PAYROLL-' . strtoupper(substr(uniqid(), -4)),
            'vendor_name' => 'Active Staff Salaries',
            'category' => 'payroll',
            'amount' => $totalMonthly,
            'due_date' => Carbon::now()->endOfMonth()->format('Y-m-d'),
            'status' => 'unpaid',
            'notes' => 'Staff payroll expense automatically posted for ' . $currentMonthName,
            'created_by' => Auth::id()
        ]);

        return response()->json(['message' => 'Monthly payroll expense posted successfully as a bill!', 'bill' => $bill]);
    }
}
