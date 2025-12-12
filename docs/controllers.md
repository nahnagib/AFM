# Controllers Technical Specification

Each section references the actual class under `app/Http/Controllers`. Inputs sourced from HTTP (route params, query params, JSON bodies) are treated as untrusted until validated. Session keys originate from the SSO intake services.

## Student\\StudentDashboardController (`app/Http/Controllers/Student/StudentDashboardController.php`)
- **Role:** Render dashboard listing pending/completed evaluations.
- **Trusted inputs:** Session keys `afm_user_id`, `afm_term_code`, `afm_term_label`, `afm_user_name`, `afm_courses` (array of `{course_reg_no, course_code, course_name}`).
- **Untrusted inputs:** None.
- **Services:** Constructor receives `CompletionTrackingService` (not invoked yet).
- **Middleware:** `web` → `afm.student`.

### `index(Request $request): View`
- **Inputs:** `$request` (unused).
- **Preconditions:**
  1. Session stores SSO-derived keys listed above.
  2. Default forms with `code` `COURSE_EVAL_DEFAULT` and `SERVICES_EVAL_DEFAULT` exist and `is_active = true`.
- **Execution Flow:**
  1. Read session values (student ID, term code/label, student name, enrolled courses).
  2. Upsert `afm_student_registry` row keyed by (`sis_student_id`, `term_code`) with student name, `courses_json`, and `last_seen_at = now()`.
  3. Update the same row’s `first_seen_at` if still null by querying the record and setting `first_seen_at` to the earliest timestamp (prevents overwriting initial visit).
  4. Load course feedback form: `Form::where('code','COURSE_EVAL_DEFAULT')->where('is_active',true)->first()`; repeat for services form. Abort 500 if either missing.
  5. For each course in `afm_courses`, query `responses` for a submitted row matching (`form_id = courseForm->id`, `sis_student_id`, `course_reg_no`, `term_code`, `status='submitted'`).
     - Push metadata into `$completedCourseForms` when query returns true; otherwise push into `$pendingCourseForms`.
  6. Determine if the services form has a submitted response with `course_reg_no` null for this student/term; populate completed/pending service collections accordingly.
  7. Concatenate course and service collections to build `$pendingForms` and `$completedForms`, then compute counts.
  8. Prepare Blade data array containing pending/completed lists, student info, and placeholder arrays maintained for backward compatibility with legacy view code.
  9. Return `view('student.dashboard', $data)`.
- **Side Effects:**
  - Writes/updates `afm_student_registry` columns `student_name`, `courses_json`, `first_seen_at`, `last_seen_at`.
- **Failure Paths:**
  - Missing default forms → `abort(500, ...)`, producing HTTP 500 and Laravel logs.
- **Security Notes:**
  - Trust boundary at middleware: only `afm_role=student` can reach controller. All course eligibility checks rely on session data, preventing crafted query parameters from enumerating other courses.

## Student\\StudentFormController (`app/Http/Controllers/Student/StudentFormController.php`)
- **Role:** Display a form for the given context and enforce eligibility/completion rules.
- **Trusted inputs:** Session keys `afm_user_id`, `afm_role`, `afm_courses`, `afm_term_code`.
- **Untrusted inputs:** Route parameter `$formId`, query parameter `course_reg_no`.
- **Services:** `CompletionTrackingService`, `ResponseSubmissionService`.
- **Middleware:** `web` → `afm.student`.

### `show(Request $request, $formId): View`
- **Inputs:** `$formId` (string/int), `$request->query('course_reg_no')`.
- **Preconditions:**
  1. Form exists with matching ID and `is_active && is_published`.
  2. Session role equals `student`.
- **Execution Flow:**
  1. Load form with nested relations: `sections.questions.options` and `sections.questions.staffRole.staffMembers` filtering active staff.
  2. Read session values for student ID, term code, and course list; read `$courseRegNo` from query string.
  3. Abort 404 if form inactive or unpublished.
  4. Abort 403 if session role not `student`.
  5. Determine eligibility:
     - If form code `COURSE_EVAL_DEFAULT`, require `$courseRegNo` and ensure it exists inside `afm_courses` (strict comparison on `course_reg_no`).
     - If form code `SERVICES_EVAL_DEFAULT`, mark eligible regardless of course.
     - Otherwise mark ineligible.
  6. Abort 403 when eligibility fails with message "YOU ARE NOT ELIGIBLE TO COMPLETE THIS FORM.".
  7. Invoke `CompletionTrackingService::isFormComplete($form, $studentId, $courseRegNo, $termCode)`; redirect to dashboard with info flash if already completed.
  8. Call `ResponseSubmissionService::createOrResumeDraft($form, $studentId, $courseRegNo, $termCode)` to obtain `responses` row (creates `status='draft'` row when missing).
  9. Load `response->items` and group by `question_id`, transforming multi-select answers into arrays and single answers into scalars for Blade.
 10. Resolve `$courseName` by locating the course in session array.
 11. Render `student.form` with form metadata, response ID, answers array, and course context.
