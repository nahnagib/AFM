# Database Reference

Every table defined in `database/migrations` is described below with lifecycle notes (who writes/updates/reads it) and column-level metadata. Sources identify where data originates: SIS payload, user input via QA UI, or system-generated values.

## Core Survey Tables

### forms
- **Purpose:** Stores canonical survey definitions (course and system evaluations).
- **Lifecycle:**
  - Inserted by `FormManagementService::createForm()` when QA officers create new forms via `QAFormsController@store`.
  - Updated by `FormManagementService::updateForm()` and `archiveForm()` from QA UI.
  - Read by `StudentFormController`, `StudentDashboardController`, `QAFormsController`, `QaReportingService`, exports.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| id | bigint | no | auto | system | Primary key referenced by all other tables. |
| code | string | no | — | QA input | Unique code (e.g., `COURSE_EVAL_DEFAULT`); used by controllers and QA reporting filters. |
| title | string | no | — | QA input | Display name in dashboards and QA views. |
| description | text | yes | — | QA input | Optional description shown in QA UI. |
| form_type | enum(course_feedback, system_services) | no | — | QA selection | Determines eligibility rules (course vs services). |
| is_active | boolean | no | false | system flag | Controls whether students/QA can interact with the form; toggled by archive/publish actions. |
| is_published | boolean | no | false | system flag | Ensures only published forms appear to students; set by `publishForm`. |
| is_anonymous | boolean | no | false | QA config | Reserved for future analytics; currently read by exports for anonymization. |
| estimated_minutes | integer | yes | — | QA input | Optional time estimate shown in UI. |
| version | integer | no | 1 | system | Incremented when duplicating forms; referenced in admin exports. |
| created_by | string | yes | — | session(`afm_user_id`) | QA user ID stored for audit context. |
| updated_by | string | yes | — | session | Last QA editor. |
| created_at / updated_at | timestamp | no | now() | system | Laravel timestamps for audits. |

### form_sections
- **Purpose:** Ordered groupings of questions.
- **Lifecycle:** Created/updated/deleted by `FormBuilderService` via QA Form Builder UI; read when rendering student forms or QA previews.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| form_id | bigint | no | — | FK to forms | Links section to parent form; used by queries and cascades. |
| title | string | no | — | QA input | Section heading on forms. |
| description | text | yes | — | QA input | Additional instructions. |
| order | integer | no | 0 | service logic | Determines render order; recalculated when sections reordered. |
| created_at/updated_at | timestamp | no | now() | system | Audit. |

### questions
- **Purpose:** Individual prompts students answer.
- **Lifecycle:** Mutated by `FormBuilderService` only; read by `StudentFormController`, `ResponseSubmissionService`, QA reporting.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| section_id | bigint | no | — | FK | Identifies section membership. |
| code | string | yes | — | QA optional input | Short identifier for exports. |
| prompt | text | no | — | QA input | Display text shown to students. |
| help_text | text | yes | — | QA input | Additional guidance for UI tooltips. |
| qtype | enum(likert, mcq_single, mcq_multi, text, textarea, rating, yes_no) | no | — | QA selection | Drives validation/rendering in StudentFormController/ResponseSubmissionService. |
| required | boolean | no | false | QA selection | Checked by `ResponseSubmissionService::validateSubmission`. |
| order | integer | no | 0 | service logic | Display order within section. |
| scale_min/scale_max | integer | yes | — | QA input | Likert/rating boundaries; validated at submission time. |
| scale_min_label/scale_max_label | string | yes | — | QA input | Used by UI to describe extremes. |
| allow_na | boolean | no | false | QA selection | Drives front-end to show N/A option. |
| max_length | integer | yes | — | QA input | Limits text answers. |
| staff_role_id | bigint | yes | — | QA selection | Links question to staff role for dynamic dropdowns. |
| created_at/updated_at | timestamp | no | now() | system | Audit. |

### question_options
- **Purpose:** Stores discrete answer choices for MCQ and Likert questions.
- **Lifecycle:** Managed by `FormBuilderService` methods; read when rendering forms and analyzing responses.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| question_id | bigint | no | — | FK | Links to question. |
| opt_value | string | no | — | QA input | Value stored in `response_items.option_value`. |
| opt_label | string | no | — | QA input | Label shown in UI. |
| order | integer | no | 0 | service logic | Display order. |
| is_other | boolean | no | false | QA selection | Flags option as "Other" for UI logic. |
| timestamps | timestamp | no | now() | system | Audit. |

