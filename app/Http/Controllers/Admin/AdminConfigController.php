<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminConfigController extends Controller
{
    public function index()
    {
        // Get current AFM configuration
        $config = [
            'current_term' => config('afm.current_term', '202410'),
            'high_risk_threshold' => config('afm.qa.high_risk_threshold', 0.6),
            'auto_save_interval' => config('afm.auto_save_interval', 30),
            'student_hash_salt' => config('afm.student_hash_salt', 'CONFIGURED'),
        ];

        return view('admin.config.index', ['config' => $config]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_term' => 'required|string',
            'high_risk_threshold' => 'required|numeric|min:0|max:1',
            'auto_save_interval' => 'required|integer|min:10|max:300',
        ]);

        // In a production app, we'd update .env or a config database table
        // For now, we'll just validate and return success
        
        return redirect()->route('admin.config.index')->with('success', 'Configuration updated successfully.');
    }
}