- **Side Effects:**
  - May insert new row in `responses` table.
- **Failure Paths:** 404 for missing form, 403 for wrong role/eligibility, redirect with info when already completed.
- **Security Notes:** Session-provided courses prevent tampering. Response ownership maintained by services.

## Student\\StudentSubmissionController (`app/Http/Controllers/Student/StudentSubmissionController.php`)
- **Role:** Persist drafts and final submissions for a specific response.
- **Trusted inputs:** Session `afm_user_id`.
- **Untrusted inputs:** Route `$responseId`, validated request body `answers` (array keyed by question ID).
- **Services:** `ResponseSubmissionService`.
- **Middleware:** `web` → `afm.student`.

### `saveDraft(SaveDraftRequest $request, $responseId): JsonResponse`
- **Inputs:** `$responseId`, `$request->input('answers', [])` (validated by `SaveDraftRequest`).
- **Preconditions:** Response exists and belongs to the logged-in student.
- **Execution Flow:**
  1. `Response::findOrFail($responseId)`.
  2. Compare `response->sis_student_id` with `session('afm_user_id')`; abort 403 if mismatch.
  3. Invoke `ResponseSubmissionService::saveDraft($response, $answers)`.
  4. Return JSON body `{success: true, message: 'Draft saved successfully.', saved_at: now()->toISOString()}`.
- **Side Effects:** Service updates `response_items` (delete + insert per question) and touches `responses.updated_at`.
- **Failure Paths:** 403 on ownership mismatch; exceptions bubble to JSON error responses.
- **Security Notes:** Ownership enforcement prevents cross-student editing.

### `submit(SubmitResponseRequest $request, $responseId): RedirectResponse`
- **Inputs:** `$responseId`, validated answers array.
- **Preconditions:** Response belongs to student and is not already submitted.
- **Execution Flow:**
  1. Load response; abort 403 if `sis_student_id` mismatch.
  2. Call `ResponseSubmissionService::submitResponse($response, $answers)` inside try/catch.
  3. On success, redirect to `route('student.dashboard')` with success flash (Arabic message).
  4. Catch `ValidationException`: redirect back with validation errors and old input.
  5. Catch general `Exception`: redirect back with error flash (`حدث خطأ ...`).
- **Side Effects:** Service writes `response_items`, sets `responses.status='submitted'`, inserts `completion_flags`, logs audit event.
- **Failure Paths:** 403 for ownership, redirect with errors for validation failure, redirect with generic error for other exceptions.
- **Security Notes:** Service-level validation ensures required questions answered; completion flags only created after validation succeeds.

## QA\\QAOverviewController (`app/Http/Controllers/QA/QAOverviewController.php`)
- **Role:** Display aggregate participation metrics and high-risk courses.
- **Trusted inputs:** None.
- **Untrusted inputs:** Query parameter `term`.
- **Services:** `QaReportingService`.
- **Middleware:** `web` → `EnsureAfmQaRole`.

### `index(Request $request): View`
- **Inputs:** `$request->query('term')` (optional).
- **Execution Flow:**
  1. Determine `$termCode = $request->query('term', $qaReporting->getCurrentTerm())`.
  2. Call `getOverviewMetrics($termCode)` to retrieve counts (eligible students, participation rate, etc.).
  3. Call `getParticipationByCourse($termCode)` for top underperforming courses.
  4. Return `view('qa.overview', [...])` with metrics and term.
- **Side Effects:** None.
- **Failure Paths:** DB exceptions bubble to 500.
- **Security Notes:** Middleware ensures QA role; term filter limited to string value but service uses strict equality comparisons, preventing injection.

## QA\\QAFormsController (`app/Http/Controllers/QA/QAFormsController.php`)
- **Role:** Manage form records (CRUD + lifecycle + duplication).
- **Trusted inputs:** Session `afm_user_id` for auditing.
- **Untrusted inputs:** Route IDs, request payload fields (`code`, `title`, `description`, `courses[]`, etc.).
- **Services:** `FormManagementService`.
- **Middleware:** `web` → `EnsureAfmQaRole`.

