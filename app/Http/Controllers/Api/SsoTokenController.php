<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SsoTokenIntakeService;
use Illuminate\Http\Request;

class SsoTokenController extends Controller
{
    protected $intakeService;

    public function __construct(SsoTokenIntakeService $intakeService)
    {
        $this->intakeService = $intakeService;
    }

    public function store(Request $request)
    {
        try {
            $result = $this->intakeService->handle($request->all(), $request);
            
            return response()->json([
                'status' => $result['status'],
                'redirect_to' => $result['redirect_to'],
            ]);

        } catch (\Illuminate\Database\ConnectionException $e) {
            // This catches both DB and Redis connection errors if they bubble up
            \Illuminate\Support\Facades\Log::channel('ssotoken')->error('SSO Intake Connection Failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'cache_unavailable',
                'message' => 'SSO session cache offline'
            ], 503);
        } catch (\Exception $e) {
            $status = $e->getCode() ?: 500;
            // Map 0 code to 500 if not set
            if ($status < 100 || $status > 599) $status = 500;
            
            return response()->json([
                'error' => 'SSO Failed',
                'message' => $e->getMessage()
            ], $status);
        }
    }
}
