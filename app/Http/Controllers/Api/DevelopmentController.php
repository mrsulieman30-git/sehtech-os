<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\ProjectNode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DevelopmentController extends Controller
{
    public function getBoardData(Request $request)
    {
        $user = Auth::user();
        
        $query = Project::with([
            'nodes',
            'tasks' => function ($q) {
                $q->with(['assignees:id,name,avatar', 'comments.user:id,name,avatar'])->orderBy('created_at', 'desc');
            }
        ]);

        if (!$user->role || ($user->role->name !== 'admin' && $user->role->name !== 'manager' && !$user->role->is_super_admin)) {
            $query->where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                  ->orWhereHas('tasks.assignees', function ($sq) use ($user) {
                      $sq->where('user_id', $user->id);
                  });
            });
        }

        $projects = $query->get();

        return response()->json([
            'projects' => $projects
        ]);
    }

    public function storeProject(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:projects,name',
            'description' => 'nullable|string',
        ]);

        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Project created successfully',
            'project' => $project->load('tasks')
        ], 201);
    }

    public function storeNode(Request $request, $id)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                \Illuminate\Validation\Rule::unique('project_nodes')->where(function ($query) use ($id, $request) {
                    return $query->where('project_id', $id)
                                 ->where('parent_id', $request->parent_id);
                })
            ],
            'type' => 'required|in:folder,board',
            'parent_id' => 'nullable|uuid|exists:project_nodes,id'
        ], [
            'name.unique' => 'A folder or board with this name already exists here.'
        ]);

        $project = Project::findOrFail($id);
        
        $node = ProjectNode::create([
            'project_id' => $project->id,
            'parent_id' => $request->parent_id,
            'name' => $request->name,
            'type' => $request->type
        ]);

        return response()->json(['message' => 'Node added', 'node' => $node]);
    }

    public function deleteNode($id)
    {
        $node = ProjectNode::findOrFail($id);
        $node->delete();
        return response()->json(['message' => 'Node deleted']);
    }

    public function moveNode(Request $request, $id)
    {
        $request->validate([
            'parent_id' => 'nullable|uuid|exists:project_nodes,id',
            'project_id' => 'required|uuid|exists:projects,id'
        ]);

        $node = ProjectNode::findOrFail($id);
        
        // Prevent moving a node into itself or its own children
        if ($request->parent_id === $node->id) {
            return response()->json(['message' => 'Cannot move node into itself'], 422);
        }

        $node->update([
            'parent_id' => $request->parent_id,
            'project_id' => $request->project_id
        ]);
        
        // If the node has children, we need to recursively update their project_id if it changed
        if ($node->wasChanged('project_id')) {
            $this->updateChildrenProjectId($node, $request->project_id);
        }

        return response()->json(['message' => 'Node moved', 'node' => $node]);
    }

    private function updateChildrenProjectId(ProjectNode $node, $projectId)
    {
        foreach ($node->children as $child) {
            $child->update(['project_id' => $projectId]);
            // Also update tasks in this node
            Task::where('node_id', $child->id)->update(['project_id' => $projectId]);
            $this->updateChildrenProjectId($child, $projectId);
        }
        Task::where('node_id', $node->id)->update(['project_id' => $projectId]);
    }

    public function mergeProject(Request $request)
    {
        $request->validate([
            'source_project_id' => 'required|uuid|exists:projects,id',
            'target_project_id' => 'required|uuid|exists:projects,id',
            'target_parent_id' => 'nullable|uuid|exists:project_nodes,id'
        ]);

        if ($request->source_project_id === $request->target_project_id) {
            return response()->json(['message' => 'Cannot merge project into itself'], 422);
        }

        $sourceProject = Project::with('nodes')->findOrFail($request->source_project_id);
        
        // 1. Create a new folder in target project representing the source project
        $rootNode = ProjectNode::create([
            'project_id' => $request->target_project_id,
            'parent_id' => $request->target_parent_id,
            'name' => $sourceProject->name,
            'type' => 'folder'
        ]);

        // 2. Move all root nodes of the source project into this new root node
        $rootSourceNodes = $sourceProject->nodes->whereNull('parent_id');
        foreach ($rootSourceNodes as $node) {
            $node->update([
                'parent_id' => $rootNode->id,
                'project_id' => $request->target_project_id
            ]);
            $this->updateChildrenProjectId($node, $request->target_project_id);
        }

        // 3. Move all tasks that have NO node into this new folder (by converting the folder into a board temporarily, or just assigning them)
        // Wait, tasks without node? There shouldn't be any in the new structure, but just in case:
        Task::where('project_id', $sourceProject->id)
            ->whereNull('node_id')
            ->update([
                'project_id' => $request->target_project_id,
                'node_id' => $rootNode->id
            ]);

        // 4. Delete the source project
        $sourceProject->delete();

        return response()->json(['message' => 'Projects merged successfully']);
    }

    public function updateTaskStatus(Request $request, $taskId)
    {
        $request->validate([
            'status' => 'required|in:backlog,todo,in_progress,review,qa,done,deployed',
        ]);

        $task = Task::findOrFail($taskId);
        $task->status = $request->status;
        $task->save();

        return response()->json(['message' => 'Task status updated', 'task' => $task]);
    }

    // NEW METHOD: Create Task
    public function storeTask(Request $request)
    {
        $request->validate([
            'project_id' => 'required|uuid|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:p0,p1,p2,p3',
            'status' => 'required|in:backlog,todo,in_progress,review,qa,done,deployed',
            'due_date' => 'nullable|date',
            'story_points' => 'nullable|integer|min:1|max:100',
            'assignees' => 'nullable|array',
            'assignees.*' => 'exists:users,id',
            'node_id' => 'nullable|uuid|exists:project_nodes,id',
        ]);

        $task = Task::create([
            'project_id' => $request->project_id,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => $request->status,
            'due_date' => $request->due_date,
            'story_points' => $request->story_points,
            'node_id' => $request->node_id,
            'reporter_id' => Auth::id(),
            'created_by' => Auth::id(),
        ]);

        if ($request->has('assignees') && is_array($request->assignees) && count($request->assignees) > 0) {
            $task->assignees()->sync($request->assignees);
        } else {
            $task->assignees()->attach(Auth::id());
        }

        return response()->json([
            'message' => 'Task created successfully',
            'task' => $task->load(['assignees:id,name,avatar', 'comments.user:id,name,avatar'])
        ], 201);
    }

    public function updateTask(Request $request, $id)
    {
        $task = \App\Models\Task::findOrFail($id);
        $data = $request->validate([
            'title' => 'sometimes|string',
            'description' => 'nullable|string',
            'priority' => 'sometimes|in:p0,p1,p2,p3',
            'story_points' => 'nullable|integer',
            'due_date' => 'nullable|date',
            'assignees' => 'nullable|array',
            'assignees.*' => 'exists:users,id',
            'node_id' => 'nullable|uuid|exists:project_nodes,id',
        ]);
        
        $task->update($request->except('assignees'));

        if ($request->has('assignees')) {
            $task->assignees()->sync($request->assignees);
        }

        return response()->json(['message' => 'Task updated', 'task' => $task->load(['assignees:id,name,avatar', 'comments.user:id,name,avatar'])]);
    }

    public function addComment(Request $request, $id)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $task = \App\Models\Task::findOrFail($id);

        $comment = \App\Models\TaskComment::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'body' => $request->body,
        ]);

        return response()->json([
            'message' => 'Comment added',
            'comment' => $comment->load('user:id,name,avatar')
        ]);
    }
}
