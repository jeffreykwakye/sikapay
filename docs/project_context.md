# 💰 SikaPay: Multi-Tenant Ghanaian Payroll System

SikaPay is a custom-built, multi-tenant web application designed to handle payroll processing in Ghana, strictly adhering to all statutory requirements (PAYE, SSNIT Tiers 1, 2, 3) and accommodating complex pay components and international hires.

## 🛠️ Technology Stack

* **Language:** PHP 8.1+ (Vanilla MVC)
* **Database:** MySQL / MariaDB
* **Dependencies:** Composer, FastRoute, FPDF, PhpSpreadsheet, vlucas/phpdotenv.

## 🚀 Setup Guide

1.  **Clone Repository:** (If checking out)
    ```bash
    git clone [https://github.com/jeffreykwakye/sikapay.git](https://github.com/jeffreykwakye/sikapay.git)
    cd sikapay
    ```
2.  **Environment:** Create a `.env` file in the root directory.
    * *Note: See the `PROJECT_CONTEXT.md` for specific required environment variables.*
3.  **Composer:** Install PHP dependencies.
    ```bash
    composer install
    ```
4.  **Virtual Host:** Ensure your web server points the document root to the `/public` directory (e.g., `sikapay.localhost`).
5.  **Database:** Create the database defined in your `.env` file (e.g., `sikapay`).
6.  **CLI Setup:** After PHP files are placed, run migrations and seed initial data.
    ```bash
    php cli/cli_runner.php db:migrate
    php cli/cli_runner.php db:seed
    ```
7.  **Access:** Open your configured URL (e.g., `http://sikapay.localhost/`).


## Project Status: Core Infrastructure & RBAC Implemented (v1.2)
---

### **Current Features Implemented**

| Module | Features | Status | Notes |
| :--- | :--- | :--- | :--- |
| **Authentication** | Super Admin Login, Tenant User Login, Logout **(Auth Service is now Singleton)** | Complete | Centralized, secure session management. |
| **Security/RBAC** | **Role-Based Access Control (RBAC)** | **Core Complete** | **Implemented central `Auth::can()` gate, Permission Middleware, and protected initial routes.** |
| **Multi-Tenancy** | Request/Tenant Routing, Database Scoping | Core Complete | Base Model enforces `WHERE tenant_id = X` on most tables. |
| **Super Admin** | **Tenant Management (CRUD-C)** | **Complete** | Full transactional creation of Tenant, Admin User, Subscription, and Audit Log. |
| **System Tables** | Plans, Roles | Complete | Scoping bypass implemented for system-wide read access. |
| **Subscriptions** | Initial Trial Provisioning | Complete | Dedicated `subscriptions` and `subscription_history` tables populated transactionally. |
| **Audit/Compliance** | Audit Logging | Complete | Logs critical actions using the acting Super Admin's ID. |
| **Notifications (NEW)** | **E2E In-App System** | **Complete** | Includes service, model, controller, display, mark-as-read functionality, and UI badge counter. |
| **Employee Profile** | View, Profile Picture Upload, Staff File Upload | **Complete** | Modern, two-column layout with tabbed navigation. |

---

### **Upcoming Features**

* **Monolog Integration** for structured logging
* **Auto-Mark Read** on notification view
* **Tenant Admin Welcome Notification**
* **Employee Management Module**
* Tenant Admin Dashboard & Payroll Approval Flow

### **Testing Credentials**

| User Role | Email | Password |
| :--- | :--- | :--- |
| **Super Admin** | `admin@sikapay.local` | `password` |
| **Tenant Admin (Example)** | `beta.admin@beta.com` | `password` |


# 🏢 SikaPay - Core Project Context and Architecture

## I. Multi-Tenancy & Security Layer (Phase 2: Complete)

The core foundation is a multi-tenant SaaS application designed for high security and isolation.

### Key Data Structures Implemented:

| Table Group | Purpose | Key Features |
| :--- | :--- | :--- |
| **Tenancy** | Core Isolation | `tenants`, `tenant_profiles`, `payroll_approval_flow` setting. |
| **User Management** | Identity & Access | `users`, `user_profiles`. |
| **RBAC** | Granular Permissions | `roles`, `permissions`, `role_permissions`, `user_permissions`. |
| **Employment** | HR Records & History | `departments`, `positions`, `employees`, `employment_history`. |
| **Billing** | Feature Gating | `plans`, `features`, `plan_features`, `subscriptions`, `subscription_history`. |
| **Compliance** | Audit Trail | `audit_logs` (Tracking all critical actions). |
| **Application** | User Feedback | `notifications` (Table structure is migrated). |

---

## II. Architectural Patterns & Core Implementation (Phase 2: Complete)

### A. Authentication and Authorization (New Core System)

| Component | Implementation Detail | Rationale | Status |
| :--- | :--- | :--- | :--- |
| **Auth Service** | **Singleton Pattern** applied to `app/Core/Auth.php`. | Guarantees a single, stateful instance for DB checks and session access. | **Complete** |
| **Base Controller**| `__construct()` updated to use `Auth::getInstance()`. | Fixed the Fatal Error and adheres to the Singleton pattern. | **Complete** |
| **Authorization Gate**| **Central `Auth::can($key)` method** implements the 3-tiered logic (Super Admin > User Override > Role Default). | Single source of truth for all access control decisions. | **Complete** |
| **Route Protection** | **`app/Middleware/PermissionMiddleware.php`** created. | Centralizes pre-controller security checks and denial handling. | **Complete** |
| **Router Support** | `app/Core/Router.php` updated to support and execute the `['middleware' => [...], 'handler' => [...] ]` route structure. | Enables declarative, per-route access control. | **Complete** |

### B. Transactional Provisioning Workflow (Super Admin)

The tenant creation process adheres to the **Single Responsibility Principle (SRP)**, with the `TenantController::store()` method acting as a dedicated **Orchestrator** for a single, critical database transaction.

| Responsibility | Model/Service | Details |
| :--- | :--- | :--- |
| **Orchestration** | `TenantController` | Manages `PDO::beginTransaction()` and `PDO::commit()`/`rollBack()` to ensure atomicity. |
| **Provisioning** | `TenantModel`, `UserModel` | Creates the new tenant record and the associated primary admin user. |
| **Subscription** | `SubscriptionModel` | Inserts the initial **trial** record into `subscriptions` (current state) and `subscription_history` (audit trail). |
| **Compliance** | `AuditModel` | Logs the creation event, citing the new `tenantId` and the acting Super Admin's `user_id`. |
| **Notification Trigger** | `NotificationService` | Triggered *outside* the main transaction (post-commit) to alert the Super Admin of success. |

---

# SikaPay Project Context Log (Updated)

| Date | Feature/Decision | Details | Status |
| :--- | :--- | :--- | :--- |
| **2025-10-13** | **Authentication (Super Admin)** | Implemented `Auth` service, `LoginController`, and session management. | Complete |
| **2025-10-13** | **Multi-Tenancy Scoping** | Implemented `getTenantScope()` logic in `app/Core/Model.php`. Super Admin bypass confirmed. | Complete |
| **2025-10-13** | **Tenant Provisioning** | **Full SRP implementation.** Orchestration of Tenant, User, Subscription, and Audit Log creation in a single transaction. | **Complete** |
| **2025-10-13** | **In-App Notifications** | **Full E2E implementation** (Models, Service, Controller, Views, and Header UI integration). | **Complete** |
| **2025-10-15** | **RBAC & Architectural Fix** | **Implemented Singleton pattern for Auth**, fixed Controller instantiation, implemented **Permission Middleware** and updated **Router** to enforce security. | **Complete** |
| **2025-10-15** | **Logging & UX Complete** | **Integrate Monolog** (daily rotation/expiration). Confirmed and implemented the **Tenant Admin Welcom Notification** on provisioning success.| **Complete** |
| **2025-10-30** | **Employee Profile** | Rebuilt the employee profile page with a modern, two-column layout, profile picture upload, and staff file management. | **Complete** |


## Next Focus Area

**User Experience Refinement & Monolog**
* **Objective:** Enhance application stability and security logging.
* **Tasks:**
    1. **Install and integrate Monolog** for structured application and security logging.
    2. Implement notification trigger for the newly created Tenant Admin.
    3. Implement auto-mark-as-read functionality upon viewing the notifications page.

---