### form_course_scope
- **Purpose:** Associates forms with courses or service scopes per term.
- **Lifecycle:**
  - Inserted by `FormManagementService::assignToSpecificCourses/assignToAllCourses/assignServiceScope`.
  - Deleted when QA removes assignments.
  - Read by `CompletionTrackingService`, `CompletionService`, `QaReportingService`.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| form_id | bigint | no | — | FK | Identifies form. |
| course_reg_no | string | yes | — | QA selection | Course key; null when scope is global services. |
| term_code | string | no | — | QA selection/config | Term (YYYYTT). |
| is_required | boolean | no | true | system default | Controls whether students must complete; read by completion tracking. |
| applies_to_services | boolean | no | false | QA selection | Distinguishes service forms. |
| timestamps | timestamp | no | now() | system | Audit. |
| unique_scope | unique index | — | — | — | Prevents duplicate assignments per term. |

### responses
- **Purpose:** One row per (form, student, course, term) evaluation attempt.
- **Lifecycle:**
  - Created by `ResponseSubmissionService::createOrResumeDraft` or `FeedbackService::startResponse`.
  - Updated by `ResponseSubmissionService::saveDraft`/`submitResponse` (status/timestamps).
  - Read by student controllers, QA reports, exports.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| id | bigint | no | auto | system | Reference for response items. |
| form_id | bigint | no | — | SSO session + controller | Identifies form answered. |
| sis_student_id | string | no | — | SSO payload | Student ID stored in session `afm_user_id`. |
| student_hash | string | no | computed | `Response::computeStudentHash` | Allows anonymized reporting. |
| course_reg_no | string | yes | — | session course selection | Null for services forms. |
| term_code | string | no | — | session term | Used for filters and completion flags. |
| status | enum(draft, submitted) | no | draft | service logic | Workflow state enforced by ResponseSubmissionService. |
| submitted_at | timestamp | yes | — | system | Set when response submitted. |
| created_at/updated_at | timestamp | no | now() | system | Audit. |
| unique_response_context | unique | — | — | — | Ensures only one response per context. |

### response_items
- **Purpose:** Normalized answers for each question.
- **Lifecycle:** Created/deleted by `ResponseSubmissionService::saveAnswer` and reloaded when editing drafts or exporting.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| response_id | bigint | no | — | FK | Parent response. |
| question_id | bigint | no | — | FK | Question answered. |
| numeric_value | decimal(8,2) | yes | — | student input | Likert/rating answers. |
| text_value | text | yes | — | student input | Free-text answers. |
| option_value | string | yes | — | student input | Selected choice for MCQ/Yes-No. |
| created_at/updated_at | timestamp | no | now() | system | Audit. |

### completion_flags
- **Purpose:** Proof-of-completion used for dashboards and QA reporting.
- **Lifecycle:**
  - Inserted by `CompletionFlag::markComplete()` (via `ResponseSubmissionService` for students or `CompletionTrackingService::markManualCompletion`).
  - Read by dashboards, QA reports, reminders.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| form_id | bigint | no | — | service call | Links to completed form. |
| sis_student_id | string | no | — | session | Identifies student. |
| course_reg_no | string | yes | — | session | Null for service forms. |
| term_code | string | no | — | session | Term context. |
| completed_at | timestamp | no | now() | system | Submission time. |
| source | enum(student, system, qa_manual) | no | student | service logic | Indicates who marked completion. |
| created_at/updated_at | timestamp | no | now() | system | Audit. |
| unique_completion | unique | — | — | — | Prevents duplicate flags. |

### notification_outbox
- **Purpose:** Outbox for reminder notifications.
- **Lifecycle:** Written by `NotificationDispatcher::queueReminder`, consumed by `NotificationDispatcher::dispatchPending`. QA controllers currently do not invoke it yet.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| channel | enum(email, sms) | no | email | system | Delivery channel. |
| recipient | string | no | — | QA input | Email/phone target. |
| subject | string | no | — | template | Email subject. |
| body | text | no | — | template | Message body referencing course/student. |
| status | enum(pending, sent, failed) | no | pending | service logic | Outbox state. |
| send_after | timestamp | no | now() | service logic | Controls schedule. |
| attempts | integer | no | 0 | service logic | Retry counter. |
| last_error | text | yes | — | system | Stores failure reason. |
| timestamps | timestamp | no | now() | system | Audit. |

## SIS Shadow Tables

### sis_students
- **Purpose:** Local cache of student metadata for reporting/exports.
- **Lifecycle:** Seeders (`SisDataSeeder`, `SimSisAfmDemoSeeder`) or future ETL jobs populate it. Read by QA exports.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| sis_student_id | string | no | — | SIS payload | Unique identifier used throughout AFM. |
| full_name | string | no | — | SIS | Display in reports. |
| email | string | yes | — | SIS | Contact channel. |
| college | string | yes | — | SIS | Used for filters. |
| department | string | yes | — | SIS | Future reminders segmentation. |
| timestamps | timestamp | no | now() | system | Audit. |

