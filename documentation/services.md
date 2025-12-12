# Services & Internal Modules

## 1. Authentication & Security Services

### 1.1 TokenService
**Responsibility:** Manages the lifecycle of AFM Session Tokens (if using token-based API auth in parallel or internal session tracking).
- **Key Methods:** `createToken()`, `validateToken()`.
- **Storage:** Uses Redis (`afm:session:{id}`) for high-performance lookups with a TTL (Time-To-Live).

### 1.2 JsonPayloadVerifier
**Responsibility:** Ensures the integrity and authenticity of SIS payloads.
- **Algorithm:** HMAC-SHA256.
- **Logic:**
    - Re-computes signature using `AFM_SSO_SHARED_SECRET`.
    - Compares with `payload.signature` using `hash_equals` (timing attack safe).
    - Validates business rules: `iss` (Issuer), `aud` (Audience), `exp` (Expiry).
- **Error Handling:** Throws `InvalidSignatureException` or `TokenExpiredException`.

### 1.3 SsoJsonIntakeService
**Responsibility:** Orchestrates the SSO handshake logic.
- **Logic:**
    - Calls `JsonPayloadVerifier`.
    - Clears previous Session data.
    - Maps SIS Role (`student`, `qa`) to AFM internal roles.
    - Hydrates Session with User Context.
    - Returns the appropriate redirect path.

## 2. Core Business Logic Services

### 2.1 FeedbackService
**Responsibility:** Manages the initialization of feedback sessions.
- **Key Methods:** `startResponse($templateId, $studentId, $courseRegNo, ...)`
- **Logic:**
    - **Context Validation:** Ensures a course registration number is provided for course-specific forms.
    - **Idempotency:** Uses `firstOrCreate` on the `responses` table to prevent duplicate active sessions for the same student/course pair.
    - **Normalization:** Handles 'system' context for non-course forms.

### 2.2 CompletionTrackingService
**Responsibility:** Optimized checking of completion status.
- **Key Methods:** `isFormComplete()`, `getPendingForms()`.
- **Interaction:**
    - Queries the `responses` table directly to check for `status = 'submitted'`.
    - Used by `StudentFormController` to block re-submission.
    - Used by `StudentDashboardController` to modify UI state (Pending vs History).

### 2.3 AuditLogger
**Responsibility:** Records security and compliance events.
- **Storage:** Writes to `audit_logs` table.
- **Events Logged:**
    - `sso_validated`: Successful entry.
    - `sso_rejected`: Failed handshake (with reason).
    - `feedback_submitted`: Student action.
    - `export_generated`: QA officer data access (for privacy auditing).

## 3. Reporting Services

### 3.1 QaReportingService
**Responsibility:** Aggregates raw response data into actionable metrics.
- **Logic:**
    - **Completion Rate:** `(Submitted Count / Enrolled Count) * 100`.
    - **Likert Analysis:** Calculates Average and Standard Deviation for 1-5 scale questions.
    - **Data Isolation:** Ensures reports only include data for the requested `term_code` and `form_id`.
