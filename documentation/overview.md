# System Overview & Architecture

## 1. Introduction
The Academic Feedback Module (AFM) is a specialized feedback collection and quality assurance system designed to bridge the gap between Student Information Systems (SIS) and academic quality improvement cycles. It provides a secure, token-based Single Sign-On (SSO) mechanism for students to submit course and service evaluations, while empowering Quality Assurance (QA) officers with real-time dashboards and reporting tools.

### 1.1 Purpose
The primary purpose of AFM is to:
- **Digitize Feedback Collection:** Replace manual paper-based or disjointed survey tools with a centralized, integrated digital platform.
- **Ensure Data Integrity:** Guarantee that feedback is authentic (via SIS verification) but anonymous (to protect student privacy).
- **Streamline QA Processes:** Automate the aggregation of response data into actionable insights for university administrators.

### 1.2 Objectives
- **Seamless Integration:** Zero-login barriers for users coming from the main SIS portal.
- **High Availability:** scalable architecture capable of handling peak submission loads during end-of-term periods.
- **Security & Privacy:** Robust separation of concerns between user identity and feedback content.

## 2. System Architecture

### 2.1 Architectural Style
AFM follows a **Modular Monolith** architecture built on the **Laravel** framework. It strictly adheres to the **Model-View-Controller (MVC)** pattern but extends it with a Service Layer to encapsulate complex business logic, ensuring controllers remain thin and focused on HTTP handling.

### 2.2 Functional Modules
The system is divided into distinct functional boundaries:
1.  **Student Feedback Module:** Handles token validation, form rendering, and response submission.
2.  **QA Administration Module:** Manages form templates, reporting, and staff evaluation configurations.
3.  **SSO & Security Module:** Manages the handshake with the parent SIS, token decryption, and session policing.
4.  **Reporting Engine:** Aggregates raw response data into statistical summaries (participation rates, Likert averages).

### 2.3 C4 Architecture Summary

#### Level 1: Context
AFM sits within the university's IT ecosystem. It relies on the **SIS (Student Information System)** for user identity and course enrollment data. It provides data to **University Management** for decision-making.

#### Level 2: Container
- **Web Application:** Laravel 11 application serving HTML (Blade) and JSON APIs.
- **Database:** MySQL relational database storing forms, verified responses, and audit logs.
- **Cache Store:** Redis/File-based cache for managing ephemeral SSO nonces and session states.

#### Level 3: Component (Key Components)
- **SSO Controller:** Entry point for encrypted payloads.
- **Feedback Service:** Orchestrates the validation of a student's eligibility to rate a specific course.
- **Form Builder:** Dynamic engine that renders questions (Likert, text) based on active templates.
- **Audit Logger:** Background service recording critical system events for security compliance.

## 3. System Lifecycle

### 3.1 Data Flow Lifecycle
The lifecycle of a single feedback session flows as follows:

1.  **Initiation (SIS Side):**
    - A student clicks "Evaluate Courses" in the SIS portal.
    - SIS generates a JSON payload containing student ID, metadata, and enrolled courses.
    - Payload is encrypted and sent to AFM's `/sso/handshake` endpoint.

2.  **Handshake & Verification (AFM Side):**
    - `SsoHandshakeController` receives the token.
    - `JsonPayloadVerifier` validates the digital signature and integrity.
    - `TokenService` issues a short-lived AFM session token.

3.  **Feedback Submission:**
    - Student accesses the dashboard, seeing a list of pending evaluations.
    - `FeedbackService` serves the active form template.
    - Submission triggers `ResponseSubmissionService` to enforce atomic updates: saving answers and marking the enrollment as "completed".

4.  **Reporting & Feedback Loop:**
    - QA Officer logs in via a separate secure route.
    - `QaReportingService` aggregates new data immediately.
    - Reports are generated and exported for academic department headers.

## 4. Integration Principles
- **Loose Coupling:** AFM does not write to the SIS database. It treats the SIS as a read-only source of truth via the passed JSON payloads or read-replicas (simulated).
- **Stateless verification:** Each request carries necessary context, ensuring the system can scale horizontally without sticky sessions blocking load balancing.
