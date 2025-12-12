# Routes

This list mirrors `routes/web.php` and `routes/api.php`. Middleware aliases come from `bootstrap/app.php` (`afm.auth`, `afm.student`, etc.).

## Dev & Landing

| Method | Path | Controller/Action | Middleware | Notes |
| --- | --- | --- | --- | --- |
| GET | /dev/simulator | `DevSimulatorController@index` | `web` (local env only) | Generates signed JSON payload cards. |
| POST | /dev/simulator/login | `DevSimulatorController@login` | `web` (local env only) | Posts selected JSON to `SsoJsonIntakeService`. |
| GET | /dev/login/student | closure | `web` | Redirects legacy dev logins to the simulator. |
| GET | /dev/logout | closure | `web` | Clears AFM session keys and returns to simulator. |
| GET | /afm | closure | `web` | Redirector: student→`/student/dashboard`, QA→`/qa`, admin→`/admin`, otherwise `/dev/simulator`. |
| GET | / | closure | `web` | Permanently redirects to `/afm`. |

## SSO & Auth

| Method | Path | Controller/Action | Middleware | Notes |
| --- | --- | --- | --- | --- |
| POST | /sso/json-intake | `SsoJsonIntakeController@store` | `web` | Entry point for SIS-signed payloads. Handles both student and staff roles. |
| GET | /api/user | closure | `auth:sanctum` | Default Laravel API stub. |

## Student Module (prefixed `/student`, name `student.*`)
Middleware: `web`, `afm.student` (`EnsureAfmStudentRole`).

| Method | Path | Controller@method | Description |
| --- | --- | --- | --- |
| GET | /student/dashboard | `Student\StudentDashboardController@index` | Builds pending/completed lists from session courses and `responses`. |
| GET | /student/form/{formId} | `Student\StudentFormController@show` | Renders a form for a specific course/service context (query `course_reg_no`). |
| POST | /student/response/{responseId}/draft | `Student\StudentSubmissionController@saveDraft` | AJAX endpoint to persist draft answers. |
| POST | /student/response/{responseId}/submit | `Student\StudentSubmissionController@submit` | Validates answers and finalizes submission. |

## QA Module (prefixed `/qa`, name `qa.*`)
Middleware: `web`, `EnsureAfmQaRole` (allows `qa` or `qa_officer`).

### Navigation & Forms
| Method | Path | Controller@method | Description |
| --- | --- | --- | --- |
| GET | /qa | `QA\QAOverviewController@index` | Dashboard metrics & participation table. |
| GET | /qa/forms | `QA\QAFormsController@index` | Paginated form list. |
| GET | /qa/forms/create | `QA\QAFormsController@create` | Form creation screen. |
| POST | /qa/forms | `QA\QAFormsController@store` | Validates and creates a form + optional course scopes. |
| GET | /qa/forms/{id} | `QA\QAFormsController@show` | Form detail with sections/questions. |
| GET | /qa/forms/{id}/edit | `QA\QAFormsController@edit` | Metadata edit screen. |
| PUT | /qa/forms/{id} | `QA\QAFormsController@update` | Saves title/description/course assignments. |
| DELETE | /qa/forms/{id} | `QA\QAFormsController@destroy` | Deletes the form (no soft delete). |
| POST | /qa/forms/{id}/publish | `QA\QAFormsController@publish` | Publishes and activates a form. |
| POST | /qa/forms/{id}/archive | `QA\QAFormsController@archive` | Marks form inactive. |
| POST | /qa/forms/{id}/duplicate | `QA\QAFormsController@duplicate` | Clones form with new code/title. |

### Form Builder (structure endpoints)
| Method | Path | Controller@method | Description |
| --- | --- | --- | --- |
| POST | /qa/forms/{formId}/sections | `QA\QAFormBuilderController@addSection` | Adds a section (ordered). |
| POST | /qa/sections/{sectionId}/questions | `QA\QAFormBuilderController@addQuestion` | Adds a question to a section. |
| DELETE | /qa/sections/{sectionId} | `QA\QAFormBuilderController@deleteSection` | Removes a section and its questions. |
| DELETE | /qa/questions/{questionId} | `QA\QAFormBuilderController@deleteQuestion` | Deletes a question. |

### Reports & Responses
| Method | Path | Controller@method | Description |
| --- | --- | --- | --- |
| GET | /qa/reports/completion | `QA\QAReportsController@completionReport` | Course-level completion rate; supports CSV/XLSX/PDF export via query `export`. |
| GET | /qa/reports/students | `QA\QAReportsController@studentReport` | Student-level status by filters. |
| GET | /qa/reports/non-completers | `QA\QAReportsController@nonCompleters` | Requires `course` query; lists students with no completion flag. |
| GET | /qa/reports/analysis/{formId} | `QA\QAReportsController@responseAnalysis` | Aggregated question stats for a form. |
| GET | /qa/reports/responses | `QA\QAResponsesReportController@index` | Responses-level view with Excel/CSV/PDF export and filters. |

### Reminders & Staff
| Method | Path | Controller@method | Description |
| --- | --- | --- | --- |
| GET | /qa/reminders | `QA\QARemindersController@index` | Placeholder view (“coming soon”). |
| POST | /qa/reminders/send | `QA\QARemindersController@send` | Placeholder action (flash info message). |
| GET | /qa/staff | `QA\QAStaffController@index` | Staff directory filtered by role. |
| POST | /qa/staff | `QA\QAStaffController@store` | Add staff member (role + Arabic name). |
| PUT | /qa/staff/{id} | `QA\QAStaffController@update` | Rename staff member. |
| POST | /qa/staff/{id}/toggle | `QA\QAStaffController@toggle` | Flips `is_active`. |

## Admin Module (prefixed `/admin`, name `admin.*`)
Middleware: `afm.auth`, `role:admin` (custom `RoleCheck`).

| Method | Path | Controller@method | Description |
| --- | --- | --- | --- |
| GET | /admin/config | `Admin\AdminConfigController@index` | Displays runtime config snapshot (term, thresholds). |
| POST | /admin/config | `Admin\AdminConfigController@update` | Validates updates (no persistence yet). |
| GET | /admin/audit | `Admin\AdminAuditController@index` | Filterable audit log list (event_type, actor, date range). |
| GET | /admin/audit/{id} | `Admin\AdminAuditController@show` | Single audit row detail page. |

## API Routes
The API router currently only exposes the default `/api/user` stub guarded by Sanctum. All functional APIs are routed through the web kernel because of the session-dependent SSO model.
