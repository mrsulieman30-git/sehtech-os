<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index()
    {
        $clientId = Auth::guard('portal')->user()->client_id;

        $invoices = Invoice::where('client_id', $clientId)
            ->orderBy('issue_date', 'desc')
            ->get();

        return Inertia::render('Portal/Invoices', [
            'invoices' => $invoices,
        ]);
    }
}
