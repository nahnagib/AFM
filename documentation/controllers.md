# Controllers Documentation

## 1. SSO & Authentication Controllers

### 1.1 SsoJsonIntakeController
**Purpose:** Serves as the primary entry point for the Single Sign-On handshake from the SIS. It accepts the JSON payload, orchestrates validation, and hydrates the session.
- **Route:** `POST /sso/json-intake`
- **Service Dependency:** `SsoJsonIntakeService`
- **Inputs:** JSON Payload (containing `iss`, `aud`, `timestamp`, `signature`, `role`, `user_data`).
- **Outputs:** HTTP 302 Redirect to `/student/dashboard`, `/qa`, or `/admin`.
- **Validation:**
    - Checks for JSON validity.
    - Delegates HMAC and timestamp verification to service.
- **Error Handling:** Catches Exceptions and redirects to Landing Page with error flash message.

### 1.2 Access Control & Middleware
While not a single controller, the **SSO Pipeline** relies on Middleware (`EnsureAfmStudentRole`, `EnsureAfmQaRole`) to guard routes.
- **SsoTokenController:** (Note: In this implementation, token management is handled by `TokenService` called within `SsoJsonIntakeController`, rather than a standalone controller).

## 2. Student Module Controllers

### 2.1 StudentDashboardController
**Purpose:** Renders the student's personalized dashboard.
- **Public Methods:** `index(Request $request)`
- **Logic:**
    - Retrieves `afm_courses` from Session.
    - Loads default active forms (`COURSE_EVAL_DEFAULT`, `SERVICES_EVAL_DEFAULT`).
    - Queries `Response` model to separate forms into `Pending` and `Completed` buckets.
    - Updates `AfmStudentRegistry` with "last seen" timestamp.

### 2.2 StudentFormController
**Purpose:** Handles the display of a specific feedback form.
- **Public Methods:** `show(Request $request, $formId)`
- **Route Bindings:** `{formId}` (Form Model ID).
- **Validation:**
    - Checks Form existence and `is_published`.
    - **Eligibility:** Verifies the student is enrolled in the course (via Session context) or if the form is a global service evaluation.
    - **Completion:** Redirects if `CompletionTrackingService` reports the form is already done.
- **Inputs:** `formId` (route), `course_reg_no` (query param).

### 2.3 StudentSubmissionController
**Purpose:** Processes the POST submission of feedback.
- **Public Methods:** `submit(Request $request, $responseId)`, `saveDraft(...)` (future).
- **Logic:**
    - Delegates transaction management to `ResponseSubmissionService`.
    - Writes answers to `response_items`.
    - Updates `responses` status to `submitted`.
    - Triggers `AuditLogger`.

## 3. QA Module Controllers

### 3.1 QAFormsController
**Purpose:** Manages the lifecycle of feedback form templates.
- **Public Methods:** `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `publish`.
- **Validation:**
    - `code`: Unique, required.
    - `type`: Must be `course_feedback` or `system_services`.
- **Authorization:** Protected by `EnsureAfmQaRole`.

### 3.2 QAReportsController
**Purpose:** Generates analytical reports for QA Officers.
- **Public Methods:**
    - `completionReport(Request $request)`: Aggregates completion rates by course.
    - `studentReport(...)`: Detailed view of a specific student's activity.
    - `nonCompleters(...)`: Lists students who haven't submitted feedback for a course.
    - `responseAnalysis(...)`: Likert analysis (Mean, Mode) for a form.
- **Exporting:** Supports `?export=xlsx` or `?export=pdf` via `Maatwebsite\Excel`.

### 3.3 QARemindersController
**Purpose:** Handles sending email reminders to non-completers.
- **Current State:** Placeholder/Stub (Returns "Coming Soon").
- **Intended Logic:** Will interface with `NotificationDispatcher` to queue emails.
