# Database Documentation

## 1. Overview & ERD Narrative
The AFM database schema is designed to work as a satellite to the main SIS. It maintains its own local copy of necessary context (courses, students) to allow for high-performance joins and historical integrity, even if the SIS data changes.

The core relationship centers on the **Response** entity, which links a **Student** (via `sis_student_id`) to a **Form Template** (via `form_template_id`) within the context of a specific **Course** (`course_reg_no`) and **Term** (`term_code`).

## 2. Core Transactional Tables

### 2.1 `responses`
The primary table for storing feedback submissions.
- **Primary Key:** `id` (BigInt)
- **Foreign Keys:**
    - `form_template_id` -> `afm_form_templates.id`
    - `form_id` -> `forms.id` (Legacy/Bridge column)
- **Key Columns:**
    - `sis_student_id` (String): Indexed reference to the student.
    - `course_reg_no` (String): The course context.
    - `term_code` (String): e.g., "202410".
    - `status` (Enum): `not_started`, `in_progress`, `completed`, `submitted`.
    - `response_json` (JSON): Stores the raw key-value pairs of answers (NoSQL-style for flexibility).
    - `student_hash` (String): SHA-256 hash for verification.
- **Constraints:**
    - Unique Index: `[form_id, course_reg_no, term_code, sis_student_id]` prevents double submission.

### 2.2 `response_items`
Optional normalized table for analytical querying of specific answers.
- **Columns:** `response_id`, `question_id`, `option_value`, `text_value`.
- **Purpose:** Allows SQL-based aggregation of Likert scores without parsing JSON.

### 2.3 `audit_logs`
Immutable record of system events.
- **Columns:** `actor_type`, `actor_id`, `event_type`, `meta_json`, `ip_address`.

## 3. SIS Reference Tables (Shadow Copy)
These tables are populated/synced from SIS payloads or nightly jobs.

### 3.1 `sis_students`
- **Columns:** `sis_student_id` (Unique), `full_name`, `email`, `college`.

### 3.2 `sis_course_ref`
- **Columns:** `course_reg_no` (Unique), `course_code`, `course_name`.
- **Purpose:** Provides human-readable names for the dashboard.

### 3.3 `sis_enrollments`
- **Columns:** `sis_student_id`, `course_reg_no`, `term_code`.
- **Purpose:** Defines the set of valid student-course pairs for eligibility checks.

## 4. Configuration Tables

### 4.1 `forms` / `afm_form_templates`
Definitions of the surveys.
- **Columns:** `title`, `description`, `is_active`, `type` (course vs service).

### 4.2 `staff_members`
List of academic staff for specific evaluation.
- **Columns:** `staff_id`, `name_ar`, `role_id`.

## 5. Schema Alignment
| SIS Entity | AFM Table Column | Notes |
| :--- | :--- | :--- |
| Student ID | `sis_student_id` | String type to support leading zeros. |
| Course Registration No | `course_reg_no` | formatting `CODE-SECTION-TERM`. |
| Term Code | `term_code` | `YYYYTT` format. |
