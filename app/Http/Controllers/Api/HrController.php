<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmployeeProfile;
use App\Models\LeaveRequest;
use App\Models\PerformanceReview;
use App\Models\Department;
use App\Models\Role;
use App\Models\Contract;
use App\Models\InfrastructureAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class HrController extends Controller
{
    public function getDashboard(Request $request)
    {
        // Fetch users with their HR profile, department, and role
        $employees = User::with(['department:id,name,primary_color', 'role:id,name', 'employeeProfile'])
            ->whereHas('employeeProfile')
            ->where('status', 'active')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'status' => $user->status,
                    'department' => $user->department,
                    'role' => $user->role,
                    'job_title' => $user->employeeProfile->job_title ?? 'N/A',
                    'hire_date' => $user->employeeProfile->hire_date ?? null,
                    'salary' => $user->employeeProfile->salary ?? 0,
                    'employment_type' => $user->employeeProfile->employment_type ?? 'full_time',
                    'manager_id' => $user->employeeProfile->manager_id ?? null,
                    'annual_leave_balance' => $user->employeeProfile->annual_leave_balance ?? 21,
                    'sick_leave_balance' => $user->employeeProfile->sick_leave_balance ?? 14,
                    'national_id' => $user->employeeProfile->national_id ?? null,
                    'tax_id' => $user->employeeProfile->tax_id ?? null,
                    'bank_details' => $user->employeeProfile->bank_details ?? null,
                    'emergency_contact' => $user->employeeProfile->emergency_contact ?? null,
                ];
            });

        // Fetch all leave requests (pending first, then recent)
        $leaveRequests = LeaveRequest::with('user:id,name,avatar')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        // Average performance rating across the company
        $avgRating = PerformanceReview::avg('rating') ?? 0;

        // Total annual payroll cost
        $totalPayroll = EmployeeProfile::whereHas('user', function($q) {
            $q->where('status', 'active');
        })->sum('salary');

        return response()->json([
            'employees' => $employees,
            'leave_requests' => $leaveRequests,
            'metrics' => [
                'total_employees' => $employees->count(),
                'on_leave_today' => LeaveRequest::where('status', 'approved')
                                        ->where('start_date', '<=', now())
                                        ->where('end_date', '>=', now())
                                        ->count(),
                'pending_requests' => LeaveRequest::where('status', 'pending')->count(),
                'avg_performance' => round($avgRating, 1),
                'total_annual_payroll' => $totalPayroll,
            ]
        ]);
    }

    /**
     * Provide dropdown metadata for onboarding forms: departments, roles, managers.
     */
    public function getMetadata()
    {
        $departments = Department::select('id', 'name')->where('is_active', true)->orderBy('name')->get();
        $roles = Role::select('id', 'name')->orderBy('name')->get();
        $managers = User::select('id', 'name')
            ->where('status', 'active')
            ->whereHas('employeeProfile')
            ->orderBy('name')
            ->get();

        return response()->json([
            'departments' => $departments,
            'roles' => $roles,
            'managers' => $managers,
        ]);
    }

    /**
     * Unified Employee Onboarding: creates User + EmployeeProfile in a single transaction.
     * Optional synergy hooks: auto-draft employment contract (Legal), request hardware bundle (Operations).
     */
    public function storeEmployee(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'role_id' => 'nullable|uuid|exists:roles,id',
            'job_title' => 'required|string|max:255',
            'employment_type' => 'required|string|in:full_time,part_time,contract,intern',
            'hire_date' => 'required|date',
            'salary' => 'required|numeric|min:0',
            'manager_id' => 'nullable|uuid|exists:users,id',
            'national_id' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:100',
            'bank_routing' => 'nullable|string|max:100',
            'emergency_name' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:50',
            'emergency_relationship' => 'nullable|string|max:100',
            'draft_contract' => 'nullable|boolean',
            'hardware_bundle' => 'nullable|boolean',
        ]);

        $result = DB::transaction(function () use ($request) {
            // Generate a secure default password
            $defaultPassword = 'Welcome@' . date('Y');

            // 1. Create the system User account
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($defaultPassword),
                'department_id' => $request->department_id,
                'role_id' => $request->role_id,
                'status' => 'active',
                'created_by' => Auth::id(),
            ]);

            // 2. Create the EmployeeProfile
            $bankDetails = null;
            if ($request->filled('bank_name') || $request->filled('bank_account')) {
                $bankDetails = [
                    'bank_name' => $request->bank_name,
                    'account_number' => $request->bank_account,
                    'routing_number' => $request->bank_routing,
                ];
            }

            $emergencyContact = null;
            if ($request->filled('emergency_name') || $request->filled('emergency_phone')) {
                $emergencyContact = [
                    'name' => $request->emergency_name,
                    'phone' => $request->emergency_phone,
                    'relationship' => $request->emergency_relationship,
                ];
            }

            $profile = EmployeeProfile::create([
                'user_id' => $user->id,
                'manager_id' => $request->manager_id,
                'job_title' => $request->job_title,
                'employment_type' => $request->employment_type,
                'hire_date' => $request->hire_date,
                'salary' => $request->salary,
                'national_id' => $request->national_id,
                'tax_id' => $request->tax_id,
                'bank_details' => $bankDetails,
                'emergency_contact' => $emergencyContact,
                'annual_leave_balance' => 21,
                'sick_leave_balance' => 14,
            ]);

            // 3. Synergy: Legal — Auto-draft Employment Contract
            if ($request->input('draft_contract', false)) {
                try {
                    Contract::create([
                        'title' => "Employment Agreement — {$request->name}",
                        'status' => 'draft',
                        'start_date' => $request->hire_date,
                        'end_date' => Carbon::parse($request->hire_date)->addYear()->format('Y-m-d'),
                        'created_by' => Auth::id(),
                    ]);
                } catch (\Exception $e) {
                    // Non-critical: log and continue
                    \Log::warning('Failed to auto-draft employment contract: ' . $e->getMessage());
                }
            }

            // 4. Synergy: Operations — Request Standard Hardware Bundle
            if ($request->input('hardware_bundle', false)) {
                try {
                    InfrastructureAsset::create([
                        'name' => "Laptop Bundle — {$request->name}",
                        'type' => 'hardware',
                        'provider' => 'IT Procurement',
                        'cost' => 1200.00,
                        'status' => 'requested',
                        'expiry_date' => Carbon::now()->addYears(3)->format('Y-m-d'),
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Failed to auto-request hardware bundle: ' . $e->getMessage());
                }
            }

            return [
                'user' => $user,
                'profile' => $profile,
                'default_password' => $defaultPassword,
            ];
        });

        // 3. Send the Welcome Email
        try {
            \Illuminate\Support\Facades\Mail::to($result['user']->email)->send(new \App\Mail\WelcomeCredentialsMail($result['user'], $result['default_password']));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send welcome email to ' . $result['user']->email . '. Error: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Employee onboarded successfully!',
            'user' => $result['user'],
            'profile' => $result['profile'],
            'credentials' => [
                'email' => $result['user']->email,
                'default_password' => $result['default_password'],
            ]
        ]);
    }

    /**
     * Fetch a single employee's full profile including leave history and performance reviews.
     */
    public function getEmployee($id)
    {
        $user = User::with(['department:id,name,primary_color', 'role:id,name', 'employeeProfile'])
            ->findOrFail($id);

        $profile = $user->employeeProfile;
        $manager = $profile && $profile->manager_id
            ? User::select('id', 'name', 'avatar')->find($profile->manager_id)
            : null;

        $leaveHistory = LeaveRequest::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get();

        $performanceReviews = PerformanceReview::where('user_id', $id)
            ->with('reviewer:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'user' => $user,
            'manager' => $manager,
            'leave_history' => $leaveHistory,
            'performance_reviews' => $performanceReviews,
        ]);
    }

    public function storeLeaveRequest(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:annual,sick,maternity,paternity,unpaid,bereavement',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);
        $data['user_id'] = Auth::id();
        $data['total_days'] = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;
        $data['status'] = 'pending';

        $leave = LeaveRequest::create($data);
        return response()->json(['message' => 'Leave request submitted', 'leave' => $leave]);
    }

    /**
     * Approve or reject a leave request. On approval, automatically deducts
     * leave days from the employee's annual or sick leave balance.
     */
    public function updateLeaveRequestStatus(Request $request, $id)
    {
        $leave = LeaveRequest::findOrFail($id);
        $data = $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $leave->update([
            'status' => $data['status'],
            'reviewed_by' => Auth::id(),
        ]);

        // Auto-deduct leave balances on approval
        if ($data['status'] === 'approved') {
            $profile = EmployeeProfile::where('user_id', $leave->user_id)->first();
            if ($profile) {
                $days = (int) $leave->total_days;
                if (in_array($leave->type, ['annual', 'maternity', 'paternity', 'bereavement', 'unpaid'])) {
                    $profile->annual_leave_balance = max(0, $profile->annual_leave_balance - $days);
                } elseif ($leave->type === 'sick') {
                    $profile->sick_leave_balance = max(0, $profile->sick_leave_balance - $days);
                }
                $profile->save();
            }
        }

        return response()->json(['message' => 'Leave status updated', 'leave' => $leave]);
    }

    public function updateEmployeeSalary(Request $request, $id)
    {
        $request->validate([
            'salary' => 'required|numeric|min:0',
            'job_title' => 'nullable|string'
        ]);

        $profile = EmployeeProfile::where('user_id', $id)->firstOrFail();

        $updateData = ['salary' => $request->salary];
        if ($request->filled('job_title')) {
            $updateData['job_title'] = $request->job_title;
        }

        $profile->update($updateData);

        return response()->json([
            'message' => 'Employee details updated successfully!',
            'profile' => $profile
        ]);
    }

    /**
     * Record a performance review for an employee.
     */
    public function storePerformanceReview(Request $request)
    {
        $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'review_cycle' => 'required|string|max:100',
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'required|string',
            'goals' => 'nullable|array',
        ]);

        $review = PerformanceReview::create([
            'user_id' => $request->user_id,
            'reviewer_id' => Auth::id(),
            'review_cycle' => $request->review_cycle,
            'rating' => $request->rating,
            'feedback' => $request->feedback,
            'goals' => $request->goals,
        ]);

        return response()->json(['message' => 'Performance review recorded successfully.', 'review' => $review]);
    }

    /**
     * Fetch all performance reviews for a given employee.
     */
    public function getPerformanceReviews($userId)
    {
        $reviews = PerformanceReview::where('user_id', $userId)
            ->with('reviewer:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reviews);
    }
    /**
     * Terminate an employee.
     */
    public function terminateEmployee(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'termination_date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $profile = \App\Models\EmployeeProfile::where('user_id', $id)->first();
        if ($profile) {
            $profile->update([
                'status' => 'terminated',
            ]);
        }

        $user->update([
            'status' => 'inactive',
        ]);

        return response()->json(['message' => 'Employee terminated successfully.']);
    }
}
