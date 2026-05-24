<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ResearchIdea;
use App\Models\ResearchComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResearchController extends Controller
{
    public function index(Request $request)
    {
        $ideas = ResearchIdea::with(['author:id,name,avatar', 'comments.user:id,name,avatar'])
            ->withCount('comments')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'ideas' => $ideas
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'required|string|max:500',
            'content' => 'nullable|string',
            'category' => 'required|in:hms,clinic,pharmacy,dental,inventory,video,internal,other',
            'priority' => 'nullable|in:P0,P1,P2',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $idea = ResearchIdea::create([
            'title' => $request->title,
            'summary' => $request->summary,
            'category' => $request->category,
            'status' => 'draft',
            'content' => $request->content ?? '',
            'priority' => $request->priority ?? 'P2',
            'tags' => $request->tags ?? [],
            'author_id' => Auth::id(),
        ]);

        return response()->json(['message' => 'Research draft created', 'idea' => $idea->load('author:id,name,avatar')]);
    }

    public function update(Request $request, $id)
    {
        $idea = ResearchIdea::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'summary' => 'sometimes|string|max:500',
            'content' => 'nullable|string',
            'category' => 'sometimes|in:hms,clinic,pharmacy,dental,inventory,video,internal,other',
            'priority' => 'sometimes|in:P0,P1,P2',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $idea->update($request->only(['title', 'summary', 'content', 'category', 'priority', 'tags']));

        return response()->json(['message' => 'Research idea updated', 'idea' => $idea]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,under_review,approved,rejected,in_development',
        ]);

        $idea = ResearchIdea::findOrFail($id);
        $idea->status = $request->status;
        $idea->save();

        return response()->json(['message' => 'Status updated', 'idea' => $idea]);
    }

    public function addComment(Request $request, $id)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $comment = ResearchComment::create([
            'idea_id' => $id,
            'user_id' => Auth::id(),
            'body' => $request->body,
        ]);

        return response()->json(['message' => 'Comment added', 'comment' => $comment->load('user:id,name,avatar')]);
    }

    public function vote(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:up,down',
        ]);

        $idea = ResearchIdea::findOrFail($id);
        
        if ($request->type === 'up') {
            $idea->increment('vote_count');
        } else {
            $idea->decrement('vote_count');
        }

        return response()->json(['message' => 'Vote recorded', 'vote_count' => $idea->vote_count]);
    }

    public function convertToProject(Request $request, $id)
    {
        $idea = ResearchIdea::findOrFail($id);

        if ($idea->status !== 'approved') {
            return response()->json(['message' => 'Only approved ideas can be converted.'], 400);
        }

        $project = \App\Models\Project::create([
            'name' => $idea->title,
            'description' => $idea->summary,
            'type' => 'internal', // Default type
            'status' => 'planning',
            'manager_id' => Auth::id(),
            'created_by' => Auth::id(),
            'tech_stack' => [],
            'meta' => ['source_idea_id' => $idea->id],
        ]);

        $idea->status = 'in_development';
        $idea->save();

        return response()->json([
            'message' => 'Idea successfully converted to a project!', 
            'project' => $project,
            'idea' => $idea
        ]);
    }
}