### sis_courses
- **Purpose:** Canonical list of course offerings.
- **Lifecycle:** Populated by seeders; read by QA UI for assignment drop-downs and `FormManagementService::assignToAllCourses`.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| course_reg_no | string | no | — | SIS import | Unique key representing course + term. |
| course_code | string | no | — | SIS import | Short code displayed in UI. |
| course_name | string | no | — | SIS import | Friendly display name. |
| term_code | string | no | — | SIS import | Term identifier; indexed. |
| faculty_name | string | no | — | SIS import | Used for grouping in reports. |
| timestamps | timestamp | no | now() | system | Audit metadata. |

### sis_course_ref
- **Purpose:** Lightweight course mapping used by `QaReportingService` when only registration number stored in responses.
- **Lifecycle:** Populated by seeders or imports; read when building reports/export labels.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| course_reg_no | string | no | — | SIS or seeders | Unique key; referenced when deriving course names. |
| course_code | string | no | — | SIS | Display code. |
| course_name | string | no | — | SIS | Display name. |
| dept_name | string | yes | — | SIS | Department info for reports. |
| college_name | string | yes | — | SIS | College label. |
| term_code | string | no | — | SIS | Term filter for reporting. |
| last_seen_at | timestamp | no | now() | system | Last refresh timestamp. |
| timestamps | timestamp | no | now() | system | Created/updated. |

### sis_enrollments
- **Purpose:** Snapshot of student-course relationships to validate eligibility.
- **Lifecycle:** Filled by seeders/ETL; read by `CompletionService` and QA analytics when inferring total students per course.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| sis_student_id | string | no | — | SIS | Identifies student enrollment. |
| course_reg_no | string | no | — | SIS | Course identifier. |
| term_code | string | no | — | SIS | Term for the enrollment. |
| snapshot_at | timestamp | no | now() | ETL job | When data was captured. |
| created_at/updated_at | timestamp | no | now() | system | Audit values. |

### afm_student_registry
- **Purpose:** Tracks when each student first/last visited AFM per term.
- **Lifecycle:** Updated by `StudentDashboardController@index` using `updateOrCreate` + `update` calls. Read by QA reporting to count eligible students.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| sis_student_id | string | no | — | session | Unique student identifier. |
| student_name | string | no | — | session | Cached display name. |
| term_code | string | no | — | session | Term; part of unique key. |
| courses_json | json | yes | — | session | Copy of SSO courses for audit. |
| first_seen_at | timestamp | yes | — | system | Populated when student first logs in. |
| last_seen_at | timestamp | yes | — | system | Updated each dashboard visit. |
| created_at/updated_at | timestamp | no | now() | system | Audit timestamps. |

## Staff & Alert Tables

### staff_roles
- **Purpose:** Defines categories displayed in QA staff UI and referenced by questions.
- **Lifecycle:** Seeded by `StaffRolesSeeder`; read by QA UI and question builder; updated rarely by migrations.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| role_key | string | no | — | seeder | Unique identifier used in code and question relationships. |
| label_ar | string | no | — | seeder | Arabic display label. |
| is_active | boolean | no | true | seeder/QA edits | Toggles role availability. |
| created_at/updated_at | timestamp | no | now() | system | Audit. |

### staff_members
- **Purpose:** Stores staff names per role.
- **Lifecycle:** Managed by `QAStaffController` actions; read by student forms when questions reference staff role.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| staff_role_id | bigint | no | — | QA selection | Links staff member to role. |
| name_ar | string | no | — | QA input | Display name on forms. |
| is_active | boolean | no | true | QA action | Determines if member appears in dropdowns. |
| created_at/updated_at | timestamp | no | now() | system | Audit. |

### afm_form_templates
- **Purpose:** JSON template legacy system (used by tests/spikes).
- **Lifecycle:** Seeded by `FormTemplatesSeeder`; read by `FeedbackService` for template-based responses.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| title | string | no | — | seeder | Template title for QA reference. |
| code | string | no | — | seeder | Unique identifier for template. |
| form_type | enum(course, system) | no | — | seeder | Determines expected context. |
| schema_json | longText | no | — | seeder | Serialized question structure used by legacy renderer. |
| status | enum(draft, published) | no | published | seeder | Activation flag. |
| created_at/updated_at | timestamp | no | now() | system | Audit. |

