<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class QARemindersController extends Controller
{
    public function index()
    {
        return view('qa.reminders.coming_soon');
    }

    public function send(Request $request)
    {
        // Placeholder for future implementation
        return redirect()->back()->with('info', 'This feature is coming soon.');
    }
}
