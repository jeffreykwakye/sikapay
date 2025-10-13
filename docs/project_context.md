# 💰 SikaPay: Multi-Tenant Ghanaian Payroll System

SikaPay is a custom-built, multi-tenant web application designed to handle payroll processing in Ghana, strictly adhering to all statutory requirements (PAYE, SSNIT Tiers 1, 2, 3) and accommodating complex pay components and international hires.

## 🛠️ Technology Stack

* **Language:** PHP 8.1+ (Vanilla MVC)
* **Database:** MySQL / MariaDB
* **Dependencies:** Composer, FastRoute, FPDF, PhpSpreadsheet, vlucas/phpdotenv.

## 🚀 Setup Guide

1.  **Clone Repository:** (If checking out)
    ```bash
    git clone https://github.com/jeffreykwakye/sikapay.git
    cd sikapay
    ```
2.  **Environment:** Create a `.env` file in the root directory.
    * *Note: See the `PROJECT_CONTEXT.md` for specific required environment variables.*
3.  **Composer:** Install PHP dependencies.
    ```bash
    composer install
    ```
4.  **Virtual Host:** Ensure your web server points the document root to the `/public` directory (e.g., `sikapay.localhost`).
5.  **Database:** Create the database defined in your `.env` file (e.g., `sikapay`).
6.  **CLI Setup:** After PHP files are placed, run migrations and seed initial data.
    ```bash
    php cli/cli_runner.php db:migrate
    php cli/cli_runner.php db:seed
    ```
7.  **Access:** Open your configured URL (e.g., `http://sikapay.localhost/`).


## Project Status: Super Admin Module Complete (v1.0)
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

---

### **Upcoming Features**

* In-App Notification System
* Role-Based Access Control (RBAC) Enforcement
* Tenant Admin Dashboard & Payroll Approval Flow

### **Installation**

1.  **Clone Repository:** `git clone [repository URL]`
2.  **Environment:** Requires PHP 8.1+ and MySQL/MariaDB.
3.  **Database Setup:** Create a `sikapay` database.
4.  **Configuration:** Update the `Database.php` connection details.
5.  **Migrations/Seeding:** Run all migration SQL scripts, including `create_tenants.sql`, `create_users.sql`, `create_subscriptions.sql`, etc.
6.  **Start Server:** Use XAMPP/MAMP or `php -S localhost:8080 -t public`.

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

### A. Multi-Tenancy Scoping

| Component | Implementation Detail | Rationale |
| :--- | :--- | :--- |
| **Data Scoping** | All Tenant-scoped Models inherit from `app/Core/Model.php`, which automatically injects `WHERE tenant_id = :currentTenantId`. | Guarantees data isolation. |
| **System Scoping** | Models for tables like `plans`, `roles`, and `audit_logs` utilize the **`$noTenantScope = true`** flag to bypass the `tenant_id` WHERE clause. | Allows Super Admin and system functionality without modifying core `Model` logic. |

### B. Transactional Provisioning Workflow (Super Admin)

The tenant creation process now adheres to the **Single Responsibility Principle (SRP)**, with the `TenantController::store()` method acting as a dedicated **Orchestrator** for a single, critical database transaction.

| Responsibility | Model/Service | Details |
| :--- | :--- | :--- |
| **Orchestration** | `TenantController` | Manages `PDO::beginTransaction()` and `PDO::commit()`/`rollBack()` to ensure atomicity. |
| **Role Lookup** | `RoleModel` | Dynamically retrieves the `tenant_admin` role ID. |
| **Provisioning** | `TenantModel`, `UserModel` | Creates the new tenant record and the associated primary admin user. |
| **Subscription** | `SubscriptionModel` | Inserts the initial **trial** record into `subscriptions` (current state) and `subscription_history` (audit trail). |
| **Compliance** | `AuditModel` | Logs the creation event, citing the new `tenantId` and the acting Super Admin's `user_id`. |

---

# SikaPay Project Context Log (Updated)

| Date | Feature/Decision | Details | Status |
| :--- | :--- | :--- | :--- |
| YYYY-MM-DD | Core Directory Structure | Adopted standard structure: `app/`, `resources/views/`, `public/`. | Complete |
| YYYY-MM-DD | Routing Implementation | Uses `FastRoute` via a custom `Router` class (`app/Core/Router.php`). | Complete |
| **2025-10-13** | **Authentication (Super Admin)** | Implemented `Auth` service, `LoginController`, and session management. | Complete |
| **2025-10-13** | **Multi-Tenancy Scoping** | Implemented `getTenantScope()` logic in `app/Core/Model.php`. Super Admin bypass confirmed. | Complete |
| **2025-10-13** | **Tenant Provisioning** | **Full SRP implementation.** Orchestration of Tenant, User, Subscription, and Audit Log creation in a single transaction. | **Complete** |

## Next Focus Area

**In-App Notification System**
* **Objective:** Design and implement a service-based system to generate and deliver in-app notifications (e.g., payroll submission, subscription renewal warnings) to specific users based on their role and actions.
* **Prerequisites:** `notifications` table structure is migrated.

