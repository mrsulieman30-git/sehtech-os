<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketingCampaign;
use App\Models\Competitor;
use App\Models\CrmAccount;
use App\Models\CrmContact;
use App\Models\CrmContent;
use App\Models\CrmChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketingController extends Controller
{
    public function getDashboard(Request $request)
    {
        $campaigns = MarketingCampaign::with('manager:id,name,avatar')
            ->orderBy('start_date', 'desc')
            ->get();

        $competitors = Competitor::orderBy('name', 'asc')->get();

        $metrics = [
            'total_campaigns' => $campaigns->count(),
            'active_campaigns' => $campaigns->where('status', 'active')->count(),
            'total_budget' => $campaigns->sum('budget'),
        ];

        return response()->json([
            'campaigns' => $campaigns->values(),
            'competitors' => $competitors,
            'metrics' => $metrics
        ]);
    }

    public function storeContent(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'type' => 'required|string',
            'status' => 'required|string',
            'url' => 'nullable|url'
        ]);

        $content = \App\Models\CrmContent::create(array_merge($request->all(), [
            'created_by' => Auth::id()
        ]));

        return response()->json(['message' => 'Content created', 'content' => $content]);
    }

    public function storeCompetitor(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'product_category' => 'required|string|max:255',
            'pricing_tier' => 'nullable|string|max:255',
            'strengths' => 'nullable|string',
            'weaknesses' => 'nullable|string',
        ]);

        $competitor = Competitor::create($request->all());

        return response()->json(['message' => 'Competitor added', 'competitor' => $competitor]);
    }

    public function getMarketingData(Request $request)
    {
        // Marketing gets top-level overview of generated Accounts and Content/Channels
        $accounts = CrmAccount::with('contacts')->orderBy('created_at', 'desc')->take(20)->get();
        $contents = CrmContent::with('creator:id,name,avatar')->orderBy('created_at', 'desc')->get();
        $channels = CrmChannel::orderBy('name')->get();

        return response()->json([
            'accounts' => $accounts,
            'contents' => $contents,
            'channels' => $channels
        ]);
    }
}
