<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractTemplate;
use App\Models\ComplianceFramework;
use App\Models\ComplianceControl;
use Illuminate\Support\Str;

class LegalSeeder extends Seeder
{
    public function run(): void
    {
        ContractTemplate::insert([
            [
                'id' => Str::uuid(), 
                'name' => 'Standard Mutual NDA', 
                'type' => 'NDA', 
                'ai_prompt' => 'Generate a standard Mutual Non-Disclosure Agreement (NDA) between two parties. Protect confidential information, trade secrets, and proprietary data. Ensure the governing law is appropriate. Specific requirements: {requirements}', 
                'variables' => json_encode(['party_a_name', 'party_b_name', 'governing_law', 'requirements']), 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'id' => Str::uuid(), 
                'name' => 'Master Services Agreement (MSA)', 
                'type' => 'MSA', 
                'ai_prompt' => 'Generate a comprehensive Master Services Agreement. Include standard clauses for payment terms, intellectual property rights, termination, and liability limitations. Specific requirements: {requirements}', 
                'variables' => json_encode(['provider_name', 'client_name', 'jurisdiction', 'requirements']), 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'id' => Str::uuid(), 
                'name' => 'Data Processing Agreement (DPA)', 
                'type' => 'DPA', 
                'ai_prompt' => 'Draft a standard Data Processing Agreement compliant with GDPR and CCPA. Detail data controller and processor obligations, breach notification timelines, and sub-processor requirements. Specific requirements: {requirements}', 
                'variables' => json_encode(['controller_name', 'processor_name', 'requirements']), 
                'created_at' => now(), 
                'updated_at' => now()
            ]
        ]);

        $gdpr = ComplianceFramework::create([
            'name' => 'GDPR',
            'description' => 'General Data Protection Regulation'
        ]);

        $soc2 = ComplianceFramework::create([
            'name' => 'SOC 2 Type II',
            'description' => 'Service Organization Control 2'
        ]);

        ComplianceControl::insert([
            ['id' => Str::uuid(), 'compliance_framework_id' => $gdpr->id, 'name' => 'Data Subject Access Requests (DSAR)', 'description' => 'Ability to export or delete user data within 30 days', 'status' => 'gap', 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid(), 'compliance_framework_id' => $gdpr->id, 'name' => 'Cookie Consent', 'description' => 'User explicit consent for non-essential cookies', 'status' => 'gap', 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid(), 'compliance_framework_id' => $soc2->id, 'name' => 'Access Control (Logical)', 'description' => 'RBAC and MFA enforced across all internal tools', 'status' => 'gap', 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid(), 'compliance_framework_id' => $soc2->id, 'name' => 'Vulnerability Management', 'description' => 'Annual penetration tests and weekly vulnerability scans', 'status' => 'gap', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
