# Security Notes

## HMAC & Payload Validation
- All SSO payloads must include `signature`, `sig_alg`, `request_id`, and `nonce`.
- `JsonPayloadVerifier` canonicalizes payloads, recomputes HMAC using `config('afm_sso.shared_secret')`, and compares using `hash_equals`.
- Issuer (`iss`), audience (`aud`), and version (`v`) are locked to config values; mismatches lead to immediate rejection.
- Tokens are time-bound: `issued_at` cannot be more than 5 minutes in the future and `expires_at` must be >= current time.

## Replay Protection & TTL
- `TokenService::isNonceUsed()` enforces unique `(request_id, nonce)` pairs. Attempts to reuse a payload raise `Token already used` and log `sso_rejected`.
- `afm_session_tokens` store `issued_at`, `expires_at`, and `consumed_at`. Middleware/handshake code refuses expired tokens and clears them from the session.
- Redis cache entries (`afm:session:{id}`) use `config('afm_sso.token_ttl')` to expire automatically, reducing window for hijacking.

## Role Enforcement
- Student routes are wrapped in `EnsureAfmStudentRole` which checks `session('afm_role') === 'student'`.
- QA routes use `EnsureAfmQaRole`; admin uses `role:admin` middleware. Unauthorized access triggers a redirect (local) or HTTP 403.
- Views also rely on `session('afm_role')` when rendering navigation so that cross-role links never appear.

## Input Validation & Ownership Checks
- `StudentSubmissionController` ensures the `responses.sis_student_id` matches the session user before saving drafts or submitting.
- `ResponseSubmissionService::validateSubmission()` enforces required questions and likert bounds before marking completion.
- Forms cannot be published unless they contain at least one section and one question (prevents QA from creating empty forms).

## Audit Logging
- `AuditLogger` captures key events: successful/failed SSO, export generation, manual completion overrides, and (future) reminder launches.
- `audit_logs` records actor ID, role, event type, action, metadata, IP, and user agent for downstream compliance reviews.

## Data Minimization
- Student identifiers are hashed (`responses.student_hash`) so aggregated reporting can use hashes instead of raw IDs when necessary.
- SIS shadow tables (`sis_students`, `sis_courses`, `sis_enrollments`) only store fields needed for reports; no grades or sensitive data are replicated.

## Transport & Deployment Considerations
- Always set `APP_ENV=production` and `APP_DEBUG=false` outside local machines to disable `/dev/simulator`.
- Use HTTPS end-to-end so AFM session cookies have `Secure` and `HttpOnly` flags.
- Rotate `AFM_SSO_SHARED_SECRET` regularly and coordinate with SIS to avoid downtime.

