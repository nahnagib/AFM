# Routes Documentation

## 1. Route Groups Overview
The application separates routes into functional domains using Prefix Grouping and Middleware.

### 1.1 Web Routes (`routes/web.php`)
- **Public:** Landing page (`/afm`), Dev Simulator tools.
- **SSO:** Handshake endpoints.
- **Student:** `/student/*` (Protected by `afm.student`).
- **QA:** `/qa/*` (Protected by `afm.qa`).
- **Admin:** `/admin/*` (Protected by `afm.admin`).

### 1.2 API Routes (`routes/api.php`)
- **SIS Integration:** `/sis/*` (Protected by Sanctum tokens).

## 2. Detailed Route Inventory

### 2.1 Single Sign-On (SSO)
| Method | URI | Controller | Name | Description |
| :--- | :--- | :--- | :--- | :--- |
| POST | `/sso/json-intake` | `SsoJsonIntakeController@store` | `sso.json_intake` | Main entry point for SIS payloads. |
| GET | `/sso/intake` | `SsoHandshakeController@intake` | `sso.intake` | Legacy entry. |

### 2.2 Student Enclave
**Middleware:** `auth`, `afm.student`
| Method | URI | Controller | Name |
| :--- | :--- | :--- | :--- |
| GET | `/student/dashboard` | `StudentDashboardController@index` | `student.dashboard` |
| GET | `/student/form/{formId}` | `StudentFormController@show` | `student.form.show` |
| POST | `/student/response/{id}/submit`| `StudentSubmissionController@submit`| `student.response.submit`|

### 2.3 QA Administration
**Middleware:** `auth`, `afm.qa`
| Method | URI | Controller | Description |
| :--- | :--- | :--- | :--- |
| GET | `/qa` | `QAOverviewController@index` | Main Dashboard. |
| RES | `/qa/forms` | `QAFormsController` | CRUD for Form Templates. |
| GET | `/qa/reports/completion` | `QAReportsController@completionReport` | Completion Stats. |
| GET | `/qa/reports/students` | `QAReportsController@studentReport` | Student Drill-down. |

## 3. Middleware Pipeline

### 3.1 `afm.auth`
Ensures the user has a valid AFM Session Token. If not, redirects to the Landing Page (which guides to SIS).

### 3.2 Role Guards
- `EnsureAfmStudentRole`: Aborts 403 if session role != `student`.
- `EnsureAfmQaRole`: Aborts 403 if session role != `qa`.

## 4. Route Flow Diagram
*(Conceptual)*
```
[Browser] -> [Laravel Route Router]
    |
    +-> /sso/json-intake (Public) -> [SsoJsonIntakeController] -> [Session]
    |
    +-> /student/* (Middleware: afm.student)
    |     |-> pass -> [StudentController]
    |     |-> fail -> [Redirect /]
    |
    +-> /qa/* (Middleware: afm.qa)
           |-> pass -> [QAController]
           |-> fail -> [Abort 403]
```
