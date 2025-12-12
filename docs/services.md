# Services Technical Specification

This file documents every class in `app/Services`. Controllers delegate to these classes to keep request handlers deterministic and to allow unit testing without HTTP context.

## AuditLogger (`app/Services/AuditLogger.php`)
- **Reason for service:** Multiple workflows log events (SSO, submissions, exports). Centralizing logging guarantees consistent schema usage.

### `log(string $eventType, string $actorType, ?string $actorId = null, ?string $requestId = null, ?string $payloadHash = null, array $meta = []): AuditLog`
- **Inputs:** Caller provides event type, actor metadata, optional request or payload identifiers, optional meta payload.
- **Execution:** Builds array with `actor_type`, `actor_id`, `event_type`, `request_id`, `payload_hash`, request IP (`Request::ip()`), user agent (`Request::userAgent()`), and `meta_json`. Inserts row via `AuditLog::create()` and returns the model.
- **Side effects:** Writes to `audit_logs` table.
- **Failures:** DB insert failure bubbles back to caller.

### `logSsoValidated(string $requestId, string $payloadHash, string $studentId): AuditLog`
- Calls `log()` with `event_type = 'sso_validated'`, `actor_type = 'system'`, metadata containing the SIS student ID.

### `logSsoRejected(string $requestId, string $reason): AuditLog`
- Calls `log()` with `event_type = 'sso_rejected'` and reason metadata.

### `logFeedbackSubmitted(string $studentId, int $formId, string $courseRegNo): AuditLog`
- Calls `log()` describing a student submission (actor type `student`).

### `logExportGenerated(string $actorId, string $exportType, array $filters): AuditLog`
- Records QA exports; metadata stores export type and filter set.

### `logAlertsRun(int $nonCompletersCount): AuditLog`
- Records number of students targeted when reminders run (actor type `system`).

## BaseService (`app/Services/BaseService.php`)
- **Reason:** Provides reusable audit helper so every service can log with consistent actor detection.

### `logAudit($type, $action, $metadata = [], $targetType = null, $targetId = null): AuditLog`
- **Inputs:** Event attributes plus optional target info.
- **Execution:**
  1. Determine `$actorId`/`$actorRole` by checking `Auth::check()` or session entries (`afm_user_id`, `afm_role`).
  2. Call `AuditLog::logEvent()` with merged metadata.
- **Side effects:** Insert into `audit_logs`.

## CompletionService (`app/Services/CompletionService.php`)
- **Reason:** Student progress dashboards require consolidated logic that merges SIS course lists with AFM completion data.

### `getStudentProgress(string $sisStudentId, string $termCode, array $enrolledCourses): array`
- **Inputs:** Student ID, term code, SIS enrollment array `[['course_reg_no'=>..., 'course_name'=>...], ...]`.
- **Execution:**
  1. Use `FormCourseScope::whereIn('course_reg_no', ...)->where('term_code', $termCode)->with('form')->get()` to obtain required forms per course.
  2. Fetch all `completion_flags` for the student/term via repository.
  3. Iterate each SIS enrollment; match scopes by `course_reg_no` and search for completion flags where `form_id` and `course_reg_no` match.
  4. Build an array entry per (course, form) with course details, form title, status string (`completed` vs `not_started`), and boolean.
- **Side effects:** None.

## CompletionTrackingService (`app/Services/CompletionTrackingService.php`)
- **Reason:** Student controllers and QA overrides require the same rules when determining required/pending forms, so logic resides here.

### `getRequiredFormsForStudent(string $studentId, array $courses, string $termCode): Collection`
- Queries `form_course_scope` for the student's course list plus service scopes for the term, filters to forms where `is_active && is_published`, and returns collection entries of `{form, course_reg_no|null, type}`.

### `getCompletedFormsForStudent(string $studentId, string $termCode): Collection`
- Returns `CompletionFlag` rows for the student/term with `form` relation eager-loaded.

### `getPendingFormsForStudent(...)`
- Calls `getRequired...` and `getCompleted...`, filters out any required entry whose (form_id, course_reg_no) exists inside the completion collection.

### `isFormComplete(Form $form, string $studentId, ?string $courseRegNo, string $termCode): bool`
- Executes `CompletionFlag::where(...)->exists()`.

### `markManualCompletion($formId, $studentId, $courseRegNo, $termCode, $reason): CompletionFlag`
- Uses `CompletionFlag::markComplete()` with `source='qa_manual'` and logs audit entry via `logAudit('completion','manual_override', ['reason'=>$reason], 'CompletionFlag', $flag->id)`.

