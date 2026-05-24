<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\KbArticle;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function getSettingsData(Request $request)
    {
        $settings = SystemSetting::all()->pluck('value', 'key');
        $users = User::with('role', 'department')->get();
        $departments = Department::withCount('users')->get();
        $kbArticles = KbArticle::with('author:id,name')->orderBy('updated_at', 'desc')->get();

        return response()->json([
            'config' => $settings,
            'users' => $users,
            'departments' => $departments,
            'kb_articles' => $kbArticles,
        ]);
    }

    public function updateSetting(Request $request, $key)
    {
        $request->validate([
            'value' => 'required|array'
        ]);

        $setting = SystemSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $request->value]
        );

        return response()->json(['message' => 'Settings updated successfully', 'setting' => $setting]);
    }
    public function testSmtp(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|numeric',
            'username' => 'required|string',
            'password' => 'required|string',
            'encryption' => 'nullable|string|in:tls,ssl',
            'from_address' => 'required|email',
            'from_name' => 'required|string',
        ]);

        try {
            // Override mail config dynamically
            config([
                'mail.mailers.smtp.host' => $request->host,
                'mail.mailers.smtp.port' => $request->port,
                'mail.mailers.smtp.encryption' => $request->encryption,
                'mail.mailers.smtp.username' => $request->username,
                'mail.mailers.smtp.password' => $request->password,
                'mail.from.address' => $request->from_address,
                'mail.from.name' => $request->from_name,
            ]);

            $adminUser = User::whereHas('role', function($q) {
                $q->where('is_super_admin', true);
            })->first() ?? User::first();

            \Illuminate\Support\Facades\Mail::raw('This is a test email to verify your SMTP settings in SEHTECH OS.', function ($message) use ($adminUser) {
                $message->to($adminUser->email)
                        ->subject('SMTP Test - SEHTECH OS');
            });

            return response()->json(['message' => 'Test email sent successfully to ' . $adminUser->email]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send test email: ' . $e->getMessage()], 500);
        }
    }
}
