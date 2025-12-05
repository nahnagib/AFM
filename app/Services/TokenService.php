<?php

namespace App\Services;

use App\Models\AfmSessionToken;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class TokenService
{
    private int $ttl;

    public function __construct()
    {
        $this->ttl = config('afm_sso.token_ttl', 120);
    }

    /**
     * Create a new session token from validated payload
     *
     * @param array $payload
     * @param string $clientIp
     * @param string $userAgent
     * @return AfmSessionToken
     */
    public function createToken(array $payload, string $clientIp, string $userAgent): AfmSessionToken
    {
        // Extract user ID - different field names for student vs QA
        $userId = $payload['student_id'] ?? $payload['user_id'] ?? null;
        
        // Extract courses - only for students
        $courses = $payload['courses'] ?? [];
        
        // Extract role
        $role = $payload['role'];
        
        $token = AfmSessionToken::create([
            'request_id' => $payload['request_id'],
            'nonce' => $payload['nonce'],
            'payload_hash' => app(JsonPayloadVerifier::class)->computePayloadHash($payload),
            'sis_student_id' => $userId,
            'courses_json' => $courses,
            'role' => $role,
            'issued_at' => $payload['issued_at'],
            'expires_at' => $payload['expires_at'],
            'client_ip' => $clientIp,
            'user_agent' => $userAgent,
        ]);

        // Cache in Redis for fast lookup
        $this->cacheToken($token);

        return $token;
    }

    /**
     * Cache token in Redis
     *
     * @param AfmSessionToken $token
     * @return void
     */
    private function cacheToken(AfmSessionToken $token): void
    {
        try {
            $key = "afm:session:{$token->id}";
            $data = json_encode([
                'id' => $token->id,
                'sis_student_id' => $token->sis_student_id,
                'courses_json' => $token->courses_json,
                'role' => $token->role,
                'expires_at' => $token->expires_at->toIso8601String(),
            ]);

            Redis::setex($key, $this->ttl, $data);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('ssotoken')->error('Redis connection failed during token caching', [
                'error' => $e->getMessage(),
                'token_id' => $token->id
            ]);
        }
    }

    /**
     * Retrieve token from cache or database
     *
     * @param int $tokenId
     * @return AfmSessionToken|null
     */
    public function getToken(int $tokenId): ?AfmSessionToken
    {
        try {
            $key = "afm:session:{$tokenId}";
            $cached = Redis::get($key);

            if ($cached) {
                return AfmSessionToken::find($tokenId);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('ssotoken')->warning('Redis connection failed during token retrieval', [
                'error' => $e->getMessage(),
                'token_id' => $tokenId
            ]);
        }

        return AfmSessionToken::find($tokenId);
    }

    /**
     * Check if nonce has been used (replay protection)
     *
     * @param string $requestId
     * @param string $nonce
     * @return bool
     */
    public function isNonceUsed(string $requestId, string $nonce): bool
    {
        return AfmSessionToken::where('request_id', $requestId)
            ->where('nonce', $nonce)
            ->exists();
    }

    /**
     * Consume a token (mark as used)
     *
     * @param AfmSessionToken $token
     * @return void
     */
    public function consumeToken(AfmSessionToken $token): void
    {
        $token->markAsConsumed();

        // Remove from cache
        try {
            Redis::del("afm:session:{$token->id}");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('ssotoken')->warning('Redis connection failed during token deletion', [
                'error' => $e->getMessage(),
                'token_id' => $token->id
            ]);
        }
    }
}
