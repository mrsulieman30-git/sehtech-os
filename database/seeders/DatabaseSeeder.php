<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Departments
        $departments = [
            ['name' => 'Administration', 'slug' => 'admin', 'primary_color' => '#1B2A4A', 'secondary_color' => '#F1F5F9', 'icon' => 'PhBuildings'],
            ['name' => 'Finance & Accounting', 'slug' => 'finance', 'primary_color' => '#10B981', 'secondary_color' => '#D1FAE5', 'icon' => 'PhCurrencyDollar'],
            ['name' => 'People & HR', 'slug' => 'hr', 'primary_color' => '#06B6D4', 'secondary_color' => '#CFFAFE', 'icon' => 'PhUsers'],
            ['name' => 'Sales & CRM', 'slug' => 'sales', 'primary_color' => '#F59E0B', 'secondary_color' => '#FEF3C7', 'icon' => 'PhStorefront'],
            ['name' => 'Development', 'slug' => 'development', 'primary_color' => '#6366F1', 'secondary_color' => '#E0E7FF', 'icon' => 'PhCode'],
            ['name' => 'Marketing', 'slug' => 'marketing', 'primary_color' => '#EC4899', 'secondary_color' => '#FCE7F3', 'icon' => 'PhMegaphone'],
            ['name' => 'Legal & Compliance', 'slug' => 'legal', 'primary_color' => '#8B5CF6', 'secondary_color' => '#EDE9FE', 'icon' => 'PhScales'],
            ['name' => 'Customer Support', 'slug' => 'support', 'primary_color' => '#EF4444', 'secondary_color' => '#FEE2E2', 'icon' => 'PhHeadset'],
            ['name' => 'Operations', 'slug' => 'operations', 'primary_color' => '#14B8A6', 'secondary_color' => '#CCFBF1', 'icon' => 'PhGearSix'],
            ['name' => 'Research & Innovation', 'slug' => 'research', 'primary_color' => '#3B82F6', 'secondary_color' => '#DBEAFE', 'icon' => 'PhFlask'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['slug' => $dept['slug']], $dept);
        }

        // 2. Seed Roles
        $roles = [
            ['name' => 'Master Administrator', 'permissions' => ['*'], 'is_super_admin' => true],
            ['name' => 'Department Head', 'permissions' => ['dept.manage', 'dept.users', 'dept.reports', 'leave.approve', 'performance.write', 'finance.view'], 'is_super_admin' => false],
            ['name' => 'HR Manager', 'permissions' => ['hr.*', 'leave.approve', 'performance.write', 'payroll.view', 'employees.manage'], 'is_super_admin' => false],
            ['name' => 'Finance Manager', 'permissions' => ['finance.*', 'payroll.run', 'bills.manage', 'reports.export'], 'is_super_admin' => false],
            ['name' => 'Project Manager', 'permissions' => ['dev.manage', 'tasks.assign', 'projects.create', 'reports.view'], 'is_super_admin' => false],
            ['name' => 'Sales Executive', 'permissions' => ['sales.*', 'crm.manage', 'deals.manage', 'contacts.manage'], 'is_super_admin' => false],
            ['name' => 'Legal Counsel', 'permissions' => ['legal.*', 'contracts.manage', 'compliance.manage'], 'is_super_admin' => false],
            ['name' => 'Support Agent', 'permissions' => ['support.*', 'tickets.manage', 'kb.manage'], 'is_super_admin' => false],
            ['name' => 'Standard Employee', 'permissions' => ['profile.edit', 'leave.request', 'files.access', 'calendar.view', 'notes.access'], 'is_super_admin' => false],
            ['name' => 'Intern / Trainee', 'permissions' => ['profile.view', 'files.read', 'calendar.view'], 'is_super_admin' => false],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }

        // 3. Create Admin User (if doesn't exist)
        $adminRole = Role::where('name', 'Master Administrator')->first();
        $adminDept = Department::where('slug', 'admin')->first();

        if ($adminRole && $adminDept) {
             User::firstOrCreate(
                ['email' => 'admin@sehtech.com'],
                [
                    'name' => 'SEHTECH Admin',
                    'password' => Hash::make('password'),
                    'role_id' => $adminRole->id,
                    'department_id' => $adminDept->id,
                    'status' => 'active',
                ]
            );
        }
    }
}
