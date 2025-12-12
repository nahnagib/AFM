# System Development Lifecycle (SDLC)

## 1. Methodology
The development of AFM followed an **Agile-Iterative** methodology, specifically adapting the **Iterative Prototyping** model. This approach was chosen to allow for continuous feedback from stakeholders (QA Department) and strictly defined integration points with the SIS.

### 1.1 Phases
1.  **Requirements Elicitation:**
    - Analysis of existing paper forms.
    - Definition of SIS integration contract (JSON payload structure).
2.  **Architecture Design:**
    - Selection of Laravel for rapid MVC development.
    - Database schema modeling (ERD).
3.  **Prototyping (Iteration 1):**
    - Built the "Dev Simulator" to mock SIS payloads.
    - Implemented basic `SsoHandshakeController`.
4.  **Core Development (Iteration 2):**
    - `StudentDashboard` and `FeedbackController`.
    - `Response` atomic submission logic.
5.  **Refinement (Iteration 3):**
    - QA Dashboard and Reporting.
    - Introduction of `FormBuilder` for dynamic questions.
6.  **Testing & Validation:**
    - Unit Tests (`tests/Unit`).
    - Feature Tests (`tests/Feature`).
    - User Acceptance Testing (UAT) with synthetic data.

## 2. Evolution of the Prototype
The system evolved from a hardcoded single-form prototype to a dynamic multi-template engine.
- **v0.1:** Hardcoded HTML forms.
- **v0.5:** Database-driven questions but static logic.
- **v1.0:** Fully dynamic `afm_form_templates` with strict eligibility scopes.

## 3. Quality Assurance in Development
- **Version Control:** Git features branches (feature/sso, feature/qa-dashboard).
- **Migration Management:** Strict immutable migrations for database schema changes.
- **Seeding:** Robust seeders (`DatabaseSeeder`, `SimSisAfmDemoSeeder`) to ensure reproducible test environments.
