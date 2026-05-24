<?php

namespace Database\Seeders;

use App\Models\CrmAccount;
use App\Models\CrmContact;
use Illuminate\Database\Seeder;

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Aaran Hospital',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Hospital in Bosaso. Listed on Africa Listings for healthcare in Bosaso, Bari, Somalia. Contact details not publicly listed — needs direct outreach.'],
            ],
            [
                'name' => 'Abdishakur Pharmacy',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Pharmacy', 'source' => 'Founder outreach', 'notes' => 'Pharmacy in Bosaso. Listed on Fastbase directory for Bosaso pharmacies. Contact info not publicly available — needs direct outreach.'],
            ],
            [
                'name' => 'Al Amal General Hospital',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Listed on Google Maps for Bosaso healthcare facilities. Contact info not publicly available.'],
                'contacts' => [
                    ['first_name' => 'Reception', 'last_name' => '', 'phone' => '+252 907 771 661']
                ]
            ],
            [
                'name' => 'Al Baraka Pharmacy',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Pharmacy', 'source' => 'Founder outreach', 'notes' => 'Pharmacy in Bosaso. Listed on local directories. Contact details not publicly listed.'],
            ],
            [
                'name' => 'Al Bayan Medical Centre',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Clinic', 'source' => 'Founder outreach', 'notes' => 'Medical centre in Bosaso. Contact details not publicly listed.'],
            ],
            [
                'name' => 'Al Hayat Hospital',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Major hospital in Bosaso.'],
                'contacts' => [
                    ['first_name' => 'Reception', 'last_name' => '', 'phone' => '+252 907 726 655']
                ]
            ],
            [
                'name' => 'Al Xikma Clinic',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Clinic', 'source' => 'Founder outreach', 'notes' => 'Clinic in Bosaso. Contact details not publicly listed.'],
            ],
            [
                'name' => 'Banadir Hospital Bosaso',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Hospital in Bosaso. Contact details not publicly listed.'],
            ],
            [
                'name' => 'Barwaaqo Medical Centre',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Clinic', 'source' => 'Founder outreach', 'notes' => 'Medical centre in Bosaso. Contact details not publicly listed.'],
            ],
            [
                'name' => 'Bosaso General Hospital (Regional)',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Government/regional hospital. Largest public hospital in Bosaso. High volume, multiple departments.'],
            ],
            [
                'name' => 'Busteele Pharmacy',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Pharmacy', 'source' => 'Founder outreach', 'notes' => 'Pharmacy listed on Fastbase directory.'],
            ],
            [
                'name' => 'Dalsan Medical Centre',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Clinic', 'source' => 'Founder outreach', 'notes' => 'Medical centre in Bosaso. Contact details not publicly listed.'],
            ],
            [
                'name' => 'Dr. Abdullahi Clinic',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Clinic', 'source' => 'Founder outreach', 'notes' => 'Private clinic in Bosaso. Appears on Google Maps.'],
            ],
            [
                'name' => 'East Africa University Health Sciences Faculty',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'website' => 'eau.edu.so',
                'meta' => ['type' => 'University/Academic', 'source' => 'Founder outreach', 'notes' => 'University with health sciences programs. Potential partnership for training and research. Website: eau.edu.so.'],
            ],
            [
                'name' => 'Furqaan Hospital',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Hospital in Bosaso. Contact details not publicly listed.'],
            ],
            [
                'name' => 'Galkayo Medical Centre',
                'industry' => 'Healthcare',
                'city' => 'Galkayo',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Clinic', 'source' => 'Founder outreach', 'notes' => 'Nearby city medical facility. Regional expansion target.'],
            ],
            [
                'name' => 'Garowe General Hospital',
                'industry' => 'Healthcare',
                'city' => 'Garowe',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Major hospital in Puntland capital Garowe. High priority for regional expansion.'],
            ],
            [
                'name' => 'Hamar Hospital',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Hospital in Bosaso area.'],
            ],
            [
                'name' => 'Horseed Hospital',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Located in Bosaso city.'],
                'contacts' => [
                    ['first_name' => 'Reception', 'last_name' => '', 'phone' => '+252 907 792 959']
                ]
            ],
            [
                'name' => 'Iftin Pharmacy',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Pharmacy', 'source' => 'Founder outreach', 'notes' => 'Pharmacy in Bosaso. Listed on Fastbase.'],
            ],
            [
                'name' => 'Jabir Pharmacy',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Pharmacy', 'source' => 'Founder outreach', 'notes' => 'Pharmacy in Bosaso.'],
            ],
            [
                'name' => 'Jigjiga Hospital',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Hospital in Bosaso area.'],
            ],
            [
                'name' => 'Kalkal Health Centre',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Clinic', 'source' => 'Founder outreach', 'notes' => 'Health centre in Bosaso.'],
            ],
            [
                'name' => 'Kulmiye Pharmacy',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Pharmacy', 'source' => 'Founder outreach', 'notes' => 'Pharmacy listed on Fastbase for Bosaso.'],
            ],
            [
                'name' => 'Life Line Hospital',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Hospital in Bosaso. Listed on Google Maps.'],
                'contacts' => [
                    ['first_name' => 'Admin', 'last_name' => '', 'phone' => '+252 907 705 700']
                ]
            ],
            [
                'name' => 'Madiina Hospital',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Private hospital in Bosaso.'],
            ],
            [
                'name' => 'Midnimo Medical Centre',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Clinic', 'source' => 'Founder outreach', 'notes' => 'Medical centre in Bosaso.'],
            ],
            [
                'name' => 'Mogadishu Turkish Recep Tayyip Erdogan Training & Research Hospital',
                'industry' => 'Healthcare',
                'city' => 'Mogadishu',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Largest hospital in East Africa. 200+ bed capacity. Turkish partnership. Strategic long-term target.'],
            ],
            [
                'name' => 'Mudug Regional Hospital',
                'industry' => 'Healthcare',
                'city' => 'Galkayo',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Regional hospital for Mudug region. Located in Galkayo.'],
            ],
            [
                'name' => 'Naashad Polyclinic',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'website' => 'nashadpolyclinic.com',
                'meta' => ['type' => 'Polyclinic', 'source' => 'Founder outreach', 'notes' => 'Advanced Healthcare, Trusted by Families in Bosaso. Website: nashadpolyclinic.com.'],
                'contacts' => [
                    ['first_name' => 'Reception', 'last_name' => '', 'phone' => '+252 907 794 794']
                ]
            ],
            [
                'name' => 'National Hospital Bosaso',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Private hospital, 24/7 service. Address: Afar Iridood Street, Main Road, Bossaso, Puntland. Email: nationalhospital1012@gmail.com. Facebook: facebook.com/NATIONALHOSPITALBOS (~30K followers).'],
                'contacts' => [
                    ['first_name' => 'Reception', 'last_name' => '', 'phone' => '+252 905 666 639', 'email' => 'nationalhospital1012@gmail.com']
                ]
            ],
            [
                'name' => 'OOG Medical Centre',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Clinic', 'source' => 'Founder outreach', 'notes' => 'Appears on Google Maps for Bosaso healthcare facilities. Related to OOG Maternal Care Health (received PPE/medical equipment donations via IOM Somalia per 2020 IOM programmatic overview).'],
            ],
            [
                'name' => 'Puntland Medical Center',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Private medical center in Bosaso. Listed on Facebook: facebook.com/Puntland-Medical-Center. Contact info not publicly published — needs direct outreach.'],
            ],
            [
                'name' => 'Takar Pharmacy',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Pharmacy', 'source' => 'Founder outreach', 'notes' => 'Listed on Fastbase directory. Contact info not publicly listed.'],
            ],
            [
                'name' => 'Tukaara Medical Centre',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Clinic', 'source' => 'Founder outreach', 'notes' => 'General medical centre. Listed on Africa Listings (Bosaso, Bari).'],
                'contacts' => [
                    ['first_name' => 'Reception', 'last_name' => '', 'phone' => '+252 907 711 849']
                ]
            ],
            [
                'name' => 'Vita Care Clinic',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Clinic', 'source' => 'Founder outreach', 'notes' => 'Listed on Africa Listings for Bosaso, Bari, Somalia. Contact details not publicly listed — needs direct outreach.'],
            ],
            [
                'name' => 'Daryeel Hospital',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Hospital', 'source' => 'Founder outreach', 'notes' => 'Hospital in Bosaso.'],
            ],
            [
                'name' => 'Bulsho Medical Centre',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Clinic', 'source' => 'Founder outreach', 'notes' => 'Medical centre in Bosaso.'],
            ],
            [
                'name' => 'Faarax Pharmacy',
                'industry' => 'Healthcare',
                'city' => 'Bosaso',
                'country' => 'Somalia',
                'status' => 'qualified',
                'meta' => ['type' => 'Pharmacy', 'source' => 'Founder outreach', 'notes' => 'Pharmacy in Bosaso.'],
            ],
        ];

        $this->command->info('Seeding ' . count($accounts) . ' CRM accounts...');

        foreach ($accounts as $data) {
            $contacts = $data['contacts'] ?? [];
            unset($data['contacts']);

            $account = CrmAccount::updateOrCreate(
                ['name' => $data['name'], 'city' => $data['city']],
                $data
            );

            foreach ($contacts as $contactData) {
                CrmContact::updateOrCreate(
                    ['crm_account_id' => $account->id, 'phone' => $contactData['phone'] ?? null],
                    array_merge($contactData, ['crm_account_id' => $account->id])
                );
            }
        }

        $this->command->info('✓ CRM seeding complete. ' . count($accounts) . ' accounts created/updated.');
    }
}
