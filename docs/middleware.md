# Middleware Control Flow

Each middleware executes within Laravel's `web` kernel order. Aliases are configured inside `bootstrap/app.php`.

## AfmAuthenticated (`app/Http/Middleware/AfmAuthenticated.php`)
- **Alias:** `afm.auth`
- **Placement:** Applied to `/admin` route group before `role:admin`.
- **Reads:** Session keys `afm_token_id`, `afm_role`.
- **Logic:**
  1. Check `session()->has('afm_token_id')` *and* `session()->has('afm_role')`.
  2. If either missing, return `redirect('/')` with flash error "Please authenticate via SSO to access AFM.". The redirect occurs before controller logic executes.
  3. If both exist, call `$next($request)`.
- **Reason:** Ensures admin routes only run when a valid AFM session exists even if Laravel auth is not used.

## AfmAuthentication (`app/Http/Middleware/AfmAuthentication.php`)
- **Placement:** Registered but not globally enabled; used when handshake routes want either Laravel auth or AFM token context.
- **Reads:** `Auth::user()`, session `afm_token_id`, `afm_role`.
- **Logic:**
  1. If a Laravel user is authenticated, share `afmUser` with views (name/id/role) and set request attributes `afm_role`. Continue pipeline.
  2. Otherwise read `$tokenId = Session::get('afm_token_id')`. If empty:
     - If `config('afm_sso.integration_mode') === 'simulated'`, redirect to `/sim-sis`.
     - Else redirect to `/login` with optional error.
  3. Use `TokenService::getToken($tokenId)`; if null or expired (`isExpired()` returns true), forget session token and redirect to simulator or login depending on mode.
  4. On valid token, set request attributes `afm_token`, `afm_role`, `afm_user_id` and build `$userName` (student vs QA). Share `afmUser` view data.
  5. Continue pipeline.
- **Reason:** Provides handshake compatibility where HTTP session must be rebuilt from `afm_session_tokens`.

## EnsureAfmStudentRole (`app/Http/Middleware/EnsureAfmStudentRole.php`)
- **Alias:** `afm.student`
- **Placement:** Every `/student/*` route uses `web` → `afm.student`.
- **Reads:** `Session::get('afm_role')`.
- **Logic:**
  1. If session role equals `'student'`, continue.
  2. Otherwise redirect to `/dev/simulator` with flash error `Student access requires a valid AFM JSON login.`
- **Reason:** Prevents QA/admin sessions from invoking student controllers and ensures course eligibility logic trusts session data only when role is student.

## EnsureAfmQaRole (`app/Http/Middleware/EnsureAfmQaRole.php`)
- **Placement:** Entire `/qa` route group uses this middleware.
- **Reads:** `Session::get('afm_role')`.
- **Logic:**
  1. Allow request when role equals `'qa'` or `'qa_officer'` (explicit check to support both naming conventions).
  2. Otherwise redirect to `route('dev.simulator')` with flash error `QA access requires a valid AFM JSON login.`
- **Reason:** QA views expose student data; middleware prevents unauthorized access instead of repeating checks in each controller.

## RoleCheck (`app/Http/Middleware/RoleCheck.php`)
- **Alias:** `role`
- **Placement:** Used after `afm.auth` on admin routes (e.g., `role:admin`).
- **Reads:** `session('afm_role')`.
- **Logic:** Compares stored role to required role parameter; if mismatch, aborts with HTTP 403 and message `Unauthorized access.`. Continues otherwise.
- **Reason:** Lightweight gate for custom route groups where alias takes role argument.

## RoleMiddleware (`app/Http/Middleware/RoleMiddleware.php`)
- **Placement:** Available for routes that rely on request attributes set by `AfmAuthentication`.
- **Reads:** `$request->attributes->get('afm_role')`.
- **Logic:**
  1. If attribute missing, check environment: in local dev redirect to simulator with error; otherwise abort 401.
  2. If attribute exists but not within allowed `$roles` arguments, redirect in local dev with unauthorized message or abort 403 in production.
  3. Otherwise proceed.
- **Reason:** When controllers rely on token attributes instead of session state, this middleware enforces role membership uniformly.

