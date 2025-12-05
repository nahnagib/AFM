<?php

namespace App\Repositories;

use App\Models\AfmSessionToken;

class SessionTokenRepository
{
    public function findByRequestIdAndNonce(string $requestId, string $nonce): ?AfmSessionToken
    {
        return AfmSessionToken::where('request_id', $requestId)
            ->where('nonce', $nonce)
            ->first();
    }

    public function create(array $data): AfmSessionToken
    {
        return AfmSessionToken::create($data);
    }

    public function findValidToken(int $id): ?AfmSessionToken
    {
        $token = AfmSessionToken::find($id);
        if ($token && $token->isValid()) {
            return $token;
        }
        return null;
    }
}
