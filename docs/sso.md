# SSO & Token Lifecycle

AFM trusts the campus SIS through a JSON payload that is signed with a shared secret. The handshake is implemented in `JsonPayloadVerifier`, `SsoJsonIntakeService`, `SsoTokenIntakeService`, and `TokenService`.

## Payload Contract
Common fields (validated for every role):
- `iss` (Issuer) → must equal `config('afm_sso.iss')`, default `LIMU-SIS`.
- `aud` (Audience) → must equal `config('afm_sso.aud')`, default `AFM`.
- `v` (Version) → must equal `config('afm_sso.version')`, default `1`.
- `request_id` (UUID) → used for replay detection + audit logs.
- `role` → must be in `config('afm_sso.allowed_roles')` (student, qa, qa_officer, department_head, admin).
- `issued_at` / `expires_at` → ISO8601 timestamps or UNIX epoch.
- `nonce` → unique random string tied to `request_id`.
- `sig_alg` → `sha256`, `HMAC-SHA256`, or `HS256`.
- `signature` → hex HMAC digest of the canonical JSON payload.

Role-specific fields:
- **Student:** `student_id`, `student_Name`, `term`, and `courses` (array of `{course_reg_no, course_code, course_name}`).
- **QA Officer/Admin:** `user_id`, `user_name`.

## Canonicalization & Signature
`JsonPayloadVerifier::verify()` canonicalizes the payload via `App\Support\AfmJsonCanonicalizer`, removes whitespace, sorts keys, and hashes with the shared secret. `hash_equals()` avoids timing attacks. If validation fails, `SsoTokenIntakeService` and `SsoJsonIntakeService` raise an exception and `AuditLogger::logSsoRejected()` is triggered.

## Token Creation
1. `/sso/json-intake` (or `/dev/simulator/login` locally) hands the parsed payload to `SsoJsonIntakeService::handle()`.
2. The service enforces timestamps (issued in the past, not yet expired by more than 5 minutes) and signature presence.
3. For students it maps term labels to internal codes and copies `courses` into the HTTP session. For QA/admin it only stores ID/name/role.
4. `TokenService::createToken()` persists the payload into `afm_session_tokens` with fields: request/nonce hash, SIS ID, optional student name, course list, role, issue/expire timestamps, client IP, and user agent.
5. The token is also cached in Redis as `afm:session:{id}` with TTL `config('afm_sso.token_ttl')`. Redis failure is logged but not fatal.

## Session Hydration & Middleware
- `SsoJsonIntakeService` clears any previous AFM session keys, writes `afm_role`, `afm_user_id`, `afm_user_name`, `afm_term_label`, `afm_term_code`, and `afm_courses`, then redirects to `/student/dashboard`, `/qa`, or `/admin` depending on role.
- In flows that use the token handshake (`SsoTokenIntakeService` + `/sso/handshake/{token}` in tests), middleware `AfmAuthentication` loads the token by ID, verifies it is not expired/consumed, stores the same session attributes, and shares a simplified `afmUser` object with Blade.
- `AfmAuthentication` automatically redirects to `/sim-sis` (simulated mode) or `/login` (prod) if the token is missing/expired.

## Replay & TTL
- `TokenService::isNonceUsed()` prevents replay by checking `afm_session_tokens` for an existing (`request_id`, `nonce`).
- `afm_session_tokens.expires_at` mirrors the SIS payload expiry. `AfmAuthentication` checks `token->isExpired()` before accepting a request.
- `TokenService::consumeToken()` sets `consumed_at` and removes the Redis key after a successful handshake so the token cannot be reused.

## Audit Logging
`AuditLogger` records:
- `sso_validated` when a payload passes verification (stores payload hash + student/QA ID).
- `sso_rejected` when verification fails (reason stored in `metadata`).

Always rotate `AFM_SSO_SHARED_SECRET` when shipping to another environment and coordinate with the SIS team so both sides sign the same canonical payload.