### `index(Request $request): View`
- **Execution Flow:** Query `Form::with(['sections','courseScopes'])->orderBy('created_at','desc')->paginate(20)` and return `qa.forms.index`.
- **Side Effects:** None.
- **Failure Paths:** Database errors bubble.
- **Security Notes:** Read-only.

### `show($id): View`
- **Execution Flow:** Load `Form::with(['sections.questions.options','courseScopes.courseRef'])->findOrFail($id)` and render `qa.forms.show`.
- **Failure Paths:** 404 when ID missing.

### `create(): View`
- Renders blank creation view (no logic).

### `store(Request $request): RedirectResponse`
- **Inputs:** Body fields `code`, `title`, optional `description`, `type`, optional `courses[]`.
- **Preconditions:** `code` unique.
- **Execution Flow:**
  1. Validate input (Laravel validator ensures correct enums, unique code, string lengths, `courses` array of strings).
  2. Call `FormManagementService::createForm([...])`, passing `created_by = session('afm_user_id')`.
  3. If `type === 'course_feedback'` and `courses` provided, iterate list and create `FormCourseScope` rows for current term (`config('afm.current_term', '202410')`).
  4. Redirect to `qa.forms.show` for new form with success flash.
  5. On exception, redirect back with input and error flash.
- **Side Effects:** Inserts into `forms` and optionally `form_course_scope`. Service logs audit events.
- **Failure Paths:** Validation errors (422) -> redirect; DB/service exceptions -> redirect with `session('error')`.
- **Security Notes:** Input validated before DB writes; only QA role permitted.

### `edit($id): View`
- Load `Form::findOrFail($id)` and render edit view; 404 on missing id.

### `update(Request $request, $id): RedirectResponse`
- **Inputs:** Route ID and body fields `title`, optional `description`, optional `courses[]`.
- **Execution Flow:**
  1. Fetch form.
  2. Validate request (title required, courses array).
  3. Call `FormManagementService::updateForm($form, [...])` with `updated_by = session('afm_user_id')`.
  4. If form type is `course_feedback`, delete existing `FormCourseScope` rows for this form and recreate using provided courses + `config('afm.current_term')`.
  5. Redirect to show page with success flash or back with error on exception.
- **Side Effects:** Updates `forms` row, rewrites `form_course_scope` entries.
- **Failure Paths:** Validation errors -> redirect; service exceptions -> redirect with error message.
- **Security Notes:** Rebuilding scopes prevents stale assignments; service audits change.

### `destroy($id): RedirectResponse`
- **Execution Flow:**
  1. Fetch form.
  2. Attempt `$form->delete()` inside try/catch.
  3. On success redirect to index with success flash; on failure (FK constraint) redirect back with error flash `Cannot delete form...`.
- **Side Effects:** Deletes form and cascades to sections/questions if allowed.

### `publish($id): RedirectResponse`
- **Execution Flow:**
  1. Find form.
  2. Call `FormManagementService::publishForm($form)` (verifies sections/questions exist, sets `is_published=true`, `is_active=true`).
  3. Redirect back with success or catch exception and redirect with error message.
- **Side Effects:** Updates `forms` columns `is_published`, `is_active` and logs audit.

### `archive($id): RedirectResponse`
- Find form, call `FormManagementService::archiveForm($form)` (sets `is_active=false`), redirect to index.

### `duplicate(Request $request, $id): RedirectResponse`
- **Inputs:** Body `code` (unique), `title`.
- **Execution Flow:**
  1. Validate inputs.
  2. Load source form.
  3. Call `FormManagementService::duplicateForm($form, $request->code, $request->title)` which deep copies sections/questions/options.
  4. Redirect to new form’s show route with success flash.
  5. On exception (e.g., duplicate code) redirect back with error.
- **Side Effects:** Inserts new form + copies nested records.

## QA\\QAFormBuilderController (`app/Http/Controllers/QA/QAFormBuilderController.php`)
- **Role:** Structural edits to form sections/questions.
- **Trusted inputs:** None.
- **Untrusted inputs:** Route IDs, validated request payloads for sections/questions.
- **Services:** `FormBuilderService`.
- **Middleware:** `web` → `EnsureAfmQaRole`.