## FeedbackService (`app/Services/FeedbackService.php`)
- **Reason:** Legacy flows still rely on form templates stored in `afm_form_templates`; this service ensures consistent template-based response creation.

### `startResponse(int $formTemplateId, string $sisStudentId, ?string $courseRegNo, string $termCode): Response`
- Loads `AfmFormTemplate` by ID.
- Validates `$courseRegNo` is present when template type is `course`; throws `InvalidArgumentException` otherwise.
- For `form_type === 'system'`, forces `$courseRegNo = 'system'` to satisfy non-null schema.
- Calls `Response::firstOrCreate()` keyed by (`form_template_id`, `sis_student_id`, `course_reg_no`, `term_code`) and populates defaults (`status='not_started'`, `student_hash = hash(...)`, `last_active_at = now()`).
- Returns `Response` model.

## FormBuilderService (`app/Services/FormBuilderService.php`)
- **Reason:** Section/question mutations touch multiple tables and must prevent edits when responses exist; service enforces that invariant and handles audit logging.

### `addSection(Form $form, array $data): FormSection`
- Wraps in DB transaction:
  1. `ensureEditable($form)`; throws `Exception` if published with responses.
  2. Set `order` to `max(order) + 1` when absent.
  3. Create section via `$form->sections()->create($data)`.
  4. `logAudit('form_structure','add_section', ['title'=>$section->title], 'FormSection', $section->id)`.

### `updateSection(FormSection $section, array $data): FormSection`
- Calls `ensureEditable($section->form)` and updates attributes.

### `deleteSection(FormSection $section): void`
- Ensures editability, deletes section, logs audit.

### `reorderSections(Form $form, array $orderedIds): void`
- Ensures editability; iterates `$orderedIds` and updates each `FormSection::where('id',$id)->update(['order'=>$index+1])` within transaction.

### `addQuestion(FormSection $section, array $data): Question`
- Ensures editability, sets default order, creates question, logs audit.

### `updateQuestion`, `deleteQuestion`, `reorderQuestions`
- Mirror the section operations for questions.

### `addOption(Question $question, array $data): QuestionOption`
- Ensures editability, sets order default, inserts option, returns it.

### `updateOption`, `deleteOption`
- Update/delete `question_options` rows after editability check.

### `ensureEditable(Form $form): void`
- Throws generic `Exception` stating "Cannot edit form structure after it has responses" if `$form->is_published && $form->has_responses` evaluates true.

## FormManagementService (`app/Services/FormManagementService.php`)
- **Reason:** Publishing/archiving/duplicating forms requires transactions, versioning, and audit logging beyond simple controller logic.

### `createForm(array $data): Form`
- Inside transaction, calls `Form::create()` with base flags (`version=1`, `is_active=false`, `is_published=false`), logs audit, returns form.

### `updateForm(Form $form, array $data): Form`
- Updates metadata and logs `form/update` audit. Structural edits remain in FormBuilderService.

### `publishForm(Form $form): Form`
- Validates `sections()->count() > 0` and `questions()->count() > 0`, throws `Exception` if missing.
- Sets `is_published=true`, `is_active=true`, logs audit.

### `archiveForm(Form $form): Form`
- Sets `is_active=false`, logs audit.

### `duplicateForm(Form $form, string $newCode, string $newTitle): Form`
- Performs deep copy inside transaction: clones form minus publish flags, iterates sections/questions/options replicating each, logs audit referencing source form ID.

### `assignToAllCourses(Form $form, string $termCode): void`
- Fetches all `SisCourseRef` for term, iterates rows, `FormCourseScope::firstOrCreate()` each with `is_required=true`. Logs audit with term.

### `assignToSpecificCourses(Form $form, array $courseRegNos, string $termCode): void`
- Iterates provided course list and `firstOrCreate`s scope rows. Logs count in audit metadata.

### `assignServiceScope(Form $form, string $termCode): void`
- Creates `form_course_scope` row with `course_reg_no = null`, `applies_to_services = true`. Logs audit.

### `removeAssignment(Form $form, ?string $courseRegNo, string $termCode): void`
- Deletes matching scope rows and logs audit referencing course+term.

## JsonPayloadVerifier (`app/Services/JsonPayloadVerifier.php`)
- **Reason:** HMAC validation, timestamp checks, and role enforcement must be reusable across intake paths (JSON simulator and token API).

