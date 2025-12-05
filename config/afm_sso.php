<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AFM SSO Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the JSON-SSO integration between SIS and AFM.
    | These values must match the SIS configuration for proper authentication.
    |
    */

    'shared_secret' => env('AFM_SSO_SHARED_SECRET'),
    
    'iss' => env('AFM_SSO_ISS', 'LIMU-SIS'),
    
    'aud' => env('AFM_SSO_AUD', 'AFM'),
    
    'version' => env('AFM_SSO_VERSION', '1'),
    
    'signature_algorithm' => 'sha256',
    
    'token_ttl' => env('AFM_SSO_TOKEN_TTL', 120), // seconds
    
    'integration_mode' => env('INTEGRATION_MODE', 'simulated'), // simulated|production
    
    'allowed_roles' => ['student', 'qa', 'qa_officer', 'department_head', 'admin'],
];
