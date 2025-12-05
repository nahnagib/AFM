<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Services\JsonPayloadVerifier;
use App\Services\TokenService;
use App\Services\AuditLogger;

class SsoTokenIntakeService
{
    protected $verifier;
    protected $tokenService;
    protected $auditLogger;

    public function __construct(
        JsonPayloadVerifier $verifier,
        TokenService $tokenService,
        AuditLogger $auditLogger
    ) {
        $this->verifier = $verifier;
        $this->tokenService = $tokenService;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Handle the SSO token intake process.
     *
     * @param array $payload
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function handle(array $payload, Request $request): array
    {
        // 1. Verify Payload
        $validation = $this->verifier->verify($payload);

        if (!$validation['valid']) {
            $this->auditLogger->logSsoRejected(
                $payload['request_id'] ?? 'unknown',
                $validation['error']
            );
            
            throw new \Exception($validation['error'], 401);
        }

        // 2. Check Replay
        if ($this->tokenService->isNonceUsed($payload['request_id'], $payload['nonce'])) {
            $this->auditLogger->logSsoRejected($payload['request_id'], 'Replayed nonce');
            throw new \Exception('Token already used', 401);
        }

        // 3. Create Token
        $token = $this->tokenService->createToken(
            $payload,
            $request->ip(),
            $request->userAgent()
        );

        $this->auditLogger->logSsoValidated(
            $payload['request_id'],
            $token->payload_hash,
            $payload['student_id'] ?? $payload['user_id'] ?? 'unknown'
        );

        return [
            'status' => 'success',
            'redirect_to' => url("/sso/handshake/{$token->id}"),
            'token_id' => $token->id,
        ];
    }
}