### `__construct()`
- Loads config values for shared secret, issuer, audience, and version.

### `verify(array $payload): array`
- **Inputs:** Associative array parsed from SIS JSON.
- **Execution:**
  1. Confirm all common fields exist: `iss`, `aud`, `v`, `request_id`, `role`, `issued_at`, `expires_at`, `nonce`, `sig_alg`, `signature`.
  2. For role `student` ensure `student_id` and `courses`; for `qa_officer` ensure `user_id`.
  3. Validate `iss`, `aud`, `v` equal config values.
  4. Map `sig_alg` to PHP algorithm (sha256). Reject unsupported algorithms.
  5. Confirm `role` exists in `config('afm_sso.allowed_roles')`.
  6. Parse timestamps (int or ISO8601). Reject payloads issued more than 5 minutes in the future or already expired.
  7. Canonicalize payload using `AfmJsonCanonicalizer::canonicalize($payload)` (excluding signature) and compute `hash_hmac($phpAlgo, canonical, sharedSecret)`.
  8. Compare with provided signature using `hash_equals`.
  9. Return `['valid'=>true]` on success or `['valid'=>false,'error'=>... ]` on failure at any step.
- **Side effects:** None. Caller logs results.

### `computePayloadHash(array $payload): string`
- Canonicalizes payload and returns `hash('sha256', canonical)` for audit storage.

## NotificationDispatcher (`app/Services/NotificationDispatcher.php`)
- **Reason:** Implements outbox writes and dequeue semantics without tying logic to controllers or jobs.

### `queueReminder(string $recipient, string $studentName, string $courseName): NotificationOutbox`
- Inserts into `notification_outbox` with fields: `channel='email'`, `recipient`, `subject='Action Required: ...'`, `body` string including student/course, `status='pending'`, `send_after=now()`.

### `dispatchPending(): void`
- Queries repository `getPendingToSend()`.
- For each notification:
  1. Wrap in try/catch.
  2. (Placeholder) would send email; currently calls `$notification->markAsSent()`.
  3. On exception, `$notification->markAsFailed($e->getMessage())`.

## NotificationService (`app/Services/NotificationService.php`)
- **Reason:** QA reminders require identifying non-completers, batching notifications, and logging, which is beyond controller scope.

### `__construct(QaReportingService $qaReportingService)`
- Stores dependency.

### `sendReminderToNonCompleters(string $termCode, ?string $courseRegNo, ?string $department): int`
- **Execution:**
  1. Call `QaReportingService::getNonCompleters($termCode, $courseRegNo)`.
  2. If `$department` provided, filter collection by `department` attribute.
  3. Count result; return 0 immediately if no non-completers.
  4. Group by `sis_student_id` to avoid duplicate notifications.
  5. Iterate groups, derive course list for logging (actual email send is TODO).
  6. Log `notification/send_reminders` via `logAudit()` with term/course/count metadata.
  7. Return count of targeted students.
- **Side effects:** Only audit log at present (no DB writes beyond logging when messaging implemented).

## QaReportingService (`app/Services/QaReportingService.php`)
- **Reason:** QA controllers require aggregated stats and exports across multiple tables; service encapsulates SQL-heavy logic.

### `getCurrentTerm(): string`
- Returns hardcoded `202510` (update when term changes).

### `getOverviewMetrics(string $termCode): array`
- Counts eligible students via `AfmStudentRegistry::where('term_code', ...)->distinct('sis_student_id')->count()`.
- Counts completed students via `Response::where('term_code', ...)->whereHas('form', code=COURSE_EVAL_DEFAULT)->where('status','submitted')->distinct('sis_student_id')->count()`.
- Calculates participation rate = completed / eligible.
- Computes pending = max(eligible - completed, 0). `high_risk_courses` currently stubbed 0.

### `getParticipationByCourse(string $termCode): array`
- Calls private `getEnrollmentsFromAFMFeedback()` to build course map.
- For each course, count `CompletionFlag` rows per course (`distinct('sis_student_id')`).
- Calculate participation percentage and sort ascending, returning worst 10 courses.

### `getCompletionReport(string $termCode, ?string $courseRegNo, ?string $formType, ?string $status): array`
- Filters enrollment map by optional course substring.
- For each course, filter `CompletionFlag` query by `form_type` when provided.
- Compute `completed` count and `rate`, apply status filter (skip rows that don’t match `Completed`/`Not Completed`), build report entries.

