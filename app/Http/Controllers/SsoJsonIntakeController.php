<?php

namespace App\Http\Controllers;

use App\Services\SsoJsonIntakeService;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class SsoJsonIntakeController extends Controller
{
    protected $intakeService;

    public function __construct(SsoJsonIntakeService $intakeService)
    {
        $this->intakeService = $intakeService;
    }

    public function store(Request $request)
    {
        try {
            // Read JSON body
            $payload = $request->json()->all();
            
            if (empty($payload)) {
                // Try getting from input if not json header
                $payload = $request->all();
            }

            // Handle
            $redirectPath = $this->intakeService->handle($payload);

            return redirect($redirectPath);

        } catch (Exception $e) {
            Log::error("SSO JSON Intake Failed: " . $e->getMessage());
            
            // For now, redirect to a generic error page or home with error
            // Should properly be a dedicated SSO error page, but using home for now with flash
            return redirect('/')->with('error', 'SSO Login Failed: ' . $e->getMessage());
        }
    }
}
