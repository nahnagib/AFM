# Reports Module

Reports live under `/qa/reports/*` and are powered by `QAReportsController` and `QAResponsesReportController`. Every report inherits the light-mode Tailwind palette and uses the new three-tab navigation (Course-Level, Student-Level, Responses Level).

## Course-Level Report (`qa.reports.completion`)
- **Route:** `GET /qa/reports/completion`
- **Controller Action:** `QAReportsController@completionReport`
- **Filters:**
  - `term` (default `QaReportingService::getCurrentTerm()`)
  - `course` (course_reg_no substring)
  - `form_type` (`all`, `course_feedback`, `system_services`)
  - `status` (`all`, `Completed`, `Not Completed`)
- **Data source:** `QaReportingService::getCompletionReport()` uses inferred enrollments from `CompletionFlag` + `Response` data.
- **Exports:** Append `?export=xlsx|csv|pdf`. Uses `CompletionReportExport` (Maatwebsite Excel + DomPDF). `AuditLogger::logExportGenerated()` records each download.
- **Table Columns:** Course code, name, department placeholder, enrolled count, completed count, completion rate (with color-coded bar).

## Student-Level Report (`qa.reports.students`)
- **Route:** `GET /qa/reports/students`
- **Controller Action:** `QAReportsController@studentReport`
- **Filters:** `term`, `course`, `student_id`, `form_type`, `status`.
- **Data source:** `QaReportingService::getStudentReport()` cross-references enrollments with completion flags.
- **Exports:** `export=xlsx|csv|pdf` via `StudentReportExport`.
- **Table Columns:** Student ID, student name (placeholder names derived from enrollment map), course code, course name, status badge (Completed/Not Completed).

## Non-Completers Drilldown (`qa.reports.non_completers`)
- **Route:** `GET /qa/reports/non-completers`
- **Controller Action:** `QAReportsController@nonCompleters`
- **Filters:** `term` + **required** `course` query.
- **Data source:** `QaReportingService::getNonCompleters()` returns a `Collection` of students missing completion flags for that course.
- **UI:** Table lists SIS ID, placeholder email, department (N/A currently), and includes a hidden input per row so reminder workflows can target specific students in the future.

## Responses Level (`qa.reports.responses`)
- **Route:** `GET /qa/reports/responses`
- **Controller Action:** `QAResponsesReportController@index`
- **Filters:**
  - `term` (defaults to `202510`)
  - `form_id` (required to view data; list limited to default course/service forms)
  - `course_reg_no` (optional filter)
- **Logic:** Joins `response_items` → `responses` → `questions` to produce a denormalized view per answer with metadata (student_id, course, section title, question prompt, answer, submission time).
- **Exports:**
  - `export=excel` → `.xlsx`
  - `export=csv` → CSV via Maatwebsite Excel
  - `export=pdf` → Landscape PDF using `qa.reports.responses_export` Blade view feeding DomPDF.
- **Empty States:**
  - If `form_id` missing → “Select filters above” message.
  - If filters return no rows → Yellow warning card.

## Response Analysis (`qa.reports.analysis/{formId}`)
- **Route:** `GET /qa/reports/analysis/{formId}`
- **Controller Action:** `QAReportsController@responseAnalysis`
- **Filters:** `course` (course_reg_no).
- **Data source:** `QaReportingService::getResponseSummary()` groups answers by question.
- **Output:** For likert/rating questions it shows averages and distribution counts; MCQ/Yes-No show counts per option. Text questions are not summarized yet.

## Navigation Tabs
Every report view now renders the same tabset:
1. **Course-Level Report** → `/qa/reports/completion`
2. **Student-Level Report** → `/qa/reports/students`
3. **Responses Level** → `/qa/reports/responses`

Active tab detection relies on `request()->routeIs('qa.reports.*')` checks so QA can quickly switch between contexts while retaining current query params when possible.