### `getNonCompleters(string $termCode, ?string $courseRegNo): Collection`
- For each enrollment (optionally filtered by course), check `CompletionFlag::where(...)->exists()` for each student; if false, create `stdClass` with SIS ID, placeholder name/email, department placeholder, and push to collection.

### `getResponseSummary(Form $form, ?string $courseRegNo): array`
- For each `Question` belonging to the form:
  1. Join `response_items` with `responses` and filter by `form_id`, `status='submitted'`, optional `course_reg_no`.
  2. For `likert`/`rating`, compute average `numeric_value` and distribution via `groupBy('numeric_value')`.
  3. For MCQ/Yes-No, count occurrences by `option_value`.
  4. Store summary keyed by question ID.

### `getStudentReport(...)`
- Build enrollment map; optionally filter by course string match.
- Load `CompletionFlag` rows for term (optionally filtering by `form_type`). Group by `sis_student_id-course_reg_no` for quick lookup.
- For each student/course combination, determine `status` by presence in completions group, apply filters for `student_id` substring and `status`, create array with student/course info.

### `getEnrollmentsFromAFMFeedback(string $termCode): array` (private)
- Builds enrollment map using `completion_flags` and `responses` for the term, ensuring course dictionaries include at least placeholder student names. Uses helper methods to parse course codes/names.

### `extractCourseCode`, `extractCourseName` (private)
- Parse registration numbers to human-readable codes/names.

## ResponseSubmissionService (`app/Services/ResponseSubmissionService.php`)
- **Reason:** Student submission flows require transactional updates across `responses`, `response_items`, `completion_flags`, and audit logs; controller delegates to this service to keep HTTP logic simple.

### `createOrResumeDraft(Form $form, string $studentId, ?string $courseRegNo, string $termCode): Response`
- Queries `Response::where('form_id', ...)->where('sis_student_id', ...)->where('course_reg_no',$courseRegNo)->where('term_code',$termCode)->first()`.
- If exists, return it.
- Otherwise `Response::create([...,'status'=>'draft'])` and return.

### `saveDraft(Response $response, array $answers): Response`
- Throws `Exception` if `status === 'submitted'`.
- Starts DB transaction:
  1. Iterate `$answers` (keyed by question ID) and call `saveAnswer($response, $questionId, $value)`.
  2. After loop, call `$response->touch()` to update `updated_at`.
- Returns response.

### `submitResponse(Response $response, array $answers): Response`
- Throws `Exception` if already submitted.
- Transactional flow:
  1. Persist answers by calling `saveAnswer()` for each entry.
  2. `validateSubmission($response)` ensures required questions answered (checks `response->form->questions`).
  3. `$response->submit()` sets `status='submitted'` and `submitted_at=now()`.
  4. Call `CompletionFlag::markComplete(...)` with `source='student'`.
  5. `logAudit('response','submit', [], 'Response', $response->id)` records audit.
- Returns response.

### `saveAnswer(Response $response, $questionId, $value): void` (protected)
- Loads `Question::find($questionId)`; returns if missing.
- Deletes existing `response_items` for that question.
- Based on `qtype`:
  - `mcq_multi`: iterate array values, create `ResponseItem` rows with `option_value`.
  - `likert`/`rating`: create row with `numeric_value`.
  - `text`/`textarea`: create row with `text_value`.
  - Others (`mcq_single`,`yes_no`): store in `option_value`.

### `validateSubmission(Response $response): void` (protected)
- Reloads `$response->load('items')` and groups items by `question_id`.
- For each question in `$response->form->questions`:
  - If `required` and no items exist → record error message.
  - For `likert`, ensure `numeric_value` within `scale_min`/`scale_max` inclusive.
- If `$errors` not empty, throw `ValidationException::withMessages($errors)`.

## SsoJsonIntakeService (`app/Services/SsoJsonIntakeService.php`)
- **Reason:** Shared between `/sso/json-intake` controller and dev simulator so logic stays in one place.

