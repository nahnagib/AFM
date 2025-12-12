# Repositories

Repositories under `app/Repositories` wrap Eloquent queries so services can focus on business logic.

## AuditLogRepository
- **Methods:**
  - `create(array $data)` → Wrapper for `AuditLog::create()`.
  - `getLatest(int $limit = 50)` → Returns most recent audit events ordered by `created_at desc`.
- **Usage:** Older jobs can inject this repository instead of touching `AuditLog` directly (not heavily used today).

## CompletionFlagRepository
- **Methods:**
  - `getFlag($formId, $courseRegNo, $termCode, $sisStudentId)` → Single `CompletionFlag` lookup.
  - `updateOrInsert(array $attributes, array $values)` → `updateOrCreate` convenience used when stamping completion.
  - `getStudentCompletionStatus($sisStudentId, $termCode)` → Returns all completion flags for the student/term.
- **Usage:** `CompletionService` and other services rely on these methods when building progress dashboards.

## FeedbackRepository
- **Methods:**
  - `findResponse($formId, $courseRegNo, $termCode, $sisStudentId)` → Loads response with `items` relationship eager loaded.
  - `createResponse(array $data)` → Creates new `responses` row.
  - `saveResponseItems(Response $response, array $itemsData)` → Deletes prior items and recreates them based on provided data structure.
  - `updateStatus(Response $response, string $status)` → Sets response status and `submitted_at` when status is `submitted`.
- **Usage:** Legacy flows that pre-date `ResponseSubmissionService`; still helpful in some tests/migrations.

## FormRepository
- **Methods:**
  - `findActiveFormsForCourse($courseRegNo, $termCode)` → Returns forms that are active and scoped to a particular course/term.
  - `findActiveSystemForms($termCode)` → Returns active forms flagged as `system_services`.
  - `findById($id)` / `getAllForms()` / `create(array $data)` / `update(Form $form, array $data)` flatten common Eloquent calls.
- **Usage:** Services that need to read forms without duplicating query logic.

## NotificationOutboxRepository
- **Methods:**
  - `create(array $data)` → Inserts a row into `notification_outbox`.
  - `getPendingToSend()` → Query scope `pending()` ensures `status = pending` and `send_after <= now()`.
- **Usage:** `NotificationDispatcher` uses this repository to stage and fetch notifications.

## SessionTokenRepository
- **Methods:**
  - `findByRequestIdAndNonce($requestId, $nonce)` → Used for replay protection.
  - `create(array $data)` → Inserts `afm_session_tokens` row.
  - `findValidToken($id)` → Returns token if it exists and `isValid()` (not expired/consumed).
- **Usage:** Alternative to `TokenService` when direct repository access is preferable (some tests/jobs).