### afm_form_assignments
- **Purpose:** Links templates to scopes (course/global) per term.
- **Lifecycle:** Seeded; read by older QA flows.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| form_template_id | bigint | no | — | seeder | FK referencing template. |
| scope_type | enum(course, system) | no | — | seeder | Indicates whether scope targets course or global. |
| scope_key | string | no | — | seeder | Course code or literal `global`. |
| term_code | string | no | — | seeder | Term this assignment applies to. |
| created_at/updated_at | timestamp | no | now() | system | Audit. |

### afm_alert_runs
- **Purpose:** History of reminder executions.
- **Lifecycle:** Will be populated by future reminder jobs; currently unused.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| window_id | string | yes | — | system/job | Optional identifier grouping multiple runs. |
| triggered_by | string | yes | — | QA user | SIS/QA ID of who initiated reminders. |
| run_type | string | no | email_reminder | job config | Type of reminder executed. |
| created_at/updated_at | timestamp | no | now() | system | Audit. |

### afm_alert_recipients
- **Purpose:** Stores each student targeted during an alert run.
- **Lifecycle:** Placeholder for future job; empty in dev data.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| alert_run_id | bigint | no | — | FK | References `afm_alert_runs`. |
| student_id | string | no | — | SIS | Student to notify. |
| course_code | string | yes | — | QA/job | Optional course scope. |
| status | enum(queued, sent, failed) | no | queued | job logic | Tracks delivery state. |
| created_at/updated_at | timestamp | no | now() | system | Audit. |

## Security & Session Tables

### afm_session_tokens
- **Purpose:** Persisted SSO payload snapshots for token-based handshakes and replay protection.
- **Lifecycle:**
  - Inserted by `TokenService::createToken()` (`SsoTokenIntakeService` caller).
  - Updated by `TokenService::consumeToken()` setting `consumed_at`.
  - Read by `TokenService::getToken()` and handshake middleware.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| request_id | uuid | no | — | SIS payload | Combined with nonce for replay detection. |
| nonce | string | no | — | SIS payload | Unique per token. |
| payload_hash | string | no | — | computed | Hash of canonical payload stored for audits. |
| sis_student_id | string | no | — | SIS payload | Student or QA identifier. |
| student_name | string | yes | — | SIS payload | Display name from JSON (student only). |
| courses_json | json | no | [] | SIS payload | Course array used to hydrate session after handshake. |
| role | enum(student, qa, qa_officer, department_head, admin) | no | — | SIS payload | Role used in middleware. |
| issued_at | timestamp | no | — | SIS payload | Validity start. |
| expires_at | timestamp | no | — | SIS payload | Validity end. |
| consumed_at | timestamp | yes | — | system | Set when token used. |
| client_ip | string | yes | — | request | IP address stored for auditing. |
| user_agent | text | yes | — | request | Browser fingerprint. |
| created_at/updated_at | timestamp | no | now() | system | Audit. |
| indexes | idx | — | — | system | `unique(request_id)`, `unique(request_id,nonce)`, indexes on `sis_student_id` and `expires_at`. |

### audit_logs
- **Purpose:** Immutable event log.
- **Lifecycle:** Written by `AuditLogger` and `BaseService::logAudit`; read by `AdminAuditController` and security reviewers.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| event_type | string | no | — | service call | e.g., `sso_validated`, `response`. |
| actor_role | string | yes | — | service call | Role (student, qa_officer, system). |
| actor_id | string | yes | — | service call | SIS ID or user ID. |
| target_type | string | yes | — | service call | Model affected (Response, CompletionFlag). |
| target_id | string | yes | — | service call | ID of target model. |
| action | string | no | — | service call | Verb describing action (submit, publish). |
| metadata | json | yes | — | service call | Additional structured data. |
| ip_address | string | yes | — | request | IP at time of event. |
| user_agent | text | yes | — | request | Browser info. |
| created_at | timestamp | no | now() | system | Logged time; no `updated_at`. |

### exemptions
- **Purpose:** Planned list of students exempt from surveys.
- **Lifecycle:** Not yet used; controllers will insert when manual exemptions added.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| sis_student_id | string | no | — | QA admin | Student receiving exemption. |
| course_reg_no | string | yes | — | QA admin | Course scope; null for term-wide exemption. |
| term_code | string | no | — | QA admin | Term the exemption applies to. |
| reason | text | no | — | QA admin | Explanation stored for audits. |
| created_by | string | no | — | QA admin | Staff ID adding exemption. |
| created_at/updated_at | timestamp | no | now() | system | Audit. |

## Laravel Infrastructure Tables
These tables follow Laravel defaults; columns are included for completeness.

