<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InfrastructureAsset;
use Illuminate\Http\Request;

class OperationsController extends Controller
{
    public function getDashboard(Request $request)
    {
        $assets = InfrastructureAsset::orderBy('expiry_date', 'asc')->get();

        return response()->json([
            'assets' => $assets,
            'metrics' => [
                'total_assets' => $assets->count(),
                'expiring_30_days' => $assets->where('expiry_date', '<=', now()->addDays(30))->count(),
                'monthly_cost' => $assets->sum('cost'),
            ]
        ]);
    }

    public function storeAsset(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:domain,server,ssl,license,saas',
            'provider' => 'required|string|max:255',
            'expiry_date' => 'required|date',
            'cost' => 'nullable|numeric',
        ]);

        $asset = InfrastructureAsset::create($request->all());

        return response()->json(['message' => 'Asset tracked', 'asset' => $asset]);
    }
}
