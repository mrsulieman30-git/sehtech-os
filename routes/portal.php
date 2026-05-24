<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Portal\PortalAuthController;
use App\Http\Controllers\Portal\TicketController;
use App\Http\Controllers\Portal\InvoiceController;
use App\Http\Controllers\Portal\DocumentController;
use App\Models\Ticket;
use App\Models\Invoice;
use App\Models\Contract;

// Portal Authentication (Guest only)
Route::middleware('guest:portal')->group(function () {
    Route::get('/portal/login', [PortalAuthController::class, 'show'])->name('portal.login');
    Route::post('/portal/login', [PortalAuthController::class, 'login']);
});

// Portal Authenticated Routes
Route::middleware('auth:portal')->prefix('portal')->group(function () {
    Route::post('/logout', [PortalAuthController::class, 'logout'])->name('portal.logout');

    Route::get('/dashboard', function () {
        $user = auth('portal')->user();
        $clientId = $user->client_id;

        return Inertia::render('Portal/Dashboard', [
            'portalUser' => $user->load('client'),
            'metrics' => [
                'open_tickets' => Ticket::where('client_id', $clientId)->whereNotIn('status', ['resolved', 'closed'])->count(),
                'unpaid_invoices' => Invoice::where('client_id', $clientId)->whereNotIn('status', ['paid'])->count(),
                'recent_documents' => Contract::where('client_id', $clientId)->count(),
            ],
        ]);
    })->name('portal.dashboard');

    Route::get('/tickets', [TicketController::class, 'index'])->name('portal.tickets');
    Route::post('/tickets', [TicketController::class, 'store'])->name('portal.tickets.store');

    Route::get('/invoices', [InvoiceController::class, 'index'])->name('portal.invoices');

    Route::get('/documents', [DocumentController::class, 'index'])->name('portal.documents');
});
