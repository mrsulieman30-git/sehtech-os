<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function index()
    {
        $clientId = Auth::guard('portal')->user()->client_id;

        $documents = Contract::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Portal/Documents', [
            'documents' => $documents,
        ]);
    }
}
