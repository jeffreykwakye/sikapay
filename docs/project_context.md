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


## Project Status: Super Admin Module & Core Infrastructure Complete (v1.1)
---

### **Current Features Implemented**

| Module | Features | Status | Notes |
| :--- | :--- | :--- | :--- |
| **Authentication** | Super Admin Login, Tenant User Login, Logout | Complete | Tenant isolation enforced post-login. |
| **Multi-Tenancy** | Request/Tenant Routing, Database Scoping | Core Complete | Base Model enforces `WHERE tenant_id = X` on most tables. |
| **Super Admin** | **Tenant Management (CRUD-C)** | **Complete** | **Full transactional creation** of Tenant, Admin User, Subscription, and Audit Log. |
| **System Tables** | Plans, Roles | Complete | Scoping bypass implemented for system-wide read access. |
| **Subscriptions** | Initial Trial Provisioning | Complete | Dedicated `subscriptions` and `subscription_history` tables populated transactionally. |
| **Audit/Compliance** | Audit Logging | Complete | Logs critical actions (e.g., Tenant creation) using the acting Super Admin's ID. |
| **Notifications (NEW)** | **E2E In-App System** | **Complete** | **Includes service, model, controller, display, mark-as-read functionality, and UI badge counter.** |

---

### **Upcoming Features**

* **Auto-Mark Read** on notification view
* **Tenant Admin Welcome Notification**
* Role-Based Access Control (RBAC) Enforcement
* Tenant Admin Dashboard & Payroll Approval Flow

### **Installation**

1.  **Clone Repository:** `git clone [repository URL]`
2.  **Environment:** Requires PHP 8.1+ and MySQL/MariaDB.
3.  **Database Setup:** Create a `sikapay` database.
4.  **Configuration:** Update the `Database.php` connection details.
5.  **Migrations/Seeding:** Run all migration SQL scripts, including `create_tenants.sql`, `create_users.sql`, `create_subscriptions.sql`, etc.
6.  **Start Server:** Use XAMPP/MAMP or `php -S localhost:8080 -t public`.

### **Testing Credentials**

| User Role | Email | Password |
| :--- | :--- | :--- |
| **Super Admin** | `admin@sikapay.local` | `password` |
| **Tenant Admin (Example)** | `beta.admin@beta.com` | `password` |


# 🏢 SikaPay - Core Project Context and Architecture

## I. Multi-Tenancy & Security Layer (Phase 1: Complete)

The core foundation is a multi-tenant SaaS application designed for high security and isolation.

### Key Data Structures Implemented:

| Table Group | Purpose | Key Features |
| :--- | :--- | :--- |
| **Tenancy** | Core Isolation | `tenants`, `tenant_profiles`, **`payroll_approval_flow`** setting. |
| **User Management** | Identity & Access | `users`, `user_profiles`. |
| **RBAC** | Granular Permissions | `roles`, `permissions`, `role_permissions`, `user_permissions`. |
| **Employment** | HR Records & History | `departments`, `positions`, `employees`, `employment_history`. |
| **Billing** | Feature Gating | `plans`, `features`, `plan_features`, **`subscriptions`**, **`subscription_history`**. |
| **Compliance** | Audit Trail | **`audit_logs`** (Tracking all critical actions). |
| **Application** | User Feedback | **`notifications`** (Table structure is migrated). |

---

## II. Architectural Patterns & Core Implementation (Phase 2: Complete)

### A. Base Controller Refactoring & Dependency Injection (NEW)

The base `Controller` now serves as the central context and service injector for all extending controllers.

| Component | Implementation Detail | Rationale |
| :--- | :--- | :--- |
| **Service Injection** | Base `Controller` initializes and exposes `$notificationService`, `$tenantModel`, and `$userModel`. | Centralizes dependency management and simplifies child controllers. |
| **Context Injection** | User context (`$userId`, `$tenantId`, `$userFirstName`, `$tenantName`) is calculated in the `Controller` and passed to all views. | Ensures a consistent header/footer experience (UX). |
| **View Pathing** | Resolved persistent pathing errors by using `dirname(__DIR__, 2)` to calculate a stable, absolute project root path for file inclusions. | Improves robustness and OS compatibility. |
| **Inheritance Fix** | Properties in child controllers (e.g., `TenantController`) were adjusted to use **`protected`** access level to prevent PHP fatal inheritance errors. | Adheres to strict PHP inheritance rules. |

### B. Transactional Provisioning Workflow (Super Admin)

The tenant creation process now adheres to the **Single Responsibility Principle (SRP)**, with the `TenantController::store()` method acting as a dedicated **Orchestrator** for a single, critical database transaction.

| Responsibility | Model/Service | Details |
| :--- | :--- | :--- |
| **Orchestration** | `TenantController` | Manages `PDO::beginTransaction()` and `PDO::commit()`/`rollBack()` to ensure atomicity. |
| **Role Lookup** | `RoleModel` | Dynamically retrieves the `tenant_admin` role ID. |
| **Provisioning** | `TenantModel`, `UserModel` | Creates the new tenant record and the associated primary admin user. |
| **Subscription** | `SubscriptionModel` | Inserts the initial **trial** record into `subscriptions` (current state) and `subscription_history` (audit trail). |
| **Compliance** | `AuditModel` | Logs the creation event, citing the new `tenantId` and the acting Super Admin's `user_id`. |
| **Notification Trigger** | `NotificationService` | Triggered *outside* the main transaction (post-commit) to alert the Super Admin of success. |

---

# SikaPay Project Context Log (Updated)

| Date | Feature/Decision | Details | Status |
| :--- | :--- | :--- | :--- |
| YYYY-MM-DD | Core Directory Structure | Adopted standard structure: `app/`, `resources/views/`, `public/`. | Complete |
| YYYY-MM-DD | Routing Implementation | Uses `FastRoute` via a custom `Router` class (`app/Core/Router.php`). | Complete |
| **2025-10-13** | **Authentication (Super Admin)** | Implemented `Auth` service, `LoginController`, and session management. | Complete |
| **2025-10-13** | **Multi-Tenancy Scoping** | Implemented `getTenantScope()` logic in `app/Core/Model.php`. Super Admin bypass confirmed. | Complete |
| **2025-10-13** | **Tenant Provisioning** | **Full SRP implementation.** Orchestration of Tenant, User, Subscription, and Audit Log creation in a single transaction. | **Complete** |
| **2025-10-13** | **In-App Notifications** | **Full E2E implementation** (Models, Service, Controller, Views, and Header UI integration). | **Complete** |
| **2025-10-13** | **Architecture Refinement** | Refactored Base Controller for central service injection, corrected property access levels, and fixed file pathing. | **Complete** |

## Next Focus Area

**User Experience Refinement**
* **Objective:** Implement logic to welcome the new Tenant Admin and improve notification usability.
* **Tasks:**
    1. Implement notification trigger for the newly created Tenant Admin.
    2. Implement auto-mark-as-read functionality upon viewing the notifications page.