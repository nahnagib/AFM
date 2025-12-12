# AFM Documentation Index

This `/docs` directory is the handover reference for the Academic Feedback Management (AFM) platform. Every file describes one slice of the system so a new developer can reason about the product without reading the whole codebase first.

## How to Navigate

| File | Why you need it |
| --- | --- |
| `architecture.md` | C4-style context/container overview and module boundaries. Start here to understand how AFM fits between SIS, Redis, and the Laravel app. |
| `setup.md` | Local environment instructions, required services (MySQL/Redis/Node), and the commands we run daily. |
| `sso.md` | JSON SSO contract, HMAC verification rules, and how tokens/sessions move through the system. |
| `database.md` | Full schema reference – every table, column, constraint, relationship, and retention note. |
| `routes.md` | Route map grouped by modules plus middleware so you can trace a request quickly. |
| `controllers.md` | Function-level breakdown of each controller method, including inputs, validations, DB access, and responses. |
| `services.md` | Function-level documentation for service classes (ResponseSubmissionService, TokenService, etc.). |
| `middleware.md` | Role/SSO middleware behaviors and redirect logic. |
| `repositories.md` | Persistence helpers used by services and jobs. |
| `reports.md` | What each QA report does, filters it exposes, and export behaviors. |
| `testing.md` | How to prep the database for tests plus the manual and automated scenario list. |
| `security.md` | HMAC, TTL, replay protection, role enforcement, and auditing practices. |
| `deployment.md` | Render/local deployment notes, migration order, and operational runbook. |
| `DEMO_DATA_SETUP.md` | (Legacy) quick instructions for populating demo data – still useful when onboarding QA. |

Each document references the real code (controllers in `app/Http/Controllers`, services in `app/Services`, migrations in `database/migrations`, etc.). Keep these synchronized after every code change.
