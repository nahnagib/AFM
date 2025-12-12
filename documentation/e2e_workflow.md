# End-to-End Workflow Document

## 1. Introduction
This narrative walks through the complete lifecycle of the AFM system, from the moment a developer initializes the environment to the final export of a semester's reports.

## 2. Initialization & Setup
1.  **Environment Boot:**
    - The server starts (Nginx/PHP-FPM).
    - `env` variables are loaded (`AFM_SSO_SHARED_SECRET`, `DB_CONNECTION`).
2.  **Database Seeding:**
    - `php artisan db:seed` populates `forms` with the term's active templates (`COURSE_EVAL_SPRING25`).
    - `staff_members` are imported for the Staff Evaluation feature.

## 3. The Student Experience (Runtime)
1.  **Trigger:** Student logs into the University Portal (SIS).
2.  **Handshake:** Student clicks "Evaluate". SIS generates a signed JSON ID card.
3.  **Entry:** AFM `SsoJsonIntakeService` validates the ID card.
    - *Success:* A session is created.
    - *Fail:* Security audit log records the rejection.
4.  **Dashboarding:** Student sees 5 form cards: 4 for their enrolled courses, 1 for general services.
5.  **Submission:**
    - Student opens "Software Engineering 1".
    - Fills out Likert scale questions.
    - Selects "Dr. John Doe" from the professor dropdown.
    - Clicks Submit.
6.  **Completion:** The card moves to "History". `responses` table row is locked.

## 4. The QA Officer Experience (Runtime)
1.  **Monitoring:** QA Officer watches the dashboard as the participation counter ticks up from 0% to 65%.
2.  **Intervention:** Participation is low for "Computer Science" dept. Officer sends a "Reminder Email" blast via AFM.
3.  **Analysis:**
    - Term ends. Officer goes to "Reports".
    - Selects "Course Analysis Report".
    - Downloads PDF.
    - The report shows "Instructor Clarity" scored 4.2/5.0 average.

## 5. System Cleanup
1.  **Archival:** Admin runs a job to mark the Term's forms as "Archived".
2.  **Rotation:** `AFM_SSO_SHARED_SECRET` is rotated for the next semester.
