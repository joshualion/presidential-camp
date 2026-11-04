<?php

namespace App\Http\Controllers;

use App\Models\EnterpriseAiSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnterpriseAiSettingsController extends Controller
{
    public function edit(Request $request)
    {
        return response()->view('backend.enterprise.ai-settings', [
            'profileData' => $request->user(), 'pageTitle' => 'AI report settings',
            'settings' => EnterpriseAiSetting::find(1),
            'audits' => DB::table('enterprise_ai_audits')->latest('id')->limit(25)
                ->get(['user_id', 'model', 'status', 'reserved_tokens', 'actual_tokens', 'created_at']),
        ])->header('Cache-Control', 'no-store');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'api_key' => ['nullable', 'string', 'min:20', 'max:512', 'regex:/^\S+$/D'],
            'model' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9._:-]+$/D'],
            'daily_requests' => ['required', 'integer', 'between:1,100'],
            'daily_tokens' => ['required', 'integer', 'between:2000,1000000'],
            'installation_daily_requests' => ['required', 'integer', 'between:1,1000'],
            'installation_daily_tokens' => ['required', 'integer', 'between:2000,10000000'],
            'max_output_tokens' => ['required', 'integer', 'between:100,4000'],
        ]);
        $settings = EnterpriseAiSetting::firstOrNew(['id' => 1]);
        if (empty($data['api_key'])) {
            unset($data['api_key']);
        }
        $settings->fill($data);
        try {
            $hasKey = !empty($settings->api_key);
        } catch (\Throwable) {
            $hasKey = false;
        }
        if ($settings->enabled && !$hasKey) {
            return back()->withErrors(['api_key' => 'Add an OpenAI API key before enabling AI analysis.']);
        }
        DB::transaction(function () use ($settings, $request, $data) {
            $settings->save();
            DB::table('enterprise_ai_audits')->insert([
                'user_id' => $request->user()->id, 'model' => $settings->model,
                'status' => 'settings_updated',
                'input_hash' => hash('sha256', json_encode(array_diff_key($data, ['api_key' => true]), JSON_THROW_ON_ERROR)),
                'reserved_tokens' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return redirect()->route('enterprise.reports.ai.settings')->with('status', 'AI settings saved.');
    }
}