### users
- **Lifecycle:** Inserted by `UserSeeder` or manual registration; read by Laravel auth and admin routes.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| id | bigint | no | auto | system | Primary key. |
| name | string | no | — | admin input | Display name. |
| email | string | no | unique | admin input | Login credential. |
| email_verified_at | timestamp | yes | — | system | Laravel email verification. |
| password | string | no | — | hashed input | Bcrypt hash. |
| sis_student_id | string | yes | — | optional | Links Laravel user to SIS ID. |
| role | enum(student, qa_officer, department_head, admin) | no | student | admin input | Authorization gates. |
| remember_token | string | yes | — | system | Persistent login token. |
| created_at/updated_at | timestamp | no | now() | system | Audit. |

### password_reset_tokens
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| email | string | no | — | user input | Primary key identifying account. |
| token | string | no | — | system | Reset token hash. |
| created_at | timestamp | yes | — | system | When token issued. |

### sessions
- **Purpose:** Database-backed HTTP session storage when `SESSION_DRIVER=database` is configured.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| id | string | no | — | system | Session key. |
| user_id | bigint | yes | — | Laravel auth | References `users.id` when logged in. |
| ip_address | string | yes | — | request | Client IP for security monitoring. |
| user_agent | text | yes | — | request | Browser fingerprint. |
| payload | longText | no | — | system | Serialized session array. |
| last_activity | integer | no | — | system | Unix timestamp for expiration; indexed. |

### cache
- **Purpose:** Stores cached values when `CACHE_DRIVER=database` is configured.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| key | string | no | — | framework | Cache identifier (primary key). |
| value | mediumText | no | — | framework | Serialized cached value. |
| expiration | integer | no | — | framework | Unix timestamp for expiry. |

### cache_locks
- **Purpose:** Stores lock ownership when using database cache locks.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| key | string | no | — | framework | Lock identifier (primary key). |
| owner | string | no | — | framework | Lock owner token. |
| expiration | integer | no | — | framework | Unix timestamp for lock expiry. |

### jobs
- **Purpose:** Stores queued jobs when using `QUEUE_CONNECTION=database`.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| id | bigint | no | auto | system | Primary key. |
| queue | string | no | — | framework | Queue name. |
| payload | longText | no | — | framework | Serialized job data. |
| attempts | unsignedTinyInteger | no | 0 | framework | Retry counter. |
| reserved_at | unsignedInteger | yes | — | framework | Timestamp when worker reserved job. |
| available_at | unsignedInteger | no | — | framework | When job becomes available. |
| created_at | unsignedInteger | no | — | framework | Creation timestamp. |

### job_batches
- **Purpose:** Tracks batch metadata for queued jobs.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| id | string | no | — | framework | Batch identifier. |
| name | string | no | — | framework | Human-readable label. |
| total_jobs | integer | no | — | framework | Number of jobs scheduled. |
| pending_jobs | integer | no | — | framework | Jobs remaining. |
| failed_jobs | integer | no | — | framework | Jobs failed so far. |
| failed_job_ids | longText | no | — | framework | Serialized list of failing job IDs. |
| options | mediumText | yes | — | framework | Batch options JSON. |
| cancelled_at | integer | yes | — | framework | Timestamp when cancelled. |
| created_at | integer | no | — | framework | Creation timestamp. |
| finished_at | integer | yes | — | framework | Completion timestamp. |

### failed_jobs
- **Purpose:** Captures jobs that exhausted retries.
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| id | bigint | no | auto | system | Primary key. |
| uuid | string | no | unique | framework | Identifier referenced in logs. |
| connection | text | no | — | framework | Queue connection used. |
| queue | text | no | — | framework | Queue name. |
| payload | longText | no | — | framework | Serialized job and context. |
| exception | longText | no | — | framework | Stack trace. |
| failed_at | timestamp | no | now() | system | Failure timestamp. |

### personal_access_tokens
- **Purpose:** Laravel Sanctum token storage (currently unused by AFM).
- **Columns:**
| Column | Type | Null | Default | Source | Usage |
| --- | --- | --- | --- | --- | --- |
| id | bigint | no | auto | system | Primary key. |
| tokenable_type | string | no | — | framework | Morph class name. |
| tokenable_id | bigint | no | — | framework | Morph ID referencing owner. |
| name | string | no | — | developer input | Token label. |
| token | string(64) | no | unique | framework | Hashed token. |
| abilities | text | yes | — | developer input | JSON array of abilities. |
| last_used_at | timestamp | yes | — | system | Timestamp of last usage. |
| expires_at | timestamp | yes | — | system | Optional expiry. |
| created_at/updated_at | timestamp | no | now() | system | Audit. |