### `handle(array $payload): string`
- **Preconditions:** Payload originates from SIS; shared secret configured.
- **Execution:**
  1. Validate envelope: `iss`, `aud`, `v` equal expected strings; `role` present.
  2. Parse timestamps using `Carbon::parse()`. Reject if missing or expired. Enforce "issued in future" check by comparing to `now()->addMinutes(5)`.
  3. Confirm `sig_alg` and `signature` exist (local mode does not recompute HMAC but ensures fields exist).
  4. Clear prior session keys (`afm_role`, `afm_user_id`, `afm_user_name`, `afm_term_code`, `afm_courses`).
  5. Switch on `$role`:
     - `student`: call `validateStudentPayload()` ensuring `student_id`, `student_Name`, `term`, `courses` array exist. Map `term` string to code via `mapTermLabelToCode()`. Write session keys: `afm_role='student'`, `afm_user_id`, `afm_user_name`, `afm_term_label`, `afm_term_code`, `afm_courses`. Return `/student/dashboard`.
     - `qa` or `qa_officer`: call `validateQaPayload()` (requires `user_id`). Store `afm_role` as actual role plus `afm_user_id`/`afm_user_name`. Return `/qa`.
     - `admin`: call `validateAdminPayload()` (requires `user_id`). Store admin session keys. Return `/admin`.
     - Others: log warning and return `/dev/simulator`.
- **Side effects:** Writes session keys only.
- **Failures:** Throws `Exception` with descriptive messages for invalid envelopes; caught by controllers.

### `validateStudentPayload(array $payload): void`
- Ensures required keys exist and `courses` is array; throws `Exception` otherwise.

### `mapTermLabelToCode(string $termLabel): string`
- Converts human-readable terms to codes using pre-defined mapping; defaults to `202510`.

### `validateQaPayload`, `validateAdminPayload`
- Ensure `user_id` present.

## SsoTokenIntakeService (`app/Services/SsoTokenIntakeService.php`)
- **Reason:** Alternative SSO flow that creates `afm_session_tokens` for handshake URLs (used by Feature tests and future SIS integration).

### `handle(array $payload, Request $request): array`
- **Execution:**
  1. Call `JsonPayloadVerifier::verify($payload)`. If invalid, log rejection via `AuditLogger` and throw `Exception` with 401 code.
  2. Use `TokenService::isNonceUsed($payload['request_id'], $payload['nonce'])`; if true log rejection and throw `Exception('Token already used')`.
  3. Call `TokenService::createToken($payload, $request->ip(), $request->userAgent())` which inserts new `afm_session_tokens` row and caches it in Redis.
  4. Log success via `AuditLogger::logSsoValidated()` storing payload hash and student/user ID.
  5. Return array `['status'=>'success','redirect_to'=>url("/sso/handshake/{$token->id}"),'token_id'=>$token->id]`.
- **Side effects:** See TokenService (DB insert + Redis cache + audit log).

## TokenService (`app/Services/TokenService.php`)
- **Reason:** Encapsulates `afm_session_tokens` persistence, Redis caching, and replay detection.

### `createToken(array $payload, string $clientIp, string $userAgent): AfmSessionToken`
- **Execution:**
  1. Determine `$userId = $payload['student_id'] ?? $payload['user_id']` and `$courses = $payload['courses'] ?? []`.
  2. Compute `$payloadHash = app(JsonPayloadVerifier::class)->computePayloadHash($payload)`.
  3. Call `AfmSessionToken::create()` with fields: `request_id`, `nonce`, `payload_hash`, `sis_student_id = $userId`, `courses_json = $courses`, `role`, `issued_at`, `expires_at`, `client_ip`, `user_agent`.
  4. Call `cacheToken($token)` to store JSON snapshot in Redis key `afm:session:{id}` with TTL from config (default 120 seconds).
  5. Return token model.
- **Side effects:** Insert row in `afm_session_tokens`; `Redis::setex` call writes cache entry. On Redis failure, logs to `ssotoken` channel but continues.

### `getToken(int $tokenId): ?AfmSessionToken`
- Attempts `Redis::get("afm:session:{$tokenId}")`; if success, still loads `AfmSessionToken::find($tokenId)` (DB is source of truth). On Redis failure, logs warning then hits DB.

### `isNonceUsed(string $requestId, string $nonce): bool`
- Executes `AfmSessionToken::where('request_id',$requestId)->where('nonce',$nonce)->exists()`.

### `consumeToken(AfmSessionToken $token): void`
- Sets `consumed_at = now()` and persists. Attempts to delete Redis key `afm:session:{id}`; logs warning on failure.

### `cacheToken(AfmSessionToken $token): void` (private)
- JSON-encodes subset of token fields and stores in Redis using `setex($key, $ttl, $data)`.

