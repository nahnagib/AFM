# Glossary & Definitions

## 1. Domain Terms
- **AFM (Academic Feedback Module):** The name of this software system.
- **SIS (Student Information System):** The parent university system (e.g., SIM-SIS) that holds the master records of students and courses.
- **Course Registration Number (course_reg_no):** A unique identifier for a specific course offering in a specific term (e.g., `CS101-202410-A`).
- **Term Code:** A 6-digit integer representing the academic term (Format: `YYYYTT`, e.g., `202410` for Spring 2024).
- **QA Officer:** An administrative user responsible for managing feedback cycles and viewing reports.

## 2. Technical Terms
- **SSO (Single Sign-On):** Authentication process where a user logs in once (Student Portal) and gains access to AFM without re-entering credentials.
- **Payload:** The JSON data packet sent from SIS to AFM containing user identity and context.
- **Handshake:** The secure exchange sequence (`POST /sso/json-intake`) establishing trust between SIS and AFM.
- **HMAC (Hash-based Message Authentication Code):** The cryptographic method used to sign the SSO payload, ensuring it hasn't been tampered with in transit.
- **Nonce:** "Number used once". A random string in the payload to prevent Replay Attacks.
- **Migration:** A Laravel file that programmatically alters the database schema (e.g., creating a table).
- **Seeder:** A script that populates the database with initial or test data.
- **Blade:** The templating engine used by Laravel to generate HTML views.

## 3. AFM Specifics
- **Form Template:** A master definition of a survey (Questions + Valid Options).
- **Response:** A single student's submission for a single course.
- **Completion Flag:** A lightweight boolean marker used to quickly check if a Student has finished a specific Form.
