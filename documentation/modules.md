# Module-by-Module Functional Documentation

## 1. Student Module
The interface for end-users (students) to submit feedback.

### 1.1 Components
- **Dashboard:** Cards-based UI separating "To Do" forms from "Completed" history.
- **Form Player:** A dynamic form renderer that supports:
    - Likert Scale (1-5)
    - Open Text
    - Single Select
    - Staff Selection (for multi-instructor courses)
- **Submission Engine:** Handles atomic saves and prevents double-submission.

### 1.2 Policies
- **Eligibility:** A student sees a form ONLY if they are enrolled in the linked course (verified via SIS payload) or if it is a global "System" form.
- **One-Shot:** Once submitted, a form cannot be edited (unless reset by Admin).

## 2. QA Module
The command center for Quality Assurance officers.

### 2.1 Components
- **Form Builder:** Drag-and-drop or step-based interface to create new survey templates.
- **Reports Engine:** Real-time aggregation of submission data.
- **Reminder System:** Tool to trigger email blasts to non-responsive students.
- **Staff Manager:** CRUD interface for managing the list of Professors/TAs available in questions.

### 2.2 Internal Workflow
1.  **Draft:** QA Officer creates a form.
2.  **Publish:** Form is locked and made visible to eligible students.
3.  **Monitor:** Officer watches the "Participation Rate" gauge on the dashboard.
4.  **Close/Archive:** At term end, forms are archived (read-only).

## 3. Reporting Module
A sub-system of the QA Module focused on output.

### 3.1 Outputs
- **Completion Report:** Table showing % completion per course.
- **Analysis Report:** Statistical breakdown (Mean, Mode, SD) of answers.
- **Export Formats:** Excel (.xlsx), PDF.

## 4. Admin Module
System-level configuration.

### 4.1 Features
- **Audit Log Viewer:** Full history of system actions.
- **Configuration:** Update Term Code, Toggle System Maintenance Mode.
- **User Management:** Assign QA/Admin roles to staff accounts.

## 5. Notification Module
*(Planned / Partially Implemented)*
- **Triggers:**
    - "Evaluation Period Open" (Blast).
    - "Reminder: 24 Hours Left" (Targeted to non-completers).
- **Channel:** Email (via SMTP/Mailgun).
