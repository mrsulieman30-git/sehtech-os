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
        $users = User::with('role', 'department')->orderBy('created_at', 'desc')->get();
        return response()->json(['users' => $users]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => $request->status,
        ]);

        return response()->json(['message' => 'User created successfully', 'user' => $user]);
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