### `addSection(AddSectionRequest $request, $formId): RedirectResponse`
- **Inputs:** Form ID, validated section payload (title, optional description/order).
- **Execution Flow:**
  1. Load `Form::findOrFail($formId)`.
  2. Invoke `FormBuilderService::addSection($form, $request->validated())` (ensures form editable, creates section, logs audit).
  3. Redirect to `qa.forms.show` with success or catch exception and redirect with error message.
- **Side Effects:** Inserts row in `form_sections` with computed `order`.

### `addQuestion(AddQuestionRequest $request, $sectionId): RedirectResponse`
- **Inputs:** Section ID and validated question payload.
- **Execution Flow:**
  1. Find section via `FormSection::findOrFail($sectionId)`.
  2. Call `FormBuilderService::addQuestion($section, $request->validated())` (creates question, logs audit).
  3. Redirect to parent form show view.
- **Side Effects:** Inserts into `questions` (and `question_options` if payload includes options via service logic).

### `deleteSection($sectionId): RedirectResponse`
- **Execution Flow:**
  1. Load section; capture `$formId = $section->form_id`.
  2. Call `FormBuilderService::deleteSection($section)` (ensures editable, deletes section + nested questions/options).
  3. Redirect to `qa.forms.show($formId)` with success or back with error.

### `deleteQuestion($questionId): RedirectResponse`
- **Execution Flow:**
  1. Load question; determine parent form ID.
  2. Call `FormBuilderService::deleteQuestion($question)` (ensures editable, deletes row).
  3. Redirect to parent form show view.

## QA\\QAReportsController (`app/Http/Controllers/QA/QAReportsController.php`)
- **Role:** Serve completion/student/non-completer/response-analysis reports plus exports.
- **Trusted inputs:** None.
- **Untrusted inputs:** Query parameters `term`, `course`, `form_type`, `status`, `student_id`, `export`.
- **Services:** `QaReportingService`, Excel exports `CompletionReportExport`, `StudentReportExport`.
- **Middleware:** `web` → `EnsureAfmQaRole`.

### `completionReport(Request $request): View|BinaryFileResponse`
- **Execution Flow:**
  1. Extract filters from query (`term`, `course`, `form_type`, `status`, `export`). Default term via `getCurrentTerm()`.
  2. Call `QaReportingService::getCompletionReport($termCode, $courseRegNo, $formType, $status)`.
  3. If `export` present:
     - Build filename `completion_report_{term}.{ext}`.
     - For `pdf`, call `Excel::download(new CompletionReportExport($report), $filename, Excel::DOMPDF)`; else default Excel download.
     - Return binary response.
  4. If no export, render `qa.reports.completion` with report data and filters.
- **Side Effects:** Excel export classes log audit events when instantiated (see export implementation). HTTP response may be binary download.
- **Failure Paths:** Service exceptions; unsupported export handled by Excel throwing.
- **Security Notes:** QA-only access; filter inputs sanitized by being used as simple query parameters.

### `studentReport(Request $request): View|BinaryFileResponse`
- Same pattern as completion report but calls `getStudentReport()` and uses `StudentReportExport` for download.

### `nonCompleters(Request $request): View`
- **Execution Flow:**
  1. Read `term` (default) and `course` query parameters.
  2. If `course` missing, redirect back with error flash `Please select a course.`
  3. Call `QaReportingService::getNonCompleters($termCode, $courseRegNo)`.
  4. Render `qa.reports.non_completers` with collection.
- **Side Effects:** None.

### `responseAnalysis(Request $request, $formId): View`
- **Execution Flow:**
  1. Load form with `Form::with('questions')->findOrFail($formId)`.
  2. Read optional `course` query parameter.
  3. Call `QaReportingService::getResponseSummary($form, $courseRegNo)`.
  4. Render `qa.reports.response_analysis` with summary data.

## QA\\QAResponsesReportController (`app/Http/Controllers/QA/QAResponsesReportController.php`)
- **Role:** Detailed response listings with exports.
- **Trusted inputs:** None.
- **Untrusted inputs:** Query `term`, `form_id`, `course_reg_no`, `export`.
- **Middleware:** `web` → `EnsureAfmQaRole`.

