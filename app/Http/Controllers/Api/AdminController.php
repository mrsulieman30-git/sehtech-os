<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('role', 'department', 'employeeProfile')->orderBy('created_at', 'desc')->get();
        $departments = \App\Models\Department::orderBy('name')->get();
        return response()->json([
            'users' => $users,
            'departments' => $departments
        ]);
    }

    public function store(Request $request)
    {
        if ($request->input('role_id') === '') {
            $request->merge(['role_id' => null]);
        }
        if ($request->input('department_id') === '') {
            $request->merge(['department_id' => null]);
        }

        $request->merge(['email' => strtolower($request->email)]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'status' => 'required|in:active,inactive,suspended',
            'role_id' => 'nullable|uuid|exists:roles,id',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'job_title' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|in:full_time,part_time,contract,intern',
            'hire_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => $request->status,
            'role_id' => $request->role_id,
            'department_id' => $request->department_id,
        ]);

        // Auto-provision a default EmployeeProfile if a department is assigned
        if ($user->department_id) {
            $user->load('role');
            $roleName = $user->role->name ?? 'Employee';
            \App\Models\EmployeeProfile::create([
                'user_id' => $user->id,
                'job_title' => $request->job_title ?? $roleName,
                'employment_type' => $request->employment_type ?? 'full_time',
                'hire_date' => $request->hire_date ?? now()->format('Y-m-d'),
                'salary' => $request->salary ?? 0,
                'annual_leave_balance' => 21,
                'sick_leave_balance' => 14,
            ]);
        }

        return response()->json(['message' => 'User created successfully', 'user' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($request->input('role_id') === '') {
            $request->merge(['role_id' => null]);
        }
        if ($request->input('department_id') === '') {
            $request->merge(['department_id' => null]);
        }

        $request->merge(['email' => strtolower($request->email)]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:active,inactive,suspended',
            'role_id' => 'nullable|uuid|exists:roles,id',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'job_title' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|in:full_time,part_time,contract,intern',
            'hire_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'status' => $request->status,
            'role_id' => $request->role_id,
            'department_id' => $request->department_id,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Auto-provision or update EmployeeProfile
        if ($user->department_id) {
            $user->load('role');
            $roleName = $user->role->name ?? 'Employee';
            $profile = \App\Models\EmployeeProfile::firstOrNew(['user_id' => $user->id]);
            
            if (!$profile->exists) {
                $profile->fill([
                    'job_title' => $request->job_title ?? $roleName,
                    'employment_type' => $request->employment_type ?? 'full_time',
                    'hire_date' => $request->hire_date ?? now()->format('Y-m-d'),
                    'salary' => $request->salary ?? 0,
                    'annual_leave_balance' => 21,
                    'sick_leave_balance' => 14,
                ]);
            } else {
                if ($request->filled('job_title')) $profile->job_title = $request->job_title;
                if ($request->filled('employment_type')) $profile->employment_type = $request->employment_type;
                if ($request->filled('hire_date')) $profile->hire_date = $request->hire_date;
                if ($request->filled('salary')) $profile->salary = $request->salary;
            }
            $profile->save();
        }

        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Safety: Do not delete the default master admin account
        if ($user->email === 'admin@sehtech.com') {
            return response()->json(['message' => 'Cannot delete the default system administrator account'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
    public function getRoles()
    {
        $roles = \App\Models\Role::withCount('users')->get();
        return response()->json(['roles' => $roles]);
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'permissions' => 'required|array',
        ]);

        $role = \App\Models\Role::create([
            'name' => $request->name,
            'permissions' => $request->permissions,
            'is_super_admin' => false,
        ]);

        return response()->json(['message' => 'Role created successfully', 'role' => $role]);
    }

    public function updateRole(Request $request, $id)
    {
        $role = \App\Models\Role::findOrFail($id);
        
        // Prevent editing super admin permissions from UI
        if ($role->is_super_admin) {
            return response()->json(['message' => 'Cannot modify Super Admin role'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'permissions' => 'required|array',
        ]);

        $role->update([
            'name' => $request->name,
            'permissions' => $request->permissions,
        ]);

        return response()->json(['message' => 'Role updated successfully', 'role' => $role]);
    }
}
