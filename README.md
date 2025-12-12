# Academic Feedback Module (AFM) – Prototype

## Overview
This is a **High-Fidelity Local Prototype** of the Academic Feedback Module (AFM).  
**Purpose:** To demonstrate a secure, integrated feedback system where students are authenticated via a JSON Single Sign-On (SSO) payload from the Student Information System (SIS).

> [!IMPORTANT]
> **LOCAL USE ONLY**: This codebase is a proof-of-concept for a graduation thesis. It is **not** intended for production deployment without further security hardening and infrastructure setup.

## Core Features
- **JSON SSO Integration**: Authentication via HMAC-SHA256 signed payloads simulated securely locally.
- **Session Management**: Short-lived sessions managed via Redis/Database.
- **Role-Based Access**: distinct flows for Students (Feedback submission) and QA Officers (Reporting).
- **Feedback Engine**: Dynamic forms, course eligibility checks, and completion tracking.

## Prerequisites
- **PHP**: 8.2 or higher
- **Composer**: Dependency Manager
- **MySQL**: Local Database (or SQLite for quick testing)
- **Redis**: For session caching (Recommended)

## Installation (Local)

1. **Clone the Repository**
   ```bash
   git clone <repo-url>
   cd AFM_Project
   ```

2. **Configure Environment**
   Copy the example environment file:
   ```bash
   cp .env.example .env
   ```
   Edit `.env` to match your local database credentials:
   ```ini
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_DATABASE=afm_prototype
   # ...
   AFM_SSO_SHARED_SECRET=DEV_SHARED_SECRET_KEY_123456789
   ```

3. **Install Dependencies**
   ```bash
   composer install
   npm install && npm run build
   ```

4. **Setup Database**
   ```bash
   php artisan key:generate
   php artisan migrate --seed
   ```
   *Note: This will seed demo forms and staff members.*

5. **Run the Application**
   ```bash
   php artisan serve
   ```
   Access the app at: `http://127.0.0.1:8000`

## Using the Simulator
Since there is no live connection to a real SIS, this prototype includes a **Dev Simulator**.

1. Navigate to `http://127.0.0.1:8000/dev/simulator`.
2. You will see a list of Pre-configured Users (Student: Nahla, Ali, etc. + QA Officer).
3. Click **"Login as [User]"**.
   - The Simulator checks `app/Http/Controllers/DevSimulatorController.php`.
   - It generates a payload, sorts keys (canonicalization), and signs it using `AFM_SSO_SHARED_SECRET`.
   - It posts this to `/sso/json-intake`.
4. If the signature matches, you are logged in and redirected to the appropriate dashboard.

## Technical Details (For Reviewers)
### HMAC Verification Flow
1. **Intake**: AFM receives a JSON payload.
2. **Canonicalization**: The `signature` field is removed. Keys are sorted lexicographically. Compact JSON string is generated.
3. **Hashing**: `hash_hmac('sha256', canonical_string, secret)` is computed.
4. **Compare**: The computed hash is compared to the incoming `signature`.

See `App\Services\JsonPayloadVerifier` and `App\Support\AfmJsonCanonicalizer` for the implementation.