### `index(Request $request): View|BinaryFileResponse`
- **Execution Flow:**
  1. Determine `$termCode = $request->query('term', '202510')`.
  2. Load available forms: `Form::where('is_active', true)->whereIn('code', ['COURSE_EVAL_DEFAULT','SERVICES_EVAL_DEFAULT'])->get()`.
  3. Build `$courses` list from `Response::where('term_code',$termCode)->whereNotNull('course_reg_no')->where('status','submitted')->select('course_reg_no')->distinct()->orderBy('course_reg_no')->pluck('course_reg_no')`.
  4. If `form_id` provided, call `loadDetailedResponses($termCode, $formId, $courseRegNo)`; otherwise set empty collection.
  5. When `export` query present and `form_id` set:
     - If collection empty, redirect back (without export param) with error `No responses available...`.
     - Else call `exportResponses($format, $rows, $termCode, $courseRegNo)` to stream file.
  6. When not exporting, render `qa.reports.responses` with forms, courses, filters, and rows.
- **Side Effects:** May return binary download. Flash error when export requested without data.

### `loadDetailedResponses(string $termCode, string $formId, ?string $courseRegNo): Collection` (private but critical)
- **Flow:**
  1. Build base query on `ResponseItem::query()`.
  2. Apply `with(['response.form', 'question.section'])` to eager load metadata.
  3. `whereHas('response', ...)` restricts to specified term, form ID, `status='submitted'`, and optional course.
  4. Join `responses` and `questions` tables to guarantee ordering by student/course/section/question.
  5. Select `response_items.*`, order by student and question order, then `get()`.
  6. Map each item to object containing `student_id`, `course_reg_no` (or `General`), `form_code`, `section_label`, `question_text`, `answer_value`, `submitted_at` (from response timestamp).
- **Side Effects:** None (read-only).

### `exportResponses(string $format, Collection $rows, string $termCode, ?string $courseRegNo)` (private)
- **Flow:**
  1. Build filename `afm_responses_{term}{optional course suffix}_{timestamp}`.
  2. Switch on `$format`:
     - `excel`: `Excel::download(new QAResponsesExport($rows), "$filename.xlsx")`.
     - `csv`: `Excel::download(new QAResponsesExport($rows), "$filename.csv", ExcelWriter::CSV)`.
     - `pdf`: Render `qa.reports.responses_export` with rows and feed into `Pdf::loadView(...)->setPaper('a4','landscape')`, then `->download("$filename.pdf")`.
  3. Unknown format → redirect to route without export param and flash error `Unsupported export format requested.`
- **Side Effects:** Binary response download; PDF generation uses DomPDF. Unsupported format triggers redirect/error.

## QA\\QAStaffController (`app/Http/Controllers/QA/QAStaffController.php`)
- **Role:** CRUD operations for staff directory.
- **Trusted inputs:** None.
- **Untrusted inputs:** Request filters and form data.
- **Middleware:** `web` → `EnsureAfmQaRole`.

### `index(Request $request): View`
- **Execution Flow:**
  1. Retrieve all `StaffRole` records.
  2. Determine `$selectedRole = $request->input('role', $roles->first()->role_key ?? null)`.
  3. Query `StaffMember::with('role')->when($selectedRole, fn($q,$roleKey) => $q->whereHas('role', ...))->orderBy('name_ar')->get()`.
  4. Render `qa.staff.index` with roles, staff list, selected role.

### `store(Request $request): RedirectResponse`
- **Inputs:** `staff_role_id`, `name_ar`.
- **Execution Flow:**
  1. Validate `staff_role_id` exists in `staff_roles` and `name_ar` length <=255.
  2. `StaffMember::create(['staff_role_id'=>$request->staff_role_id, 'name_ar'=>$request->name_ar, 'is_active'=>true])`.
  3. Redirect back with success flash.
- **Side Effects:** Inserts row in `staff_members`.

### `update(Request $request, $id): RedirectResponse`
- **Execution Flow:**
  1. Load staff member via `findOrFail($id)`.
  2. Validate `name_ar`.
  3. `$staff->update(['name_ar'=>$request->name_ar])`.
  4. Redirect back with success flash.

### `toggle($id): RedirectResponse`
- **Execution Flow:**
  1. Load staff member.
  2. `$staff->update(['is_active' => !$staff->is_active])`.
  3. Redirect back with success flash `Staff member status updated.`
- **Side Effects:** Writes boolean to `staff_members.is_active`.

## QA\\QARemindersController (`app/Http/Controllers/QA/QARemindersController.php`)
- **Role:** Placeholder UI for reminders.
- **Middleware:** `web` → `EnsureAfmQaRole`.

### `index(): View`
- Renders `qa.reminders.coming_soon`.

### `send(Request $request): RedirectResponse`
- Redirects back with flash `This feature is coming soon.`; no DB writes.

