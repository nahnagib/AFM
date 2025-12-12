# Technical Stack & Environment

## 1. Core Frameworks & Languages

### Backend
- **Framework:** Laravel 11.x
- **Language:** PHP 8.2+
- **Architecture:** MVC with Service Layer pattern

### Frontend
- **Templating Engine:** Laravel Blade
- **CSS Framework:** Tailwind CSS 3.4
- **JavaScript:** Vanilla JS + Alpine.js (minimal interactivity)
- **Build Tool:** Vite

### Database & Storage
- **Primary Database:** MySQL 8.0 (compatible with SQLite for testing/dev)
- **Caching Driver:** Redis (Production) / File (Development)
- **Session Driver:** Database (for persistence and security)

## 2. Development Environment (Laravel Herd / Standard)
The development environment assumes a standard LAMP/LEMP stack or Laravel Herd on macOS.

### Key Tools
- **IDE:** VS Code (recommended with PHP Intelephense)
- **Version Control:** Git
- **API Testing:** Postman / Thunder Client (for SSO simulation)
- **Local Server:** Laravel Herd or `php artisan serve`

### Runtime Configuration
The application relies on `dotenv` (.env) configuration. Critical keys include:
- `APP_KEY`: 32-character encryption key for session security.
- `AFM_SSO_SHARED_SECRET`: Shared secret for decrypting SIS payloads.
- `DB_CONNECTION`: Database driver selector.

## 3. Constraints & Assumptions

### System Constraints
1.  **Single Active Term:** The system is designed to handle feedback for one active academic term at a time. Multi-term historical analysis is handled by data warehousing (out of scope for this operational module).
2.  **Synchronous Processing:** Most actions are synchronous to ensure immediate consistency for the user, though email notifications are dispatched via queues.

### Assumptions
- **SIS Availability:** It is assumed the SIS can generate valid JSON payloads conforming to the AFM schema.
- **Network Security:** AFM operates behind the university firewall or via HTTPS termination at the load balancer level.
- **Browser Support:** Targeted for modern evergreen browsers (Chrome, Edge, Safari, Firefox). Legacy IE support is not required.

## 4. Third-Party Packages
Analysis of `composer.json` reveals the following key dependencies:
- `laravel/sanctum`: For API token management (if expanding to mobile apps).
- `maatwebsite/excel`: Potentially used for exporting tabular reports.
- `barryvdh/laravel-dompdf`: For PDF generation of feedback summaries.
