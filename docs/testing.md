# Testing & Verification Guide

This guide explains how to reproduce critical flows manually and via automated tests. Follow the steps in order to verify end-to-end integrity.

## Environment Preparation
1. Copy `.env.example` → `.env.testing` and set:
   ```env
   APP_ENV=testing
   DB_CONNECTION=mysql
   DB_DATABASE=afm_testing
   DB_USERNAME=your_user
   DB_PASSWORD=your_pass
   CACHE_DRIVER=array
   SESSION_DRIVER=array
   QUEUE_CONNECTION=sync
   AFM_SSO_SHARED_SECRET=testing-secret-32chars
   AFM_SSO_INTEGRATION_MODE=simulated
   ```
2. Run `php artisan migrate --env=testing` to build schema.
3. Seed baseline data:
   ```bash
   php artisan db:seed --class=DefaultFormsSeeder --env=testing
   php artisan db:seed --class=SimSisAfmDemoSeeder --env=testing
   php artisan db:seed --class=StaffRolesSeeder --env=testing
   php artisan db:seed --class=UserSeeder --env=testing   # optional admin login
   ```
4. Reset between suites using `php artisan migrate:fresh --seed --env=testing`.

## Manual Scenario Matrix
Follow these reproducible steps instead of relying on ambiguous “smoke tests”.

### 1. SSO Happy Path (Student)
1. Launch local server: `php artisan serve`.
2. Open `http://127.0.0.1:8000/dev/simulator`.
3. Choose a student card (e.g., Nahla Burweiss) and click "Login with this JSON".
4. Expected results:
   - `DevSimulatorController@login` posts payload to `SsoJsonIntakeService::handle`.
   - Session keys set: `afm_role = student`, `afm_user_id = payload.student_id`, `afm_term_code = 202510`, `afm_courses = payload.courses`.
   - Browser redirected to `/student/dashboard` showing correct student name and counts.
   - `afm_student_registry` row created/updated for `sis_student_id=payload.student_id` + `term_code=202510`.

### 2. SSO Failure Paths
Perform each modification manually (edit JSON using browser dev tools or `curl`).

| Scenario | Steps | Expected Behavior |
| --- | --- | --- |
| Expired payload | Copy JSON, change `expires_at` to 10 minutes ago, submit via simulator form (inspect element to edit hidden field). | `SsoJsonIntakeService` throws "Token expired"; user returned to simulator with flash error "Login Failed: Token expired". No session keys stored. |
| Future-issued payload | Set `issued_at` to 60 minutes ahead. | Service throws "Token issued in the future"; same redirect with error. |
| Missing signature | Remove `signature` key before submitting. | Service throws "Missing Signature"; no session data written. |
| Replay nonce (token flow) | Use `Tests\Feature\Sso\QaSsoTest` approach: call `/api/sso/token` twice with same `request_id`/`nonce` (can mimic via `php artisan tinker`). Second call returns HTTP 401 `Token already used` and logs `sso_rejected`. |

### 3. Student Submission Lifecycle
1. Complete SSO login (scenario 1).
2. From dashboard click a pending course.
3. Fill at least one answer and click "Save Draft" (AJAX call to `/student/response/{id}/draft`).
4. Inspect network response: JSON `success=true`; DB check: `response_items` rows exist for answered questions.
5. Reload form: answers pre-fill from `response_items`.
6. Submit via "Submit" button. Verify:
   - `responses.status` changes to `submitted`, `submitted_at` not null.
   - `completion_flags` contains `form_id`, `sis_student_id`, `course_reg_no`, `term_code` with `source='student'`.
   - Dashboard now lists the form under "completed".
   - `AuditLogger` entry `feedback_submitted` appears (check `audit_logs`).

### 4. Completion Flag Propagation
1. After submission, query `completion_flags` for matching context.
2. Run `php artisan tinker`:
   ```php
   App\Models\CompletionFlag::where('sis_student_id','4401')->where('course_reg_no','SE401-Spring2025')->get();
   ```
3. Ensure exactly one row exists and `source = 'student'`, `completed_at` equals `responses.submitted_at`.

### 5. QA Reports Validation
1. Login as QA via simulator (use QA payload). Ensure session `afm_role=qa_officer`.
2. Visit `/qa/reports/completion`:
   - Change `term`, `course`, `form_type`, `status` filters; confirm table updates.
   - Click CSV/XLSX/PDF export; verify downloads contain same filtered data and `audit_logs` record `export_generated` event.
3. Visit `/qa/reports/students` and `/qa/reports/responses`; ensure navigation tabs highlight correctly when switching routes.
4. For responses view: choose a course-specific form, filter by course, and use "Load Responses". Spot check table values against `response_items` records.

### 6. Authorization Matrix
Test each role to confirm middleware enforcement.

| Route | Login Method | Expected |
| --- | --- | --- |
| `/student/dashboard` | QA payload | Redirect to simulator with error (EnsureAfmStudentRole).
| `/qa` | Student payload | Redirect to simulator with error (EnsureAfmQaRole).
| `/admin/config` | Student payload | Redirect to `/` via `afm.auth` or 403 if `role:admin` fails.
| `/qa/staff` POST | No session | Redirect to `/dev/simulator` due to middleware.

### 7. Exports Accuracy
1. Populate sample data via `php artisan db:seed --class=QaOverviewTestDataSeeder`.
2. For each report (`completion`, `students`, `responses`), trigger `export=xlsx|csv|pdf`.
3. Open downloaded file and compare counts to on-screen table. Confirm grouping matches DB queries by running manual SQL (e.g., `SELECT COUNT(*) FROM completion_flags WHERE term_code='202510';`).

## Automated Test Suite
Run `php artisan test` (Laravel 11 uses Pest/PHPUnit). Notable files:

| Test Class | Location | Coverage |
| --- | --- | --- |
| `Tests\Feature\Sso\QaSsoTest` | `tests/Feature/Sso/QaSsoTest.php` | Valid QA payload acceptance, token reuse rejection, handshake redirect, authorization gating. |
| `Tests\Feature\EndToEndFlowsTest` | `tests/Feature/EndToEndFlowsTest.php` | Student + QA flows from SSO through reporting. |
| `Tests\Feature\Student\*` | `tests/Feature/Student` | Dashboard context building, form eligibility, submission controllers. |
| `Tests\Feature\QAFlowTest` | `tests/Feature/QAFlowTest.php` | QA user can load overview, publish forms, and view reports. |
| `Tests\Feature\QA\OverviewDataTest` | `tests/Feature/Qa/OverviewDataTest.php` | Ensures `QaReportingService` aggregates data correctly. |
| `Tests\Feature\ScenarioIntegrationTest` | `tests/Feature/ScenarioIntegrationTest.php` | Integration of SIS enrollments with AFM submissions. |
| `Tests\Unit\*` | `tests/Unit` | Model relationship assertions and service-level tests (e.g., `ResponseSubmissionExpandedTest`, `FormServicesExpandedTest`). |

### Running Subsets
- `php artisan test --filter=Sso` to execute only SSO-related tests.
- `php artisan test tests/Feature/Student` for student controllers.
- Use `--coverage` with Xdebug enabled for thesis appendices.

## Regression Checklist
Before shipping, verify:
1. Student SSO still creates `afm_student_registry` rows and restricts course access.
2. `ResponseSubmissionService` enforces required questions (try submitting with missing answers; expect validation errors).
3. QA exports record audit events.
4. `afm_session_tokens` rows expire when TTL reached (inspect `expires_at` and call `TokenService::getToken()` in Tinker to confirm `isExpired()` behavior).
5. Authorization matrix per table above holds true.

