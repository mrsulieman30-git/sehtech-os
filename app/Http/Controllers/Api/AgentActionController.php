<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Department;
use App\Models\Role;
use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentActionController extends Controller
{
    public function execute(Request $request)
    {
        // Require valid secret to verify this is coming from the python backend
        if ($request->bearerToken() !== config('services.python.secret')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $action = $request->input('action');
        $payload = is_string($request->input('payload')) ? json_decode($request->input('payload'), true) : $request->input('payload');
        $userId = $request->input('user_id');

        Log::info("Agent Action Executed", ['action' => $action, 'payload' => $payload, 'user_id' => $userId]);

        try {
            switch ($action) {
                case 'create_employee':
                    return $this->createEmployee($payload);
                case 'terminate_employee':
                    return $this->terminateEmployee($payload);
                default:
                    return response()->json(['error' => "Action '{$action}' is not supported."], 400);
            }
        } catch (\Exception $e) {
            Log::error("Agent Action Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function createEmployee($payload)
    {
        $email = $payload['email'] ?? null;
        if (!$email) throw new \Exception("Email is required");

        if (User::where('email', $email)->exists()) {
            throw new \Exception("A user with this email already exists.");
        }

        // Try to find the department if passed as string (name) or uuid
        $departmentId = null;
        if (!empty($payload['department'])) {
            $deptStr = strtolower(trim($payload['department']));
            if (\Illuminate\Support\Str::isUuid($deptStr)) {
                $department = Department::find($deptStr);
            } else {
                $department = Department::whereRaw("LOWER(name) LIKE ?", ["%{$deptStr}%"])->first();
            }
            if ($department) {
                $departmentId = $department->id;
            }
        }

        $user = User::create([
            'name' => $payload['name'] ?? 'New Employee',
            'email' => $email,
            'password' => Hash::make(Str::random(12)),
            'department_id' => $departmentId,
            'role_id' => null,
            'status' => 'active',
        ]);

        EmployeeProfile::create([
            'user_id' => $user->id,
            'job_title' => $payload['job_title'] ?? 'Employee',
            'salary' => $payload['salary'] ?? 0,
            'employment_type' => $payload['employment_type'] ?? 'full_time',
            'hire_date' => now()->toDateString(),
        ]);

        return response()->json([
            'message' => 'Employee successfully added.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department_id' => $departmentId
            ]
        ]);
    }

    private function terminateEmployee($payload)
    {
        $email = $payload['email'] ?? null;
        if (!$email) throw new \Exception("Email is required to identify the employee to terminate.");

        $user = User::where('email', $email)->first();
        if (!$user) {
            throw new \Exception("Employee not found with email: {$email}");
        }

        $user->update(['status' => 'inactive']);
        
        return response()->json([
            'message' => "Employee {$user->name} has been successfully terminated.",
            'user_id' => $user->id
        ]);
    }
}
