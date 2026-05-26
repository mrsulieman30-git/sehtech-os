<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Authenticated Application Routes (Desktop Shell)
Route::middleware(['auth'])->group(function () {
    
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::prefix('app')->group(function () {
        Route::get('/dashboard', function () { return Inertia::render('DesktopShell'); })->name('dashboard');
        
        Route::get('/research', function () { return Inertia::render('Departments/Research/Index'); })->name('app.research');
        Route::get('/development', function () { return Inertia::render('Departments/Development/Index'); })->name('app.development');
        Route::get('/marketing', function () { return Inertia::render('Departments/Marketing/Index'); })->name('app.marketing');
        Route::get('/sales', function () { return Inertia::render('Departments/Sales/Index'); })->name('app.sales');
        Route::get('/legal', function () { return Inertia::render('Departments/Legal/Index'); })->name('app.legal');
        Route::get('/finance', function () { return Inertia::render('Departments/Finance/Index'); })->name('app.finance');
        Route::get('/hr', function () { return Inertia::render('Departments/HR/Index'); })->name('app.hr');
        Route::get('/support', function () { return Inertia::render('Departments/Support/Index'); })->name('app.support');
        Route::get('/operations', function () { return Inertia::render('Departments/Operations/Index'); })->name('app.operations');
        
        Route::get('/files', function () { return Inertia::render('Modules/FileSystem/Index'); })->name('app.files');
        Route::get('/settings', function () { return Inertia::render('Modules/Settings/Index'); })->name('app.settings');
        Route::get('/calendar', function () { return Inertia::render('Modules/Calendar/Index'); })->name('app.calendar');
        Route::get('/agents', function () { return Inertia::render('Modules/Agents/ControlRoom'); })->name('app.agents');
    });

    // -------------------------------------------------------------
    // INTERNAL API ROUTES (Consumed by Vue Components)
    // -------------------------------------------------------------

    Route::prefix('api/admin')->group(function () {
        Route::get('/users', [\App\Http\Controllers\Api\AdminController::class, 'index'])->name('api.admin.users.index');
        Route::post('/users', [\App\Http\Controllers\Api\AdminController::class, 'store'])->name('api.admin.users.store');
        Route::put('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'update'])->name('api.admin.users.update');
        Route::delete('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'destroy'])->name('api.admin.users.destroy');
        Route::get('/roles', [\App\Http\Controllers\Api\AdminController::class, 'getRoles'])->name('api.admin.roles.index');
        Route::post('/roles', [\App\Http\Controllers\Api\AdminController::class, 'storeRole'])->name('api.admin.roles.store');
        Route::put('/roles/{id}', [\App\Http\Controllers\Api\AdminController::class, 'updateRole'])->name('api.admin.roles.update');
    });

    Route::prefix('api/files')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\FileController::class, 'index'])->name('api.files.index');
        
        // Chunked Upload Routes
        Route::get('/upload/status', [\App\Http\Controllers\Api\FileController::class, 'chunkStatus'])->name('api.files.chunk.status');
        Route::post('/upload/chunk', [\App\Http\Controllers\Api\FileController::class, 'uploadChunk'])->name('api.files.chunk.upload');
        Route::post('/upload/complete', [\App\Http\Controllers\Api\FileController::class, 'uploadComplete'])->name('api.files.chunk.complete');
        
        // Legacy single upload (keeping for fallback if needed)
        Route::post('/upload', [\App\Http\Controllers\Api\FileController::class, 'upload'])->name('api.files.upload');
        
        // File Operations
        Route::post('/copy', [\App\Http\Controllers\Api\FileController::class, 'copyItem'])->name('api.files.copy');
        Route::post('/move', [\App\Http\Controllers\Api\FileController::class, 'moveItem'])->name('api.files.move');
        Route::delete('/delete', [\App\Http\Controllers\Api\FileController::class, 'deleteItem'])->name('api.files.delete');
        
        // Trash Operations
        Route::get('/trash', [\App\Http\Controllers\Api\FileController::class, 'getTrash'])->name('api.files.trash.get');
        Route::post('/trash/{id}/restore', [\App\Http\Controllers\Api\FileController::class, 'restoreTrash'])->name('api.files.trash.restore');
        
        Route::post('/folders', [\App\Http\Controllers\Api\FileController::class, 'createFolder'])->name('api.files.folders.create');
        Route::get('/preview', [\App\Http\Controllers\Api\FileController::class, 'preview'])->name('api.files.preview');
        Route::get('/access', [\App\Http\Controllers\Api\FileController::class, 'getAccess'])->name('api.files.access.get');
        Route::post('/access', [\App\Http\Controllers\Api\FileController::class, 'setAccess'])->name('api.files.access.set');
    });

    Route::prefix('api/research')->group(function () {
        Route::get('/ideas', [\App\Http\Controllers\Api\ResearchController::class, 'index'])->name('api.research.ideas');
        Route::post('/ideas', [\App\Http\Controllers\Api\ResearchController::class, 'store'])->name('api.research.ideas.store');
        Route::put('/ideas/{id}', [\App\Http\Controllers\Api\ResearchController::class, 'update'])->name('api.research.ideas.update');
        Route::put('/ideas/{id}/status', [\App\Http\Controllers\Api\ResearchController::class, 'updateStatus'])->name('api.research.ideas.status');
        Route::post('/ideas/{id}/comments', [\App\Http\Controllers\Api\ResearchController::class, 'addComment'])->name('api.research.comments.store');
        Route::post('/ideas/{id}/vote', [\App\Http\Controllers\Api\ResearchController::class, 'vote'])->name('api.research.ideas.vote');
        Route::post('/ideas/{id}/convert', [\App\Http\Controllers\Api\ResearchController::class, 'convertToProject'])->name('api.research.ideas.convert');
    });

    Route::prefix('api/development')->group(function () {
        Route::get('/board', [\App\Http\Controllers\Api\DevelopmentController::class, 'getBoardData'])->name('api.dev.board');
        Route::put('/tasks/{taskId}/status', [\App\Http\Controllers\Api\DevelopmentController::class, 'updateTaskStatus'])->name('api.dev.task.status');
        Route::post('/tasks', [\App\Http\Controllers\Api\DevelopmentController::class, 'storeTask'])->name('api.dev.task.store');
        Route::put('/tasks/{id}', [\App\Http\Controllers\Api\DevelopmentController::class, 'updateTask'])->name('api.dev.tasks.update');
        Route::post('/tasks/{id}/comments', [\App\Http\Controllers\Api\DevelopmentController::class, 'addComment'])->name('api.dev.tasks.comments');
        Route::delete('/tasks/{id}', [\App\Http\Controllers\Api\DevelopmentController::class, 'deleteTask'])->name('api.dev.tasks.delete');
        Route::post('/projects', [\App\Http\Controllers\Api\DevelopmentController::class, 'storeProject'])->name('api.dev.projects.store');
        Route::post('/projects/merge', [\App\Http\Controllers\Api\DevelopmentController::class, 'mergeProject'])->name('api.dev.projects.merge');
        Route::post('/projects/{id}/nodes', [\App\Http\Controllers\Api\DevelopmentController::class, 'storeNode'])->name('api.dev.projects.nodes.store');
        Route::put('/nodes/{id}/move', [\App\Http\Controllers\Api\DevelopmentController::class, 'moveNode'])->name('api.dev.nodes.move');
        Route::delete('/nodes/{id}', [\App\Http\Controllers\Api\DevelopmentController::class, 'deleteNode'])->name('api.dev.nodes.delete');
        Route::get('/grants', [\App\Http\Controllers\Api\DevelopmentController::class, 'getGrants'])->name('api.dev.grants');
        Route::post('/grants/sync', [\App\Http\Controllers\Api\DevelopmentController::class, 'syncGrants'])->name('api.dev.grants.sync');
    });

    Route::prefix('api/ide')->group(function () {
        Route::get('/url', function () {
            $user = \Illuminate\Support\Facades\Auth::user();
            
            // Provide the root directory as the default folder
            $query = '?folder=/home/coder/project';

            return response()->json([
                'url' => 'http://localhost:8080' . $query
            ]);
        })->name('api.ide.url');
    });

    // Marketing Department
    Route::prefix('api/marketing')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\MarketingController::class, 'getDashboard'])->name('api.marketing.dashboard');
        Route::get('/crm', [\App\Http\Controllers\Api\MarketingController::class, 'getMarketingData'])->name('api.marketing.crm');
        Route::post('/contents', [\App\Http\Controllers\Api\MarketingController::class, 'storeContent'])->name('api.marketing.contents.store');
        Route::post('/competitors', [\App\Http\Controllers\Api\MarketingController::class, 'storeCompetitor'])->name('api.marketing.competitors.store');
    });

    Route::prefix('api/sales')->group(function () {
        Route::get('/crm', [\App\Http\Controllers\Api\SalesController::class, 'getSalesData'])->name('api.sales.crm');
        Route::post('/accounts', [\App\Http\Controllers\Api\SalesController::class, 'storeAccount'])->name('api.sales.accounts.store');
        Route::get('/accounts/{id}', [\App\Http\Controllers\Api\SalesController::class, 'showAccount'])->name('api.sales.accounts.show');
        Route::put('/accounts/{id}', [\App\Http\Controllers\Api\SalesController::class, 'updateAccount'])->name('api.sales.accounts.update');
        Route::post('/contacts', [\App\Http\Controllers\Api\SalesController::class, 'storeContact'])->name('api.sales.contacts.store');
        Route::put('/contacts/{id}', [\App\Http\Controllers\Api\SalesController::class, 'updateContact'])->name('api.sales.contacts.update');
        Route::delete('/contacts/{id}', [\App\Http\Controllers\Api\SalesController::class, 'destroyContact'])->name('api.sales.contacts.destroy');
        Route::post('/deals', [\App\Http\Controllers\Api\SalesController::class, 'storeDeal'])->name('api.sales.deals.store');
        Route::put('/deals/{id}', [\App\Http\Controllers\Api\SalesController::class, 'updateDeal'])->name('api.sales.deals.update');
        Route::get('/deals/{id}', [\App\Http\Controllers\Api\SalesController::class, 'showDeal'])->name('api.sales.deals.show');
        Route::put('/deals/{id}', [\App\Http\Controllers\Api\SalesController::class, 'updateDeal'])->name('api.sales.deals.update');
        Route::put('/deals/{id}/stage', [\App\Http\Controllers\Api\SalesController::class, 'updateDealStage'])->name('api.sales.deals.stage');
        Route::get('/clients/list', [\App\Http\Controllers\Api\SalesController::class, 'getClientsList'])->name('api.sales.clients.list');
    });

    Route::prefix('api/legal')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\LegalController::class, 'getDashboard'])->name('api.legal.dashboard');
        Route::post('/contracts/generate', [\App\Http\Controllers\Api\LegalController::class, 'generateContractDraft'])->name('api.legal.contracts.generate');
        Route::post('/risks', [\App\Http\Controllers\Api\LegalController::class, 'storeRisk'])->name('api.legal.risks.store');
        Route::put('/contracts/{id}', [\App\Http\Controllers\Api\LegalController::class, 'updateContract'])->name('api.legal.contracts.update');
    });

    Route::prefix('api/finance')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\FinanceController::class, 'getDashboard'])->name('api.finance.dashboard');
        Route::post('/invoices', [\App\Http\Controllers\Api\FinanceController::class, 'storeInvoice'])->name('api.finance.invoice.store');
        Route::post('/payments', [\App\Http\Controllers\Api\FinanceController::class, 'storePayment'])->name('api.finance.payment.store');
        
        // Bills Payable & Operating Expenses
        Route::get('/bills', [\App\Http\Controllers\Api\FinanceController::class, 'getBills'])->name('api.finance.bills');
        Route::post('/bills', [\App\Http\Controllers\Api\FinanceController::class, 'storeBill'])->name('api.finance.bills.store');
        Route::put('/bills/{id}', [\App\Http\Controllers\Api\FinanceController::class, 'updateBill'])->name('api.finance.bills.update');
        Route::put('/bills/{id}/pay', [\App\Http\Controllers\Api\FinanceController::class, 'payBill'])->name('api.finance.bills.pay');
        Route::put('/bills/{id}/void', [\App\Http\Controllers\Api\FinanceController::class, 'voidBill'])->name('api.finance.bills.void');
        Route::put('/bills/{id}/cancel', [\App\Http\Controllers\Api\FinanceController::class, 'cancelBill'])->name('api.finance.bills.cancel');
        Route::delete('/bills/{id}', [\App\Http\Controllers\Api\FinanceController::class, 'destroyBill'])->name('api.finance.bills.destroy');

        // Synergy: Sales & HR
        Route::get('/synergy/won-deals', [\App\Http\Controllers\Api\FinanceController::class, 'getWonDealsQueue'])->name('api.finance.synergy.won-deals');
        Route::get('/synergy/payroll', [\App\Http\Controllers\Api\FinanceController::class, 'getPayrollMetrics'])->name('api.finance.synergy.payroll');
        Route::post('/synergy/payroll/post', [\App\Http\Controllers\Api\FinanceController::class, 'postPayrollExpense'])->name('api.finance.synergy.payroll.post');
    });

    Route::prefix('api/hr')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\HrController::class, 'getDashboard'])->name('api.hr.dashboard');
        Route::get('/metadata', [\App\Http\Controllers\Api\HrController::class, 'getMetadata'])->name('api.hr.metadata');
        Route::post('/employees', [\App\Http\Controllers\Api\HrController::class, 'storeEmployee'])->name('api.hr.employees.store');
        Route::get('/employees/{id}', [\App\Http\Controllers\Api\HrController::class, 'getEmployee'])->name('api.hr.employees.show');
        Route::put('/employees/{id}', [\App\Http\Controllers\Api\HrController::class, 'updateEmployee'])->name('api.hr.employees.update');
        Route::delete('/employees/{id}', [\App\Http\Controllers\Api\HrController::class, 'destroyEmployee'])->name('api.hr.employees.destroy');
        Route::put('/employees/{id}/terminate', [\App\Http\Controllers\Api\HrController::class, 'terminateEmployee'])->name('api.hr.employees.terminate');
        Route::put('/employees/{id}/salary', [\App\Http\Controllers\Api\HrController::class, 'updateEmployeeSalary'])->name('api.hr.employees.salary');
        Route::post('/leave', [\App\Http\Controllers\Api\HrController::class, 'storeLeaveRequest'])->name('api.hr.leave.store');
        Route::put('/leave/{id}/status', [\App\Http\Controllers\Api\HrController::class, 'updateLeaveRequestStatus'])->name('api.hr.leave.status');
        Route::post('/performance', [\App\Http\Controllers\Api\HrController::class, 'storePerformanceReview'])->name('api.hr.performance.store');
        Route::get('/performance/{userId}', [\App\Http\Controllers\Api\HrController::class, 'getPerformanceReviews'])->name('api.hr.performance.show');
    });

    Route::prefix('api/support')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\SupportController::class, 'getDashboard'])->name('api.support.dashboard');
        Route::post('/tickets', [\App\Http\Controllers\Api\SupportController::class, 'createTicket'])->name('api.support.tickets.create');
        Route::post('/incidents', [\App\Http\Controllers\Api\SupportController::class, 'createIncident'])->name('api.support.incidents.create');
        Route::post('/kb-articles', [\App\Http\Controllers\Api\SupportController::class, 'createKbArticle'])->name('api.support.kb.create');
        Route::post('/tickets/{id}/resolve', [\App\Http\Controllers\Api\SupportController::class, 'resolveTicket'])->name('api.support.tickets.resolve');
        Route::post('/tickets/{id}/escalate', [\App\Http\Controllers\Api\SupportController::class, 'escalateTicket'])->name('api.support.tickets.escalate');
    });

    Route::prefix('api/operations')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\OperationsController::class, 'getDashboard'])->name('api.operations.dashboard');
        Route::post('/assets', [\App\Http\Controllers\Api\OperationsController::class, 'storeAsset'])->name('api.operations.assets.store');
    });

    Route::prefix('api/settings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SettingsController::class, 'getSettingsData'])->name('api.settings.index');
        Route::post('/test-smtp', [\App\Http\Controllers\Api\SettingsController::class, 'testSmtp'])->name('api.settings.test-smtp');
        Route::put('/{key}', [\App\Http\Controllers\Api\SettingsController::class, 'updateSetting'])->name('api.settings.update');
    });

    Route::prefix('api/forms')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\FormBuilderController::class, 'index'])->name('api.forms.index');
        Route::post('/', [\App\Http\Controllers\Api\FormBuilderController::class, 'store'])->name('api.forms.store');
    });

    Route::prefix('api/calendar')->group(function () {
        Route::get('/events', [\App\Http\Controllers\Api\CalendarController::class, 'getEvents'])->name('api.calendar.events');
        Route::post('/meetings', [\App\Http\Controllers\Api\CalendarController::class, 'storeMeeting'])->name('api.calendar.meetings.store');
    });

    Route::prefix('api/agents')->group(function () {
        Route::post('/{agentId}/chat', [\App\Http\Controllers\Api\AgentController::class, 'chat'])->name('api.agents.chat');
    });

    // Global AI routes
    Route::prefix('api/agents')->group(function () {
        Route::post('/master-chat', [\App\Http\Controllers\Api\AgentController::class, 'masterChat'])->name('api.ai.master-chat');
        Route::get('/master-chat/history', [\App\Http\Controllers\Api\AgentController::class, 'getMasterChatHistory'])->name('api.ai.master-chat.history');
    });

    Route::prefix('api/internal')->group(function () {
        Route::post('/agent-action', [\App\Http\Controllers\Api\AgentActionController::class, 'execute'])->withoutMiddleware(['auth'])->name('api.internal.agent-action');
    });

    // Notifications
    Route::prefix('api/notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\NotificationController::class, 'index'])->name('api.notifications.index');
        Route::post('/test', [\App\Http\Controllers\Api\NotificationController::class, 'testNotification'])->name('api.notifications.test');
        Route::post('/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead'])->name('api.notifications.read_all');
        Route::post('/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead'])->name('api.notifications.read');
        Route::delete('/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy'])->name('api.notifications.destroy');
    });
});
