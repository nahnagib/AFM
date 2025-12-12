# Architecture

## Context (C4 Level 1)
AFM sits between the University Student Information System (SIS) and the actors who submit/read academic feedback.

- **Students** authenticate through SIS JSON SSO, land on AFM, and complete forms per course/term.
- **QA Officers** authenticate the same way (role `qa`/`qa_officer`) and access reporting, reminders, and staff management.
- **Admins** can log in locally (Laravel auth) to tune term-wide settings and review audit logs.
- **External SIS** signs JSON payloads with a shared secret. AFM verifies the payload, issues a short-lived `afm_session_tokens` record, and drives the UI from session state.

## Containers (C4 Level 2)

| Container | Technology | Responsibility | Interactions |
| --- | --- | --- | --- |
| Web App | Laravel 11 (PHP 8.2) | Hosts controllers, Blade views, jobs, queues, exports. | Talks to MySQL via Eloquent models, to Redis for token cache, and to DomPDF/Excel for report exports. |
| Database | MySQL / MariaDB | Stores forms, responses, SIS snapshots, session tokens, audit logs, etc. | Accessed via Eloquent/Query Builder. Schema defined in `database/migrations`. |
| Cache | Redis | Optional cache for session tokens (`afm:session:{id}`) and queues. | Used by `TokenService`. Gracefully falls back to DB if unavailable. |
| Clients | Browser | Students/QA/Admin views rendered with Blade + Tailwind. JS kept minimal (Alpine/Axios for drafts). |
| SIS | External HTTP client | Posts signed JSON payloads to `/sso/json-intake`. | No inbound API from AFM; SIS only pushes login payloads. |

## Components (C4 Level 3)

| Component | Description | Key Files |
| --- | --- | --- |
| **SSO Intake** | Verifies JSON payloads, enforces HMAC, issues `afm_session_tokens`, and writes session attributes used by middleware. Handles QA vs student role routing. | `app/Services/JsonPayloadVerifier.php`, `app/Services/SsoJsonIntakeService.php`, `app/Services/SsoTokenIntakeService.php`, `app/Services/TokenService.php`, `app/Http/Controllers/SsoJsonIntakeController.php`. |
| **Student Portal** | Builds dashboards, enforces eligibility for forms, manages drafts/submissions, and writes completion flags. | `StudentDashboardController`, `StudentFormController`, `StudentSubmissionController`, `ResponseSubmissionService`, `CompletionTrackingService`, Blade views under `resources/views/student`. |
| **QA Portal** | Provides overview metrics, form CRUD, form builder operations, staff directory, reports/exports, and reminder stubs. | `QA*Controller` classes, `QaReportingService`, `FormManagementService`, `FormBuilderService`, `QAResponsesReportController`, views under `resources/views/qa`. |
| **Admin Console** | Simple configuration read/write plus audit log display. | `AdminConfigController`, `AdminAuditController`, `resources/views/admin`. |
| **Reporting & Exports** | Data aggregation + exports (Excel/PDF/CSV). | `app/Services/QaReportingService.php`, `app/Exports/*`, `QAReportsController`, `QAResponsesReportController`. |
| **Notifications & Registry** | Tracks first/last student visits and queues reminder messages (outbox pattern). Future reminder jobs will pick rows from `notification_outbox`. | `AfmStudentRegistry` model, `NotificationService`, `NotificationDispatcher`, `NotificationOutboxRepository`. |
| **Audit & Security** | Centralized audit logging, role middleware, and replay protection. | `AuditLogger`, `app/Models/AuditLog.php`, middleware classes in `app/Http/Middleware`. |

## Data Flow Summary
1. SIS sends a signed JSON payload to AFM → `JsonPayloadVerifier` checks signature/time/nonce.
2. `TokenService` persists the payload context (`afm_session_tokens`) and mirrors it in Redis.
3. Middleware stores `afm_role`, `afm_user_id`, `afm_courses`, etc. in the HTTP session.
4. Student/QA controllers load data from session, query domain tables (`forms`, `responses`, `completion_flags`, SIS shadows), and render Blade views.
5. Submissions create/update `responses` and `response_items`, then stamp `completion_flags`.
6. QA exports hit aggregated queries in `QaReportingService` and stream Excel/PDF/CSV using Maatwebsite Excel or DomPDF.
7. All sensitive actions log to `audit_logs` for traceability.