## Admin\\AdminConfigController (`app/Http/Controllers/Admin/AdminConfigController.php`)
- **Role:** Display and validate configuration settings (non-persistent yet).
- **Trusted inputs:** None.
- **Untrusted inputs:** POST body `current_term`, `high_risk_threshold`, `auto_save_interval`.
- **Middleware:** `web` → `afm.auth` → `role:admin`.

### `index(): View`
- Build `$config` array from `config('afm.*')` values (current term, QA threshold, auto-save interval, student hash salt) and render `admin.config.index`.

### `update(Request $request): RedirectResponse`
- Validate incoming fields (`current_term` string, `high_risk_threshold` numeric 0-1, `auto_save_interval` integer 10-300).
- Since persistence not implemented, just redirect back with success flash "Configuration updated successfully." after validation passes.

## Admin\\AdminAuditController (`app/Http/Controllers/Admin/AdminAuditController.php`)
- **Role:** List and inspect `audit_logs` entries.
- **Middleware:** `web` → `afm.auth` → `role:admin`.

### `index(Request $request): View`
- **Execution Flow:**
  1. Start `AuditLog::query()->orderBy('created_at','desc')`.
  2. Apply optional filters: `event_type`, `actor_id`, `from_date` (`whereDate >=`), `to_date` (`whereDate <=`).
  3. Paginate 50 rows per page and render `admin.audit.index` with paginator.

### `show($id): View`
- `AuditLog::findOrFail($id)` and render `admin.audit.show` with the log row.

## DevSimulatorController (`app/Http/Controllers/DevSimulatorController.php`)
- **Role:** Local-only SIS payload simulator.
- **Middleware:** Routes defined inside `if (app()->environment('local'))` block (no middleware beyond default web stack).

### `index(): View`
- **Execution Flow:**
  1. Build array of example students (`id`, `name`, `courses[]`), plus QA payload. For each student:
     - Assemble payload with fixed fields (`iss`, `aud`, `v`, `request_id`, `role='student'`, `student_id`, `student_Name`, `term`, `courses`, `issued_at`, `expires_at`, `nonce`, `sig_alg`).
     - Canonicalize via `AfmJsonCanonicalizer::canonicalize($rawPayload)` and sign with `hash_hmac('sha256', canonicalString, config('afm_sso.shared_secret'))`.
     - Append `signature` to payload.
  2. Do same for QA payload (`role='qa_officer'`).
  3. Render `dev.simulator` view with payload list.
- **Security Notes:** Only available in local env; uses actual signing algorithm to mimic SIS.

### `login(Request $request, SsoJsonIntakeService $intake): RedirectResponse`
- **Inputs:** POST `payload` or `json_payload` string.
- **Execution Flow:**
  1. Read JSON string from `payload` (direct) or `json_payload` (hidden field). Throw exception if missing.
  2. Decode JSON to associative array; throw if `json_last_error()` not `JSON_ERROR_NONE` or result not array.
  3. Log role + JSON payload via `Log::info`.
  4. Call `$intake->handle($payload)` to run same logic as `/sso/json-intake`.
  5. Redirect to returned path.
  6. On exception, redirect to simulator route with error flash `Login Failed: ...`.
- **Security Notes:** Because only local, no extra CSRF protections beyond default; still uses shared secret for signing.

## SsoJsonIntakeController (`app/Http/Controllers/SsoJsonIntakeController.php`)
- **Role:** Production endpoint for SIS JSON SSO submissions.
- **Middleware:** `web` (default) + CSRF unless route excluded in config.
- **Services:** `SsoJsonIntakeService`.

### `store(Request $request): RedirectResponse`
- **Inputs:** Raw JSON body (via `$request->json()->all()`); falls back to `$request->all()` for form posts (simulator compatibility).
- **Execution Flow:**
  1. Attempt to parse JSON. If payload empty, use `$request->all()` (handles `application/x-www-form-urlencoded`).
  2. Pass payload array to `$this->intakeService->handle($payload)`.
  3. Redirect to path returned by service (student dashboard, QA area, or admin root).
  4. Catch `Exception` → log error via `Log::error('SSO JSON Intake Failed: ' . $e->getMessage())` and redirect to `/` with flash `SSO Login Failed: ...`.
- **Side Effects:** Session is cleared/hydrated inside service; controller only redirects.
- **Failure Paths:** Invalid payloads cause redirect with flash error; service logs reason.
- **Security Notes:** Entire trust boundary resides in `SsoJsonIntakeService`; controller never mutates payload beyond parsing.

